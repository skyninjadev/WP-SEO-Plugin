<?php
namespace NitroPack\HttpClient;

class HttpClientTimer {
    public static $idCounter = 0;

    public $id;
    public $type;

    private $callback;
    private $delay;
    private $timeSet;

    public function __construct($callback, $delay, $type) { // $delay is in milliseconds
        $this->id = ++HttpClientTimer::$idCounter;
        $this->callback = $callback;
        $this->delay = $delay;
        $this->type = $type;

        $this->timeSet = microtime(true);
    }

    public function hasToExecute() {
        return (microtime(true) - $this->timeSet) * 1000 >= $this->delay;
    }

    public function execute() {
        call_user_func($this->callback);
        $this->timeSet = microtime(true);
    }

    public function getRemainingDelay() {
        return $this->delay - ((microtime(true) - $this->timeSet) * 1000);
    }
}