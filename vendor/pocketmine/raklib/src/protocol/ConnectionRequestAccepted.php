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

use raklib\RakLib;
use raklib\utils\InternetAddress;
use function strlen;

class ConnectionRequestAccepted extends Packet{
	public static $ID = MessageIdentifiers::ID_CONNECTION_REQUEST_ACCEPTED;

	/** @var InternetAddress */
	public $address;
	/** @var InternetAddress[] */
	public $systemAddresses = [];

	/** @var int */
	public $sendPingTime;
	/** @var int */
	public $sendPongTime;

	public function __construct(string $buffer = "", int $offset = 0){
		parent::__construct($buffer, $offset);
		$this->systemAddresses[] = new InternetAddress("127.0.0.1", 0, 4);
	}

	protected function encodePayload() : void{
		$this->putAddress($this->address);
		($this->buffer .= (\pack("n", 0)));

		$dummy = new InternetAddress("0.0.0.0", 0, 4);
		for($i = 0; $i < RakLib::$SYSTEM_ADDRESS_COUNT; ++$i){
			$this->putAddress($this->systemAddresses[$i] ?? $dummy);
		}

		($this->buffer .= (\pack("NN", $this->sendPingTime >> 32, $this->sendPingTime & 0xFFFFFFFF)));
		($this->buffer .= (\pack("NN", $this->sendPongTime >> 32, $this->sendPongTime & 0xFFFFFFFF)));
	}

	protected function decodePayload() : void{
		$this->address = $this->getAddress();
		((\unpack("n", $this->get(2))[1])); //TODO: check this

		$len = strlen($this->buffer);
		$dummy = new InternetAddress("0.0.0.0", 0, 4);

		for($i = 0; $i < RakLib::$SYSTEM_ADDRESS_COUNT; ++$i){
			$this->systemAddresses[$i] = $this->offset + 16 < $len ? $this->getAddress() : $dummy; //HACK: avoids trying to read too many addresses on bad data
		}

		$this->sendPingTime = (Binary::readLong($this->get(8)));
		$this->sendPongTime = (Binary::readLong($this->get(8)));
	}
}
