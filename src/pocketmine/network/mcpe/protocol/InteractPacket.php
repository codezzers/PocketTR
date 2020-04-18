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

namespace pocketmine\network\mcpe\protocol;

use pocketmine\utils\Binary;

use pocketmine\network\mcpe\NetworkSession;

class InteractPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::INTERACT_PACKET;

	public const ACTION_LEAVE_VEHICLE = 3;
	public const ACTION_MOUSEOVER = 4;
	public const ACTION_OPEN_NPC = 5;
	public const ACTION_OPEN_INVENTORY = 6;

	/** @var int */
	public $action;
	/** @var int */
	public $target;

	/** @var float */
	public $x;
	/** @var float */
	public $y;
	/** @var float */
	public $z;

	protected function decodePayload(){
		$this->action = (\ord($this->get(1)));
		$this->target = $this->getEntityRuntimeId();

		if($this->action === self::ACTION_MOUSEOVER){
			//TODO: should this be a vector3?
			$this->x = ((\unpack("g", $this->get(4))[1]));
			$this->y = ((\unpack("g", $this->get(4))[1]));
			$this->z = ((\unpack("g", $this->get(4))[1]));
		}
	}

	protected function encodePayload(){
		($this->buffer .= \chr($this->action));
		$this->putEntityRuntimeId($this->target);

		if($this->action === self::ACTION_MOUSEOVER){
			($this->buffer .= (\pack("g", $this->x)));
			($this->buffer .= (\pack("g", $this->y)));
			($this->buffer .= (\pack("g", $this->z)));
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleInteract($this);
	}
}
