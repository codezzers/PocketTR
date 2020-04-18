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

class MobEffectPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::MOB_EFFECT_PACKET;

	public const EVENT_ADD = 1;
	public const EVENT_MODIFY = 2;
	public const EVENT_REMOVE = 3;

	/** @var int */
	public $entityRuntimeId;
	/** @var int */
	public $eventId;
	/** @var int */
	public $effectId;
	/** @var int */
	public $amplifier = 0;
	/** @var bool */
	public $particles = true;
	/** @var int */
	public $duration = 0;

	protected function decodePayload(){
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->eventId = (\ord($this->get(1)));
		$this->effectId = $this->getVarInt();
		$this->amplifier = $this->getVarInt();
		$this->particles = (($this->get(1) !== "\x00"));
		$this->duration = $this->getVarInt();
	}

	protected function encodePayload(){
		$this->putEntityRuntimeId($this->entityRuntimeId);
		($this->buffer .= \chr($this->eventId));
		$this->putVarInt($this->effectId);
		$this->putVarInt($this->amplifier);
		($this->buffer .= ($this->particles ? "\x01" : "\x00"));
		$this->putVarInt($this->duration);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleMobEffect($this);
	}
}
