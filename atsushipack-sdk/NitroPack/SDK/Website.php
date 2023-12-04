<?php

namespace NitroPack\SDK;

class Website {
    private $name;
    private $url;
    private $apikey;
    private $apisecret;
    private $used_disk_space_bytes;
    private $used_optimizations;
    private $last_quota_reset_timestamp;
    private $status;
    private $created_timestamp;
    private $modified_timestamp;

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getURL() {
        return $this->url;
    }

    public function setURL($url) {
        $this->url = $url;
    }

    public function getAPIKey() {
        return $this->apikey;
    }

    public function setAPIKey($apikey) {
        $this->apikey = $apikey;
    }

    public function getAPISecret() {
        return $this->apisecret;
    }

    public function setAPISecret($apisecret) {
        $this->apisecret = $apisecret;
    }

    public function getUsedDiskSpaceBytes() {
        return $this->used_disk_space_bytes;
    }

    public function setUsedDiskSpaceBytes($used_disk_space_bytes) {
        $this->used_disk_space_bytes = $used_disk_space_bytes;
    }

    public function getUsedOptimizations() {
        return $this->used_optimizations;
    }

    public function setUsedOptimizations($used_optimizations) {
        $this->used_optimizations = $used_optimizations;
    }

    public function getLastQuotaResetTimestamp() {
        return $this->last_quota_reset_timestamp;
    }

    public function setLastQuotaResetTimestamp($last_quota_reset_timestamp) {
        $this->last_quota_reset_timestamp = $last_quota_reset_timestamp;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function getCreatedTimestamp() {
        return $this->created_timestamp;
    }

    public function setCreatedTimestamp($created_timestamp) {
        $this->created_timestamp = $created_timestamp;
    }

    public function getModifiedTimestamp() {
        return $this->modified_timestamp;
    }

    public function setModifiedTimestamp($modified_timestamp) {
        $this->modified_timestamp = $modified_timestamp;
    }
}
