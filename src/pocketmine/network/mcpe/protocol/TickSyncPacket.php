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

class TickSyncPacket extends DataPacket/* implements ClientboundPacket, ServerboundPacket*/{
	public const NETWORK_ID = ProtocolInfo::TICK_SYNC_PACKET;

	/** @var int */
	private $clientSendTime;
	/** @var int */
	private $serverReceiveTime;

	public static function request(int $clientTime) : self{
		$result = new self;
		$result->clientSendTime = $clientTime;
		$result->serverReceiveTime = 0; //useless
		return $result;
	}

	public static function response(int $clientSendTime, int $serverReceiveTime) : self{
		$result = new self;
		$result->clientSendTime = $clientSendTime;
		$result->serverReceiveTime = $serverReceiveTime;
		return $result;
	}

	public function getClientSendTime() : int{
		return $this->clientSendTime;
	}

	public function getServerReceiveTime() : int{
		return $this->serverReceiveTime;
	}

	protected function decodePayload() : void{
		$this->clientSendTime = (Binary::readLLong($this->get(8)));
		$this->serverReceiveTime = (Binary::readLLong($this->get(8)));
	}

	protected function encodePayload() : void{
		($this->buffer .= (\pack("VV", $this->clientSendTime & 0xFFFFFFFF, $this->clientSendTime >> 32)));
		($this->buffer .= (\pack("VV", $this->serverReceiveTime & 0xFFFFFFFF, $this->serverReceiveTime >> 32)));
	}

	public function handle(NetworkSession $handler) : bool{
		return $handler->handleTickSync($this);
	}
}
