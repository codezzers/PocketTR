<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\Player;
use pocketmine\entity\projectile\Hook;

class FishingRod extends Durable{

	public static $fishing = [];

	public function __construct(int $meta = 0){
		parent::__construct(self::FISHING_ROD, $meta, "Olta");
	}

	public function getMaxStackSize(): int{
		return 1;
	}

	public function getCooldownTicks(): int{
		return 5;
	}

	public function getMaxDurability(): int{
		return 355;
	}

	public function onClickAir(Player $player, Vector3 $directionVector): bool{
		if(!$player->hasItemCooldown($this)){
			$player->resetItemCooldown($this);

			if($this->getFishingHook($player) === NULL){
				$motion = $player->getDirectionVector();
				$motion = $motion->multiply(0.4);
				$nbt = Entity::createBaseNBT($player->add(0, $player->getEyeHeight(), 0), $motion);
				$hook = Entity::createEntity("FishingHook", $player->level, $nbt, $player);
				$hook->spawnToAll();
			}else{
				$hook = $this->getFishingHook($player);
				$hook->flagForDespawn();
				$this->setFishingHook(NULL, $player);
			}
			$player->broadcastEntityEvent(AnimatePacket::ACTION_SWING_ARM);
			return true;
		}
		return false;
	}

	public function getProjectileEntityType(): string{
		return "Hook";
	}

	public function getThrowForce(): float{
		return 0.9;
	}

	public static function getFishingHook(Player $player): ?Hook{
		return self::$fishing[$player->getName()] ?? null;
	}

	public static function setFishingHook(?Hook $fish, Player $player){
		self::$fishing[$player->getName()] = $fish;
	}
}
