<?php

/*
 *
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0.0
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace kim\present\realisticcompass;

use kim\present\realisticcompass\lang\PluginLang;
use kim\present\realisticcompass\listener\PlayerEventListener;
use kim\present\realisticcompass\task\CheckUpdateAsyncTask;
use kim\present\realisticcompass\task\SendNorthTask;
use pocketmine\command\{
	Command, CommandExecutor, CommandSender, PluginCommand
};
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\{
	ByteTag, ListTag
};
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;
use pocketmine\permission\{
	Permission, PermissionManager
};
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class RealisticCompass extends PluginBase implements CommandExecutor{
	public const TAG_PLUGIN = "RealisticCompass";

	/** @var RealisticCompass */
	private static $instance = null;

	/**
	 * @return RealisticCompass
	 */
	public static function getInstance() : RealisticCompass{
		return self::$instance;
	}

	/** @var PluginLang */
	private $language;

	/** @var PluginCommand */
	private $command;

	/** @var SendNorthTask */
	private $task;

	/**
	 * Called when the plugin is loaded, before calling onEnable()
	 */
	public function onLoad() : void{
		self::$instance = $this;
	}

	/**
	 * Called when the plugin is enabled
	 */
	public function onEnable() : void{
		//Save default resources
		$this->saveResource("lang/eng/lang.ini", false);
		$this->saveResource("lang/kor/lang.ini", false);
		$this->saveResource("lang/language.list", false);

		//Load config file
		$this->saveDefaultConfig();
		$this->reloadConfig();
		$config = $this->getConfig();

		//Check latest version
		if($config->getNested("settings.update-check", false)){
			$this->getServer()->getAsyncPool()->submitTask(new CheckUpdateAsyncTask());
		}

		//Load language file
		$this->language = new PluginLang($this, $config->getNested("settings.language"));
		$this->getLogger()->info($this->language->translate("language.selected", [$this->language->getName(), $this->language->getLang()]));

		//Register main command
		$this->command = new PluginCommand($config->getNested("command.name"), $this);
		$this->command->setPermission("realisticcompass.cmd");
		$this->command->setAliases($config->getNested("command.aliases"));
		$this->command->setUsage($this->language->translate("commands.realisticcompass.usage"));
		$this->command->setDescription($this->language->translate("commands.realisticcompass.description"));
		$this->getServer()->getCommandMap()->register($this->getName(), $this->command);

		//Load permission's default value from config
		$permissions = PermissionManager::getInstance()->getPermissions();
		$defaultValue = $config->getNested("permission.main");
		if($defaultValue !== null){
			$permissions["realisticcompass.cmd"]->setDefault(Permission::getByName($config->getNested("permission.main")));
		}

		//Register repeating tasks
		$this->task = new SendNorthTask();
		$this->getScheduler()->scheduleRepeatingTask($this->task, (int) $config->getNested("send-delay", 20));

		//Register event listeners
		$this->getServer()->getPluginManager()->registerEvents(new PlayerEventListener($this), $this);
	}

	/**
	 * @param CommandSender $sender
	 * @param Command       $command
	 * @param string        $label
	 * @param string[]      $args
	 *
	 * @return bool
	 */
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if($sender instanceof Player){
			$item = $sender->getInventory()->getItemInHand();
			if($item->getId() !== Item::COMPASS){
				$sender->sendMessage($this->language->translate("commands.realisticcompass.notCompass"));
			}else{
				$item->setCustomName($this->language->translate("realisticcompass.name"));
				$item->setLore(explode("\\n", $this->language->translate("realisticcompass.lore")));
				$item->setNamedTagEntry(new ListTag(Item::TAG_ENCH, [], NBT::TAG_Compound)); //for make it cool
				$item->setNamedTagEntry(new ByteTag(self::TAG_PLUGIN, 1)); //for check
				$sender->getInventory()->setItemInHand($item);

				$sender->sendMessage($this->language->translate("commands.realisticcompass.success"));
			}
		}else{
			$sender->sendMessage($this->language->translate("commands.generic.onlyPlayer"));
		}
		return true;
	}

	/**
	 * @Override for multilingual support of the config file
	 *
	 * @return bool
	 */
	public function saveDefaultConfig() : bool{
		$resource = $this->getResource("lang/{$this->getServer()->getLanguage()->getLang()}/config.yml");
		if($resource === null){
			$resource = $this->getResource("lang/" . PluginLang::FALLBACK_LANGUAGE . "/config.yml");
		}

		if(!file_exists($configFile = $this->getDataFolder() . "config.yml")){
			$ret = stream_copy_to_stream($resource, $fp = fopen($configFile, "wb")) > 0;
			fclose($fp);
			fclose($resource);
			return $ret;
		}
		return false;
	}

	/**
	 * @return PluginLang
	 */
	public function getLanguage() : PluginLang{
		return $this->language;
	}

	/**
	 * @return SendNorthTask
	 */
	public function getTask() : SendNorthTask{
		return $this->task;
	}

	/**
	 * @param Player $player
	 */
	public static function sendNorth(Player $player) : void{
		$pk = new SetSpawnPositionPacket();
		$target = $player->subtract(0, 0, 0x7fff)->round(); //North is negative Z
		$pk->x = $target->x;
		$pk->y = $target->y;
		$pk->z = $target->z;
		$pk->spawnType = SetSpawnPositionPacket::TYPE_WORLD_SPAWN;
		$pk->spawnForced = true;
		$player->sendDataPacket($pk);
	}

	/**
	 * @param Player $player
	 */
	public static function sendReal(Player $player) : void{
		$pk = new SetSpawnPositionPacket();
		$target = $player->level->getSafeSpawn();
		$pk->x = $target->x;
		$pk->y = $target->y;
		$pk->z = $target->z;
		$pk->spawnType = SetSpawnPositionPacket::TYPE_WORLD_SPAWN;
		$pk->spawnForced = true;
		$player->sendDataPacket($pk);
	}
}