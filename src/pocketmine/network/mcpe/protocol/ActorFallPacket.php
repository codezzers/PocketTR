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

class ActorFallPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ACTOR_FALL_PACKET;

	/** @var int */
	public $entityRuntimeId;
	/** @var float */
	public $fallDistance;
	/** @var bool */
	public $isInVoid;

	protected function decodePayload(){
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->fallDistance = ((\unpack("g", $this->get(4))[1]));
		$this->isInVoid = (($this->get(1) !== "\x00"));
	}

	protected function encodePayload(){
		$this->putEntityRuntimeId($this->entityRuntimeId);
		($this->buffer .= (\pack("g", $this->fallDistance)));
		($this->buffer .= ($this->isInVoid ? "\x01" : "\x00"));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleActorFall($this);
	}
}
