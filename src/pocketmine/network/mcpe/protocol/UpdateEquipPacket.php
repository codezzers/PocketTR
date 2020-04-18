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

class UpdateEquipPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::UPDATE_EQUIP_PACKET;

	/** @var int */
	public $windowId;
	/** @var int */
	public $windowType;
	/** @var int */
	public $windowSlotCount; //useless, seems to be part of a standard container header
	/** @var int */
	public $entityUniqueId;
	/** @var string */
	public $namedtag;

	protected function decodePayload(){
		$this->windowId = (\ord($this->get(1)));
		$this->windowType = (\ord($this->get(1)));
		$this->windowSlotCount = $this->getVarInt();
		$this->entityUniqueId = $this->getEntityUniqueId();
		$this->namedtag = $this->getRemaining();
	}

	protected function encodePayload(){
		($this->buffer .= \chr($this->windowId));
		($this->buffer .= \chr($this->windowType));
		$this->putVarInt($this->windowSlotCount);
		$this->putEntityUniqueId($this->entityUniqueId);
		($this->buffer .= $this->namedtag);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUpdateEquip($this);
	}
}
