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

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;

/**
 * Useless leftover from a 1.9 refactor, does nothing
 */
class LevelSoundEventPacketV2 extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::LEVEL_SOUND_EVENT_PACKET_V2;

	/** @var int */
	public $sound;
	/** @var Vector3 */
	public $position;
	/** @var int */
	public $extraData = -1;
	/** @var string */
	public $entityType = ":"; //???
	/** @var bool */
	public $isBabyMob = false; //...
	/** @var bool */
	public $disableRelativeVolume = false;

	protected function decodePayload(){
		$this->sound = (\ord($this->get(1)));
		$this->position = $this->getVector3();
		$this->extraData = $this->getVarInt();
		$this->entityType = $this->getString();
		$this->isBabyMob = (($this->get(1) !== "\x00"));
		$this->disableRelativeVolume = (($this->get(1) !== "\x00"));
	}

	protected function encodePayload(){
		($this->buffer .= \chr($this->sound));
		$this->putVector3($this->position);
		$this->putVarInt($this->extraData);
		$this->putString($this->entityType);
		($this->buffer .= ($this->isBabyMob ? "\x01" : "\x00"));
		($this->buffer .= ($this->disableRelativeVolume ? "\x01" : "\x00"));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleLevelSoundEventPacketV2($this);
	}
}
