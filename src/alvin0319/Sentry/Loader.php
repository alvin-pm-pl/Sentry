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

use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginLogger;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\Terminal;
use pocketmine\utils\Timezone;
use Webmozart\PathUtil\Path;
use function array_merge;
use function is_dir;
use function Sentry\init;

final class Loader extends PluginBase{

	public static string $vendorPath = "";

	private bool $enabled = true;

	protected function onLoad() : void{
		if(!is_dir(Path::join($this->getFile(), "vendor"))){
			$this->getLogger()->critical("Couldn't find the composer dependencies. Please re-install the plugin.");
			$this->enabled = false;
			return;
		}
		$this->saveDefaultConfig();
		if($this->getConfig()->get("sentry-dsn", "") === ""){
			$this->getLogger()->critical("Please set the sentry-dsn in the config.yml");
			$this->enabled = false;
			return;
		}
		self::$vendorPath = Path::join($this->getFile(), "vendor", "autoload.php");

		require self::$vendorPath;

		init(array_merge([
			"dsn" => $this->getConfig()->get("sentry-dsn")
		], $this->getConfig()->get("sentry-options", [])));
		$prop = new \ReflectionProperty(Server::class, "logger");
		if(!$prop->isPublic()){
			$prop->setAccessible(true);
		}
		/** @var MainLogger $value */
		$value = $prop->getValue($this->getServer());
		unset($value); // make sure to call __destruct()
		$logger = new SentryLogger(Path::join($this->getServer()->getDataPath(), "server.log"), Terminal::hasFormattingCodes(), "Server", new \DateTimeZone(Timezone::get()), $this->getServer()->getConfigGroup()->getPropertyInt("debug.level", 1) > 1);
		\GlobalLogger::set($logger);
		$prop->setValue($this->getServer(), $logger);
		$this->getScheduler()->scheduleTask(new ClosureTask(function() : void{
			foreach($this->getServer()->getPluginManager()->getPlugins() as $plugin){
				$loggerProp = new \ReflectionProperty(PluginBase::class, "logger");
				if(!$loggerProp->isPublic()){
					$loggerProp->setAccessible(true);
				}
				$newLogger = new PluginLogger($this->getServer()->getLogger(), $plugin->getDescription()->getPrefix() !== "" ? $plugin->getDescription()->getPrefix() : $plugin->getDescription()->getName());
				$loggerProp->setValue($plugin, $newLogger);
			}
		}));
	}

	protected function onEnable() : void{
		if(!$this->enabled){
			$this->getServer()->getPluginManager()->disablePlugin($this);
		}
	}
}