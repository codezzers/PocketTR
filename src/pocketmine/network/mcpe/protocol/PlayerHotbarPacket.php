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
use pocketmine\network\mcpe\protocol\types\ContainerIds;

/**
 * One of the most useless packets.
 */
class PlayerHotbarPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_HOTBAR_PACKET;

	/** @var int */
	public $selectedHotbarSlot;
	/** @var int */
	public $windowId = ContainerIds::INVENTORY;
	/** @var bool */
	public $selectHotbarSlot = true;

	protected function decodePayload(){
		$this->selectedHotbarSlot = $this->getUnsignedVarInt();
		$this->windowId = (\ord($this->get(1)));
		$this->selectHotbarSlot = (($this->get(1) !== "\x00"));
	}

	protected function encodePayload(){
		$this->putUnsignedVarInt($this->selectedHotbarSlot);
		($this->buffer .= \chr($this->windowId));
		($this->buffer .= ($this->selectHotbarSlot ? "\x01" : "\x00"));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerHotbar($this);
	}
}
