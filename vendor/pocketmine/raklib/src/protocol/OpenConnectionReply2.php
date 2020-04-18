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

use raklib\utils\InternetAddress;

class OpenConnectionReply2 extends OfflineMessage{
	public static $ID = MessageIdentifiers::ID_OPEN_CONNECTION_REPLY_2;

	/** @var int */
	public $serverID;
	/** @var InternetAddress */
	public $clientAddress;
	/** @var int */
	public $mtuSize;
	/** @var bool */
	public $serverSecurity = false;

	protected function encodePayload() : void{
		$this->writeMagic();
		($this->buffer .= (\pack("NN", $this->serverID >> 32, $this->serverID & 0xFFFFFFFF)));
		$this->putAddress($this->clientAddress);
		($this->buffer .= (\pack("n", $this->mtuSize)));
		($this->buffer .= \chr($this->serverSecurity ? 1 : 0));
	}

	protected function decodePayload() : void{
		$this->readMagic();
		$this->serverID = (Binary::readLong($this->get(8)));
		$this->clientAddress = $this->getAddress();
		$this->mtuSize = ((\unpack("n", $this->get(2))[1]));
		$this->serverSecurity = (\ord($this->get(1))) !== 0;
	}
}
