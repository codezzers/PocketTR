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

use pocketmine\utils\BinaryDataException;
use pocketmine\utils\BinaryStream;
use raklib\utils\InternetAddress;
use function assert;
use function count;
use function explode;
use function inet_ntop;
use function inet_pton;
use function strlen;
use const AF_INET6;

use pocketmine\utils\Binary;

abstract class Packet extends BinaryStream{
	/** @var int */
	public static $ID = -1;

	/** @var float|null */
	public $sendTime;

	protected function getString() : string{
		return $this->get(((\unpack("n", $this->get(2))[1])));
	}

	protected function getAddress() : InternetAddress{
		$version = (\ord($this->get(1)));
		if($version === 4){
			$addr = ((~(\ord($this->get(1)))) & 0xff) . "." . ((~(\ord($this->get(1)))) & 0xff) . "." . ((~(\ord($this->get(1)))) & 0xff) . "." . ((~(\ord($this->get(1)))) & 0xff);
			$port = ((\unpack("n", $this->get(2))[1]));
			return new InternetAddress($addr, $port, $version);
		}elseif($version === 6){
			//http://man7.org/1/man-pages/man7/ipv6.7.html
			(\unpack("v", $this->get(2))[1]); //Family, AF_INET6
			$port = ((\unpack("n", $this->get(2))[1]));
			((\unpack("N", $this->get(4))[1] << 32 >> 32)); //flow info
			$addr = inet_ntop($this->get(16));
			if($addr === false){
				throw new BinaryDataException("Failed to parse IPv6 address");
			}
			((\unpack("N", $this->get(4))[1] << 32 >> 32)); //scope ID
			return new InternetAddress($addr, $port, $version);
		}else{
			throw new \UnexpectedValueException("Unknown IP address version $version");
		}
	}

	protected function putString(string $v) : void{
		($this->buffer .= (\pack("n", strlen($v))));
		($this->buffer .= $v);
	}

	protected function putAddress(InternetAddress $address) : void{
		($this->buffer .= \chr($address->version));
		if($address->version === 4){
			$parts = explode(".", $address->ip);
			assert(count($parts) === 4, "Wrong number of parts in IPv4 IP, expected 4, got " . count($parts));
			foreach($parts as $b){
				($this->buffer .= \chr((~((int) $b)) & 0xff));
			}
			($this->buffer .= (\pack("n", $address->port)));
		}elseif($address->version === 6){
			($this->buffer .= (\pack("v", AF_INET6)));
			($this->buffer .= (\pack("n", $address->port)));
			($this->buffer .= (\pack("N", 0)));
			$rawIp = inet_pton($address->ip);
			if($rawIp === false){
				throw new \InvalidArgumentException("Invalid IPv6 address could not be encoded");
			}
			($this->buffer .= $rawIp);
			($this->buffer .= (\pack("N", 0)));
		}else{
			throw new \InvalidArgumentException("IP version $address->version is not supported");
		}
	}

	public function encode() : void{
		$this->reset();
		$this->encodeHeader();
		$this->encodePayload();
	}

	protected function encodeHeader() : void{
		($this->buffer .= \chr(static::$ID));
	}

	abstract protected function encodePayload() : void;

	public function decode() : void{
		$this->offset = 0;
		$this->decodeHeader();
		$this->decodePayload();
	}

	protected function decodeHeader() : void{
		(\ord($this->get(1))); //PID
	}

	abstract protected function decodePayload() : void;

	/**
	 * @return $this
	 */
	public function clean(){
		$this->buffer = "";
		$this->offset = 0;
		$this->sendTime = null;

		return $this;
	}
}
