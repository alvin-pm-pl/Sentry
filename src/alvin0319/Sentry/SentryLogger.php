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

use Http\Promise\Promise;
use pocketmine\utils\MainLogger;
use Psr\Http\Message\ResponseInterface;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\EventType;
use Sentry\SentrySdk;
use function sprintf;

final class SentryLogger extends MainLogger{

	public function logException(\Throwable $e, $trace = null){
		parent::logException($e, $trace);
		$client = SentrySdk::getCurrentHub()->getClient();
		(function() use ($e, $client) : void{
			/* @noinspection PhpUndefinedMethodInspection */
			$scope = $this->getScope();
			(function() use ($scope, $e) : void{
				$hint = new EventHint();
				$hint->exception = $e;
				/* @noinspection PhpUndefinedMethodInspection */
				/** @var Event $event */
				$event = $this->prepareEvent(Event::createEvent(), $hint, $scope);
				if($event === null){
					return;
				}
				/* @noinspection PhpUndefinedFieldInspection */
				$transport = $this->transport;
				(function() use ($event) : void{
					/* @noinspection PhpUndefinedFieldInspection */
					$dsn = $this->options->getDsn();

					if($dsn === null){
						throw new \RuntimeException(sprintf('The DSN option must be set to use the "%s" transport.', self::class));
					}

					$eventType = $event->getType();

					/* @noinspection PhpUndefinedFieldInspection */
					if($this->rateLimiter->isRateLimited($eventType)){
						/* @noinspection PhpUndefinedFieldInspection */
						$this->warning(
							sprintf('Rate limit exceeded for sending requests of type "%s".', (string) $eventType)
						);
						return;
					}
					if(EventType::transaction() === $eventType){
						/* @noinspection PhpUndefinedFieldInspection */
						$request = $this->requestFactory->createRequest('POST', $dsn->getEnvelopeApiEndpointUrl())
							->withHeader('Content-Type', 'application/x-sentry-envelope')
							->withBody($this->streamFactory->createStream($this->payloadSerializer->serialize($event)));
					}else{
						/* @noinspection PhpUndefinedFieldInspection */
						$request = $this->requestFactory->createRequest('POST', $dsn->getStoreApiEndpointUrl())
							->withHeader('Content-Type', 'application/json')
							->withBody($this->streamFactory->createStream($this->payloadSerializer->serialize($event)));
					}
					/** @var Promise $promise */
					/* @noinspection PhpUndefinedFieldInspection */
					$promise = $this->httpClient->sendAsyncRequest($request);
					$promise->then(function(ResponseInterface $response) : void{
						/* @noinspection PhpUndefinedFieldInspection */
						$this->rateLimiter->handleResponse($response);
					});
				})->call($transport);
			})->call($client);
		})->call(SentrySdk::getCurrentHub());
	}
}