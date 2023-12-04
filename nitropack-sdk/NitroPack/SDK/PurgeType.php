<?php
namespace NitroPack\SDK;

class PurgeType {
    const COMPLETE       = 0x1;
    const INVALIDATE     = 0x2;
    const PAGECACHE_ONLY = 0x4;
    /**
     * Used to request purge excluding images from the servers of NitroPack
     */
    const LIGHT_PURGE    = 0x8;
}
