<?php
namespace NitroPack\SDK;

class ExcludeEntry {
    public $resourceType = NULL;
    public $resourceRelation = NULL;
    public $device = NULL;
    public $layout = NULL;
    public $string = "";
    public $operation;

    public function __construct()
    {
        $this->operation = new \stdClass();
    }
}