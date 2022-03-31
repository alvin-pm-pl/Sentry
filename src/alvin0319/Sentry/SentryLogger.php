<?php

declare(strict_types=1);

namespace alvin0319\Sentry;

use pocketmine\utils\MainLogger;
use function Sentry\captureException;

final class SentryLogger extends MainLogger{

	public function logException(\Throwable $e, $trace = null){
		parent::logException($e, $trace);
		captureException($e);
	}
}