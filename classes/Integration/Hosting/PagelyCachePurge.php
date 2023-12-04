<?php

namespace NitroPack\Integration\Hosting;

class PagelyCachePurge extends \PagelyCachePurge {
    public function __construct() {
        parent::__construct();
        $this->deferred = false;
    }
}
