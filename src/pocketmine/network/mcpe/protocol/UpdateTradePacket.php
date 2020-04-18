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
use pocketmine\network\mcpe\protocol\types\WindowTypes;

class UpdateTradePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::UPDATE_TRADE_PACKET;

	//TODO: find fields

	/** @var int */
	public $windowId;
	/** @var int */
	public $windowType = WindowTypes::TRADING; //Mojang hardcoded this -_-
	/** @var int */
	public $windowSlotCount = 0; //useless, seems to be part of a standard container header
	/** @var int */
	public $tradeTier;
	/** @var int */
	public $traderEid;
	/** @var int */
	public $playerEid;
	/** @var string */
	public $displayName;
	/** @var bool */
	public $isV2Trading;
	/** @var bool */
	public $isWilling;
	/** @var string */
	public $offers;

	protected function decodePayload(){
		$this->windowId = (\ord($this->get(1)));
		$this->windowType = (\ord($this->get(1)));
		$this->windowSlotCount = $this->getVarInt();
		$this->tradeTier = $this->getVarInt();
		$this->traderEid = $this->getEntityUniqueId();
		$this->playerEid = $this->getEntityUniqueId();
		$this->displayName = $this->getString();
		$this->isV2Trading = (($this->get(1) !== "\x00"));
		$this->isWilling = (($this->get(1) !== "\x00"));
		$this->offers = $this->getRemaining();
	}

	protected function encodePayload(){
		($this->buffer .= \chr($this->windowId));
		($this->buffer .= \chr($this->windowType));
		$this->putVarInt($this->windowSlotCount);
		$this->putVarInt($this->tradeTier);
		$this->putEntityUniqueId($this->traderEid);
		$this->putEntityUniqueId($this->playerEid);
		$this->putString($this->displayName);
		($this->buffer .= ($this->isV2Trading ? "\x01" : "\x00"));
		($this->buffer .= ($this->isWilling ? "\x01" : "\x00"));
		($this->buffer .= $this->offers);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUpdateTrade($this);
	}
}
