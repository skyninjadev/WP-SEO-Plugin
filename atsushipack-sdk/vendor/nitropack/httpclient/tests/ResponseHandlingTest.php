<?php

use PHPUnit\Framework\TestCase;
use React\Http\Message\Response;
use React\Http\HttpServer;
use Psr\Http\Message\ServerRequestInterface;
use NitroPack\HttpClient\HttpClient;

class ResponseHandlingTest extends TestCase
{
    private $serverPid;
    private $serverUrl;

    private function startServer() {
        echo "Starting server..." . PHP_EOL;
        $loop = React\EventLoop\Loop::get();
        $server = new HttpServer(function (ServerRequestInterface $request) {
            if ($request->getUri()->getPath() === '/health') {
                return new Response(200, [], 'OK');
            }

            $requestPath = ltrim($request->getUri()->getPath(), '/');
            $responseFile = __DIR__ . "/fixtures/response-{$requestPath}.json";
            if (file_exists($responseFile)) {
                $resp = json_decode(file_get_contents($responseFile), true);

                switch ($resp["bodyEncoding"]) {
                    case "base64":
                        return new Response($resp["statusCode"], $resp["headers"], base64_decode($resp["body"]));
                    default:
                        return new Response($resp["statusCode"], $resp["headers"], $resp["body"]);
                }
            }

            return new Response(404, [], 'Resource Not Found ' . $responseFile);
        });
        
        $socket = new React\Socket\SocketServer('0.0.0.0:8000');
        $server->listen($socket);
        echo 'Server running at http://0.0.0.0:8000' . PHP_EOL;
        $loop->run();
    }

    public function setUp(): void
    {
        // Start the web server in a separate process

        $pid = pcntl_fork();
        if ($pid == -1) {
            die('Could not fork');
        } else if ($pid) {
            // we are the parent
            $this->serverPid = $pid;
        } else {
            $this->startServer();
        }

        // Wait for the web server to start
        $this->serverUrl = "http://localhost:8000";
        $healthEndpoint = "{$this->serverUrl}/health";
        while (@file_get_contents($healthEndpoint) === false) {
            usleep(10000); // Wait for 10 milliseconds
        }
    }

    public function tearDown(): void
    {
        // Stop the web server
        posix_kill($this->serverPid, SIGTERM);
        pcntl_wait($status); //Protect against Zombie children
    }

    public function testGzipCommentIsParsedCorrectly()
    {
        $client = new HttpClient("{$this->serverUrl}/gzip-comment");
        $client->accept_deflare = true;

        $errors = [];
        set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$errors) {
            $errors[] = "$errstr in $errfile on line $errline";
        });
        $client->fetch();
        restore_error_handler();
        $this->assertEmpty($errors, "There were errors while reading gzipped response: \n" . implode("\n", $errors));
    }
}