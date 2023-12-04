<?php

namespace NitroPack\HttpClient;

use \NitroPack\HttpClient\Exceptions\ProxyConnectException;
use \NitroPack\HttpClient\Exceptions\ProxyConnectTimedOutException;

class HttpClientSocks4Proxy extends HttpClientSocksProxy {
    private $isConnectPending;
    private $resolveOnProxy;
    private $sendFrame;
    private $recvFrame;
    private $lastIo;
    private $connectTime;
    private $connectStart;

    public function __construct($ip, $port, $resolveOnProxy = false) {
        parent::__construct($ip, $port);

        $this->isConnectPending = false;
        $this->resolveOnProxy = $resolveOnProxy;
        $this->sendFrame = "";
        $this->recvFrame = "";
        $this->lastIo = 0;
    }

    public function getConnectTime() {
        return $this->connectTime;
    }

    public function resolveOnProxy($status) {
        $this->resolveOnProxy = $status;
    }

    public function connect($sock, $ip, $port, $domain = NULL) {
        $this->prepareSendFrame($ip, $port, $domain);
        $this->recvFrame = "";

        if (@fwrite($sock, $this->sendFrame) === false) {
            throw new ProxyConnectException("Proxy communication error: cannot send CONNECT frame");
        }

        $this->recvFrame = @fread($sock, 8);

        if (!$this->recvFrame) {
            $metaData = stream_get_meta_data($sock);
            if (!empty($metaData["timed_out"])) {
                throw new ProxyConnectTimedOutException("Connecting to server timed out");
            } else {
                throw new ProxyConnectException("Empty proxy response");
            }
        }

        $bytes = unpack("CVN/CCD/SDSTPORT/LDSTIP", $this->recvFrame);

        if ($bytes["CD"] != 90) {
            throw new ProxyConnectException("Connection rejected by proxy with code " . $bytes["CD"]);
        }

        return true;
    }

    public function connectAsync($sock, $ip, $port, $domain = NULL, $timeout = 5) {
        $now = microtime(true);
        if (!$this->isConnectPending) {
            $this->prepareSendFrame($ip, $port, $domain);
            $this->recvFrame = "";
            $this->isConnectPending = true;
            $this->lastIo = $now;
            $this->connectStart = $now;
        }

        if ($timeout && $now - $this->lastIo >= $timeout) {
            $this->connectTime = $now - $this->connectStart;
            $this->isConnectPending = false;
            throw new ProxyConnectTimedOutException(sprintf("Proxy connect timed out: Connecting to origin took more than %s
                seconds", $timeout));
        }

        if ($this->sendFrame) { // We have more data to send here
            $written = @fwrite($sock, $this->sendFrame);
            if ($written === false) {
                $this->isConnectPending = false;
                $this->connectTime = $now - $this->connectStart;
                throw new ProxyConnectException("Proxy communication error: cannot send CONNECT frame");
            } else {
                $this->sendFrame = substr($this->sendFrame, $written);
                if (!$this->sendFrame) {
                    $this->lastIo = microtime(true);
                }
                return false;
            }
        } else { // Read te proxy's reply
            $this->recvFrame .= @fread($sock, 8);

            if ($this->recvFrame === false) {
                $this->isConnectPending = false;
                $this->connectTime = $now - $this->connectStart;
                throw new ProxyConnectException("Proxy communication error: cannot read CONNECT reply frame");
            } else if (strlen($this->recvFrame) == 8) {
                $bytes = unpack("CVN/CCD/SDSTPORT/LDSTIP", $this->recvFrame);
                $this->isConnectPending = false;
                $this->connectTime = $now - $this->connectStart;

                if ($bytes["CD"] != 90) {
                    throw new ProxyConnectException("Connection rejected by proxy with code " . $bytes["CD"]);
                }

                return true;
            } else {
                return false;
            }
        }

        $this->isConnectPending = false;
        return true;
    }

    private function prepareSendFrame($ip, $port, $domain = NULL) {
        if ($domain && $this->resolveOnProxy) $ip = "0.0.0.1";

        $ipInt = ip2long($ip);
        if ($ipInt === -1 || $ipInt === false) {
            throw new ProxyConnectException("Invalid destination IP");
        }

        $this->sendFrame = pack("CCnNx", 4, 1, $port, $ipInt);

        if ($domain && $this->resolveOnProxy) {
            $this->sendFrame .= $domain . pack("x");
        }
    }
}