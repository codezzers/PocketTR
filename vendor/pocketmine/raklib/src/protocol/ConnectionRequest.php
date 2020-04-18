<?php

/*
 * RakLib network library
 *
 *
 * This project is not affiliated with Jenkins Software LLC nor RakNet.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 */

declare(strict_types=1);

namespace raklib\protocol;

use pocketmine\utils\Binary;

class ConnectionRequest extends Packet{
	public static $ID = MessageIdentifiers::ID_CONNECTION_REQUEST;

	/** @var int */
	public $clientID;
	/** @var int */
	public $sendPingTime;
	/** @var bool */
	public $useSecurity = false;

	protected function encodePayload() : void{
		($this->buffer .= (\pack("NN", $this->clientID >> 32, $this->clientID & 0xFFFFFFFF)));
		($this->buffer .= (\pack("NN", $this->sendPingTime >> 32, $this->sendPingTime & 0xFFFFFFFF)));
		($this->buffer .= \chr($this->useSecurity ? 1 : 0));
	}

	protected function decodePayload() : void{
		$this->clientID = (Binary::readLong($this->get(8)));
		$this->sendPingTime = (Binary::readLong($this->get(8)));
		$this->useSecurity = (\ord($this->get(1))) !== 0;
	}
}
