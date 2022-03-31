<?php

/**
 *   ____             _
 *  / ___|  ___ _ __ | |_ _ __ _   _
 * \___ \ / _ \ '_ \| __| '__| | | |
 *   ___) |  __/ | | | |_| |  | |_| |
 *  |____/ \___|_| |_|\__|_|   \__, |
 *                             |___/
 * MIT License
 *
 * Copyright (c) 2022 alvin0319
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);

namespace alvin0319\Sentry;

use pocketmine\thread\Thread;
use function igbinary_unserialize;
use function Sentry\captureException;

class SentryThread extends Thread{

	/** @var \Threaded */
	private \Threaded $exceptions;

	public function __construct(){
		$this->exceptions = new \Threaded();
	}

	public function onRun() : void{
		while(!$this->isKilled){
			foreach($this->readExceptions() as $e){
				captureException($e);
			}
			$this->wait();
		}
	}

	public function writeException(\Throwable $t){
		$this->synchronized(function() use ($t){
			$this->exceptions[] = igbinary_serialize($t);
			$this->notify();
		});
	}

	private function readExceptions() : array{
		return $this->synchronized(function() : array{
			$ret = [];
			while(($e = $this->exceptions->shift()) !== null){
				$ret[] = igbinary_unserialize($e);
			}
			return $ret;
		});
	}
}