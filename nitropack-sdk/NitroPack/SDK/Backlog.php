<?php

namespace NitroPack\SDK;
use NitroPack\SDK\Api\ResponseStatus;

class Backlog {
    const TTL = 3600; // 1 hour in seconds

    private $dataDir;
    private $nitropack;
    private $communicators;
    private $queue;
    private $queuePath;
    private $isAcquired;
    private $fileHandle;
    private $header;
    private $backlogFile = array('data', 'backlog.queue');

    public function __construct($dataDir, $nitropack) {
        $this->dataDir = $dataDir;
        $this->nitropack = $nitropack;
        $this->communicators = array();
        $this->queue = array();
        $this->queuePath = $this->getQueuePath();
        $this->isAcquired = false;
        $this->fileHandle = NULL;
        $this->header = new \stdClass();
        $this->header->offset = 0;
        $this->header->firstProcessingTimestamp = 0;
        $this->header->lastProcessingTimestamp = 0;
    }

    public function __destruct() {
        $this->closeHandle();
    }

    public function delete() {
        Filesystem::deleteFile($this->queuePath);
    }

    public function append($entry) {
        if (defined("NITROPACK_DISABLE_BACKLOG")) return;
        $fh = $this->getHandle();
        Filesystem::flock($fh, LOCK_EX);
        Filesystem::fseek($fh, 0, SEEK_END);
        $this->writeEntry($fh, $this->encodeEntry($entry));
        Filesystem::flock($fh, LOCK_UN);
    }

    public function replay($timeLimit = 10) {
        if (defined("NITROPACK_DISABLE_BACKLOG")) return;
        $fh = $this->getHandle();
        Filesystem::flock($fh, LOCK_EX);
        $lastProcessingTimestamp = $this->header->lastProcessingTimestamp;
        if (time() - $lastProcessingTimestamp <= $timeLimit) {
            return false;
        }
        $this->acquireBacklog($fh);
        Filesystem::flock($fh, LOCK_UN);

        $initialProcesssTime = $this->header->firstProcessingTimestamp;
        if (time() - $initialProcesssTime > self::TTL) {
            throw new BacklogReplayTimeoutException(sprintf("Backlog replay did not complete within %s seconds", self::TTL));
            // In case there have been previous attempts at clearing the backlog and these attempts started more than the specified TTL seconds ago
            // Perform a full purge and clear the backlog
        }

        if ($this->header->offset > 0) {
            Filesystem::fseek($fh, $this->header->offset, SEEK_SET);
        }
        $start = microtime(true);
        while (!$this->isEndOfQueue($fh) && NULL != ($entry = $this->getentry($fh)) && microtime(true) - $start < $timeLimit) {
            $elapsedTime = microtime(true) - $start;
            try {
                $this->replayEntry($entry, $timeLimit - $elapsedTime);
            } catch (\Exception $e) {
                break;
            }
        }

        if ($this->isEndOfQueue($fh)) {
            $this->closeHandle();
            $this->delete();
            return true;
        }

        return false;
    }

    private function isEndOfQueue($fh) {
        return Filesystem::feof($fh);
    }

    public function dumpEntries() {
        $fh = $this->getHandle();
        while (!$this->isEndOfQueue($fh) && NULL != ($entry = $this->getentry($fh))) {
            try {
                var_dump($entry);
            } catch (\Exception $e) {
                break;
            }
        }
    }

    public function dumpHeader() {
        $this->getHandle();
        var_dump($this->header);
    }

    public function resetOffset() {
        $fh = $this->getHandle();
        $this->header->offset = 0;
        $this->writeHeader($fh);
    }

    public function exists() {
        if (defined("NITROPACK_DISABLE_BACKLOG")) return false;
        return Filesystem::fileExists($this->queuePath);
    }

    private function replayEntry($entry, $timeLimit) {
        if (array_key_exists("siteSecret", $entry)) {
            $requestMaker = $this->nitropack->getApi()->secure_request_maker;
        } else {
            $requestMaker = $this->nitropack->getApi()->request_maker;
        }
        $httpResponse = $requestMaker->makeRequest($entry["path"], $entry["headers"], $entry["cookies"], $entry["type"], $entry["bodyData"], false, $entry["verifySSL"]);
        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        $headers = $httpResponse->getHeaders();

        $start = microtime(true);
        while ($status == ResponseStatus::OK && !empty($headers["x-nitro-repeat"]) && microtime(true) - $start < $timeLimit) {
            $httpResponse->replay(); // In reality $httpResponse is an instance of HttpClient which has the replay method
            $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
            $headers = $httpResponse->getHeaders();
            if ($status != ResponseStatus::OK) {
                throw \RuntimeException("Unable to replay backlogged entry");
            }
        }

        if ($status == ResponseStatus::OK && empty($headers["x-nitro-repeat"])) {
            $fh = $this->getHandle();
            $this->header->offset = Filesystem::ftell($fh);
            $this->writeHeader($fh);
        }
    }

    private function getQueuePath() {
        $backlogFile = $this->backlogFile;
        array_unshift($backlogFile, $this->dataDir);
        return Filesystem::getOsPath($backlogFile);
    }

    private function getEntry($fh = NULL) {
        $closeFile = empty($fh);
        $fh = !empty($fh) ? $fh : $this->getHandle();
        $entry = @Filesystem::fgets($fh);
        if ($closeFile) {
            Filesystem::fclose($fh);
        }
        return $this->decodeEntry($entry);
    }

    private function writeEntry($fh, $entry) {
        Filesystem::fwrite($fh, $entry . "\n");
        Filesystem::fflush($fh);
    }

    private function encodeEntry($entry) {
        return base64_encode(json_encode($entry));
    }

    private function decodeEntry($entry) {
        return json_decode(base64_decode($entry), true);
    }

    private function acquireBacklog($fh) {
        $this->header->lastProcessingTimestamp = time();
        if (!$this->header->firstProcessingTimestamp) {
            $this->header->firstProcessingTimestamp = $this->header->lastProcessingTimestamp;
        }
        $this->writeHeader($fh);
        $this->isAcquired = true;
    }

    private function releaseBacklog($fh) {
        $this->header->lastProcessingTimestamp = 0;
        $this->writeHeader($fh);
        $this->isAcquired = false;
    }

    private function readHeader($fh) {
        $offsetBackup = Filesystem::ftell($fh);
        Filesystem::fseek($fh, 0, SEEK_SET);
        $header = Filesystem::fread($fh, 12);
        $parts = unpack("Loffset/LfirstProcessingTimestamp/LlastProcessingTimestamp", $header);
        $this->header->offset = $parts["offset"];
        $this->header->firstProcessingTimestamp = $parts["firstProcessingTimestamp"];
        $this->header->lastProcessingTimestamp = $parts["lastProcessingTimestamp"];
        Filesystem::fseek($fh, $offsetBackup, SEEK_SET);
    }

    private function writeHeader($fh) {
        $offsetBackup = Filesystem::ftell($fh);
        Filesystem::fseek($fh, 0, SEEK_SET);
        Filesystem::fwrite($fh, pack("L*", $this->header->offset, $this->header->firstProcessingTimestamp, $this->header->lastProcessingTimestamp), 12);
        Filesystem::fflush($fh);
        Filesystem::fseek($fh, $offsetBackup, SEEK_SET);
    }

    private function getHandle() {
        if ($this->fileHandle) return $this->fileHandle;

        $fh = Filesystem::fopen($this->queuePath, "c+");
        Filesystem::flock($fh, LOCK_EX);
        Filesystem::fseek($fh, 0, SEEK_END);
        $pos = Filesystem::ftell($fh);
        if ($pos > 11) { // The first 12 bytes are reserved for the header. If the last pos is further than the 12th byte then the log is considered initialized
            $this->readHeader($fh);
        } else {
            $this->writeHeader($fh);
        }
        Filesystem::fseek($fh, 12, SEEK_SET);
        Filesystem::flock($fh, LOCK_UN);

        $this->fileHandle = $fh;

        return $this->fileHandle;
    }

    private function closeHandle() {
        if ($this->fileHandle) {
            if ($this->isAcquired) {
                $this->releaseBacklog($this->fileHandle);
            }
            Filesystem::fclose($this->fileHandle);
            $this->fileHandle = NULL;
        }
    }
}
