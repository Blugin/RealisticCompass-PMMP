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

namespace kim\present\realisticcompass\listener;

use kim\present\realisticcompass\RealisticCompass;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\item\Item;

class PlayerEventListener implements Listener{
	/** @var RealisticCompass */
	private $plugin;

	/**
	 * PlayerEventListener constructor.
	 *
	 * @param RealisticCompass $plugin
	 */
	public function __construct(RealisticCompass $plugin){
		$this->plugin = $plugin;
	}

	/**
	 * @priority MONITOR
	 *
	 * @param PlayerItemHeldEvent $event
	 */
	public function onPlayerItemHeldEvent(PlayerItemHeldEvent $event) : void{
		if(!$event->isCancelled()){
			$player = $event->getPlayer();
			$item = $event->getItem();
			if($item->getId() === Item::COMPASS && $item->getNamedTagEntry(RealisticCompass::TAG_PLUGIN) !== null){
				$this->plugin->getTask()->addPlayer($player);
			}else{
				$this->plugin->getTask()->removePlayer($player);
				RealisticCompass::sendReal($player);
			}
		}
	}
}
