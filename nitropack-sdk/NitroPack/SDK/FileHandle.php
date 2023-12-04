<?php
namespace NitroPack\SDK;

class FileHandle {
    protected $handle;

    public function __construct($handle) {
        $this->handle = $handle;
    }

    public function getHandle() {
        return $this->handle;
    }

    public function setHandle($handle) {
        $this->handle = $handle;
    }
}
