<?php

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
use function is_dir;
use function Sentry\init;

final class Loader extends PluginBase{

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
		require Path::join($this->getFile(), "vendor", "autoload.php");
		init([
			"dsn" => $this->getConfig()->get("sentry-dsn")
		]);
		$prop = new \ReflectionProperty(Server::class, "logger");
		if(!$prop->isPublic()){
			$prop->setAccessible(true);
		}
		/** @var MainLogger $value */
		$value = $prop->getValue($this->getServer());
		unset($value); // make sure to call __destruct()
		$logger = new SentryLogger(Path::join($this->getServer()->getDataPath(), "server.log"), Terminal::hasFormattingCodes(), "Server", new \DateTimeZone(Timezone::get()));
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