<?php
namespace NitroPack\SDK;

class ExcludeOperationType {
    const OPTIMIZE              = 0x1;
    const MINIFY                = 0x2;
    const RENDER_BLOCKING_FIX   = 0x4;
    const COMBINE               = 0x8;
    const RESIZE                = 0x10;
    const REMOVE_UNUSED_CSS     = 0x20;
    const PAGE_PREFETCH         = 0x40;
    const ALL                   = 0xFF;
}