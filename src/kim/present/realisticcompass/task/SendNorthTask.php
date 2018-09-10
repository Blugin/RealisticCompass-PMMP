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
 * it under the terms of the MIT License. see <https://opensource.org/licenses/MIT>.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://opensource.org/licenses/MIT MIT License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace kim\present\realisticcompass\task;

use kim\present\realisticcompass\RealisticCompass;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class SendNorthTask extends Task{
	/** @var Player[] */
	private $players = [];

	/**
	 * Actions to execute when run
	 *
	 * @param int $currentTick
	 *
	 * @return void
	 */
	public function onRun(int $currentTick){
		foreach($this->players as $name => $player){
			RealisticCompass::sendNorth($player);
		}
	}

	/**
	 * @param Player $player
	 */
	public function addPlayer(Player $player) : void{
		$this->players[$player->getLowerCaseName()] = $player;
	}

	/**
	 * @param Player $player
	 */
	public function removePlayer(Player $player) : void{
		unset($this->players[$player->getLowerCaseName()]);
	}
}