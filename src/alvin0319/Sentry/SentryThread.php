<?php

declare(strict_types=1);

namespace alvin0319\Sentry;

use pocketmine\thread\Thread;
use Threaded;
use function igbinary_unserialize;
use function Sentry\captureException;

class SentryThread extends Thread{

    /** @var Threaded */
    private Threaded $exceptions;

    public function __construct() {
        $this->exceptions = new Threaded();
    }

    public function onRun(): void{
        while(!$this->isKilled) {
            foreach($this->readExceptions() as $e){
                captureException($e);
            }
            $this->wait();
        }
    }

    public function writeException(\Throwable $t) {
        $this->synchronized(function() use ($t) {
            $this->exceptions[] = igbinary_serialize($t);
            $this->notify();
        });
    }

    private function readExceptions() : array {
        return $this->synchronized(function() : array{
            $ret = [];
            while(($e = $this->exceptions->shift()) !== null) {
                $ret[] = igbinary_unserialize($e);
            }
            return $ret;
        });
    }
}