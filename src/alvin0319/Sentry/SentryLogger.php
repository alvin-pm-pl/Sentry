<?php

declare(strict_types=1);

namespace alvin0319\Sentry;

use pocketmine\utils\MainLogger;
use const PTHREADS_INHERIT_NONE;

final class SentryLogger extends MainLogger{

    private SentryThread $sentryThread;

    public function __construct(string $logFile, bool $useFormattingCodes, string $mainThreadName, \DateTimeZone $timezone, bool $logDebug = false){
        parent::__construct($logFile, $useFormattingCodes, $mainThreadName, $timezone, $logDebug);

        $this->sentryThread = new SentryThread();
        $this->sentryThread->start(PTHREADS_INHERIT_NONE);
    }

    public function logException(\Throwable $e, $trace = null){
		parent::logException($e, $trace);
		$this->sentryThread->writeException($e);
	}
}