<?php

namespace NitroPack\HttpClient;

class HttpClientMulti {
    private $clients;
    private $successCallback;
    private $errorCallback;
    private $returnClients;
    private $intervals;
    private $clientRegistrationTimes;

    public function __construct() {
        $this->clients = array();
        $this->intervals = array();
        $this->successCallback = NULL;
        $this->errorCallback = NULL;
        $this->returnClients = true;
        $this->clientRegistrationTimes = new \SplObjectStorage();
    }

    public function returnClients($status) {
        $this->returnClients = $status;
    }

    public function push($client) {
        $this->clients[] = $client;
        $this->clientRegistrationTimes->attach($client, time());
    }

    public function getClients() {
        return $this->clients;
    }

    public function onSuccess($callback) {
        $this->successCallback = $callback;
    }

    public function onError($callback) {
        $this->errorCallback = $callback;
    }

    public function setInterval($callback, $interval) { // Interval is expected in ms
        $this->intervals[] = new HttpClientTimer($callback, $interval, HttpClientTimerType::INTERVAL);
    }

    public function executeIntervals() {
        if (!$this->intervals) return;
        foreach ($this->intervals as $task) {
            if ($task->hasToExecute()) {
                $task->execute();
            }
        }
    }

    public function getNextInterval() {
        $delays = array();
        foreach ($this->intervals as $task) {
            $delays[] = $task->getRemainingDelay();
        }
        return min($delays);
    }

    public function fetchAll($follow_redirects = true, $method = "GET") {
        foreach ($this->clients as $client) {
            $client->fetch($follow_redirects, $method, true);
        }

        return $this->readAll();
    }

    /* Returns an array with succeeded and failed clients
     * [
     *     [succeeded clients...],
     *     [[failed client, exception]...]
     * ]
     */
    public function readAll() {
        $succeededClients = [];
        $failedClients = [];

        while ($this->clients) {
            // Check whether to sleep using a syscall in order to conserve CPU usage
            $except = NULL;
            $read = [];
            $write = [];
            $remainingTimeouts = [];
            $canSleep = true;

            if ($this->intervals) {
                $nextInterval = $this->getNextInterval();

                if ($nextInterval < 5) {
                    $this->executeIntervals();
                    $nextInterval = $this->getNextInterval();
                }

                $remainingTimeouts[] = $nextInterval / 1000; // We need to save the remaining delay in seconds
            }

            foreach ($this->clients as $client) {
                if ($client->getState() == HttpClientState::READY || $client->getState() == HttpClientState::INIT || $client->getState() == HttpClientState::CONNECT) {
                    $canSleep = false;
                    break;
                }

                switch ($client->getState()) {
                case HttpClientState::SSL_HANDSHAKE:
                    $read[] = $client->sock;
                    $write[] = $client->sock;
                    $operationTimeout = $client->ssl_timeout ? $client->ssl_timeout : $client->timeout;
                    $remainingTimeout = $operationTimeout - (microtime(true) - $client->ssl_negotiation_start);
                    break;
                case HttpClientState::SEND_REQUEST:
                    $write[] = $client->sock;
                    $operationTimeout = $client->timeout;
                    $remainingTimeout = $operationTimeout - (microtime(true) - $client->last_write);
                    break;
                case HttpClientState::DOWNLOAD:
                    if ($client->wasEmptyRead()) {
                        $read[] = $client->sock;
                        $operationTimeout = $client->timeout;
                        $remainingTimeout = $operationTimeout - (microtime(true) - $client->last_read);
                    } else {
                        $canSleep = false;
                        break 2;
                    }
                    break;
                default:
                    $canSleep = false;
                    break 2;
                }

                $remainingTimeouts[] = $remainingTimeout;
            }

            if ($canSleep) {
                $read = $read ? $read : NULL;
                $write = $write ? $write : NULL;

                if (!empty($read) || !empty($write)) {
                    if (defined("NITROPACK_USE_MICROTIMEOUT") && NITROPACK_USE_MICROTIMEOUT) {
                        stream_select($read, $write, $except, 0, NITROPACK_USE_MICROTIMEOUT);
                    } else {
                        $microtimeout = (int)(min($remainingTimeouts) * 1000000);
                        if ($microtimeout > 0) {
                            $streamsWithChange = stream_select($read, $write, $except, 0, 0);
                            if ($streamsWithChange === 0) {
                                usleep(20000);// 20ms
                            }
                        }
                    }
                } else {
                    usleep(20000);// 20ms
                }
            }
            // End check

            foreach ($this->clients as $client) {
                try {
                    if ($client->asyncLoop()) {
                        $this->removeClient($client);
                        if ($this->returnClients) {
                            $succeededClients[] = $client;
                        }
                        if ($this->successCallback) {
                            call_user_func($this->successCallback, $client);
                        }
                    }
                } catch (\Exception $e) {
                    $this->removeClient($client);
                    if ($this->returnClients) {
                        $failedClients[] = [$client, $e];
                    }
                    if ($this->errorCallback) {
                        call_user_func($this->errorCallback, $client, $e);
                    }
                }
            }
        }

        return [$succeededClients, $failedClients];
    }

    public function evictStuckClients($timeout = 300) {
        $evictedClients = [];
        $now = time();
        foreach ($this->clients as $client) {
            try {
                $clientRegistrationTime = $this->clientRegistrationTimes->offsetGet($client);
            } catch (\UnexpectedValueException $e) {
                $clientRegistrationTime = 0;
                // Weird client - remove it
            }

            if ($now - $clientRegistrationTime >= $timeout) {
                $evictedClients[] = $client;
                //echo sprintf("Evicting stuck client for %s in state %s \n", $client->URL, $client->getState());
                $client->abort();
                $this->removeClient($client);
            }
        }

        return $evictedClients;
    }

    public function removeClient($client) {
        $index = array_search($client, $this->clients, true);
        if ($index !== false) { // Index can be false if the client has been evicted earlier
            array_splice($this->clients, $index, 1);
        }

        if ($this->clientRegistrationTimes->offsetExists($client)) { // Offset may not exist if the client has been evicted earlier
            $this->clientRegistrationTimes->detach($client);
        }
    }
}