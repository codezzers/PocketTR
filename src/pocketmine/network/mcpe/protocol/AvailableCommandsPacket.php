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
use pocketmine\network\mcpe\protocol\types\CommandData;
use pocketmine\network\mcpe\protocol\types\CommandEnum;
use pocketmine\network\mcpe\protocol\types\CommandEnumConstraint;
use pocketmine\network\mcpe\protocol\types\CommandParameter;
use pocketmine\utils\BinaryDataException;
use function array_search;
use function count;
use function dechex;

class AvailableCommandsPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::AVAILABLE_COMMANDS_PACKET;

	/**
	 * This flag is set on all types EXCEPT the POSTFIX type. Not completely sure what this is for, but it is required
	 * for the argtype to work correctly. VALID seems as good a name as any.
	 */
	public const ARG_FLAG_VALID = 0x100000;

	/**
	 * Basic parameter types. These must be combined with the ARG_FLAG_VALID constant.
	 * ARG_FLAG_VALID | (type const)
	 */
	public const ARG_TYPE_INT             = 0x01;
	public const ARG_TYPE_FLOAT           = 0x02;
	public const ARG_TYPE_VALUE           = 0x03;
	public const ARG_TYPE_WILDCARD_INT    = 0x04;
	public const ARG_TYPE_OPERATOR        = 0x05;
	public const ARG_TYPE_TARGET          = 0x06;

	public const ARG_TYPE_FILEPATH = 0x0e;

	public const ARG_TYPE_STRING   = 0x1d;

	public const ARG_TYPE_POSITION = 0x25;

	public const ARG_TYPE_MESSAGE  = 0x29;

	public const ARG_TYPE_RAWTEXT  = 0x2b;

	public const ARG_TYPE_JSON     = 0x2f;

	public const ARG_TYPE_COMMAND  = 0x36;

	/**
	 * Enums are a little different: they are composed as follows:
	 * ARG_FLAG_ENUM | ARG_FLAG_VALID | (enum index)
	 */
	public const ARG_FLAG_ENUM = 0x200000;

	/** This is used for /xp <level: int>L. It can only be applied to integer parameters. */
	public const ARG_FLAG_POSTFIX = 0x1000000;

	public const HARDCODED_ENUM_NAMES = [
		"CommandName" => true
	];

	/**
	 * @var CommandData[]
	 * List of command data, including name, description, alias indexes and parameters.
	 */
	public $commandData = [];

	/**
	 * @var CommandEnum[]
	 * List of enums which aren't directly referenced by any vanilla command.
	 * This is used for the `CommandName` enum, which is a magic enum used by the `command` argument type.
	 */
	public $hardcodedEnums = [];

	/**
	 * @var CommandEnum[]
	 * List of dynamic command enums, also referred to as "soft" enums. These can by dynamically updated mid-game
	 * without resending this packet.
	 */
	public $softEnums = [];

	/**
	 * @var CommandEnumConstraint[]
	 * List of constraints for enum members. Used to constrain gamerules that can bechanged in nocheats mode and more.
	 */
	public $enumConstraints = [];

	protected function decodePayload(){
		/** @var string[] $enumValues */
		$enumValues = [];
		for($i = 0, $enumValuesCount = $this->getUnsignedVarInt(); $i < $enumValuesCount; ++$i){
			$enumValues[] = $this->getString();
		}

		/** @var string[] $postfixes */
		$postfixes = [];
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$postfixes[] = $this->getString();
		}

		/** @var CommandEnum[] $enums */
		$enums = [];
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$enums[] = $enum = $this->getEnum($enumValues);
			if(isset(self::HARDCODED_ENUM_NAMES[$enum->enumName])){
				$this->hardcodedEnums[] = $enum;
			}
		}

		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$this->commandData[] = $this->getCommandData($enums, $postfixes);
		}

		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$this->softEnums[] = $this->getSoftEnum();
		}

		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$this->enumConstraints[] = $this->getEnumConstraint($enums, $enumValues);
		}
	}

	/**
	 * @param string[] $enumValueList
	 *
	 * @throws \UnexpectedValueException
	 * @throws BinaryDataException
	 */
	protected function getEnum(array $enumValueList) : CommandEnum{
		$retval = new CommandEnum();
		$retval->enumName = $this->getString();

		$listSize = count($enumValueList);

		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$index = $this->getEnumValueIndex($listSize);
			if(!isset($enumValueList[$index])){
				throw new \UnexpectedValueException("Invalid enum value index $index");
			}
			//Get the enum value from the initial pile of mess
			$retval->enumValues[] = $enumValueList[$index];
		}

		return $retval;
	}

	protected function getSoftEnum() : CommandEnum{
		$retval = new CommandEnum();
		$retval->enumName = $this->getString();

		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			//Get the enum value from the initial pile of mess
			$retval->enumValues[] = $this->getString();
		}

		return $retval;
	}

	/**
	 * @param int[]       $enumValueMap string enum name -> int index
	 */
	protected function putEnum(CommandEnum $enum, array $enumValueMap) : void{
		$this->putString($enum->enumName);

		$this->putUnsignedVarInt(count($enum->enumValues));
		$listSize = count($enumValueMap);
		foreach($enum->enumValues as $value){
			$index = $enumValueMap[$value] ?? -1;
			if($index === -1){
				throw new \InvalidStateException("Enum value '$value' not found");
			}
			$this->putEnumValueIndex($index, $listSize);
		}
	}

	protected function putSoftEnum(CommandEnum $enum) : void{
		$this->putString($enum->enumName);

		$this->putUnsignedVarInt(count($enum->enumValues));
		foreach($enum->enumValues as $value){
			$this->putString($value);
		}
	}

	/**
	 * @throws BinaryDataException
	 */
	protected function getEnumValueIndex(int $valueCount) : int{
		if($valueCount < 256){
			return (\ord($this->get(1)));
		}elseif($valueCount < 65536){
			return ((\unpack("v", $this->get(2))[1]));
		}else{
			return ((\unpack("V", $this->get(4))[1] << 32 >> 32));
		}
	}

	protected function putEnumValueIndex(int $index, int $valueCount) : void{
		if($valueCount < 256){
			($this->buffer .= \chr($index));
		}elseif($valueCount < 65536){
			($this->buffer .= (\pack("v", $index)));
		}else{
			($this->buffer .= (\pack("V", $index)));
		}
	}

	/**
	 * @param CommandEnum[] $enums
	 * @param string[]      $enumValues
	 */
	protected function getEnumConstraint(array $enums, array $enumValues) : CommandEnumConstraint{
		//wtf, what was wrong with an offset inside the enum? :(
		$valueIndex = ((\unpack("V", $this->get(4))[1] << 32 >> 32));
		if(!isset($enumValues[$valueIndex])){
			throw new \UnexpectedValueException("Enum constraint refers to unknown enum value index $valueIndex");
		}
		$enumIndex = ((\unpack("V", $this->get(4))[1] << 32 >> 32));
		if(!isset($enums[$enumIndex])){
			throw new \UnexpectedValueException("Enum constraint refers to unknown enum index $enumIndex");
		}
		$enum = $enums[$enumIndex];
		$valueOffset = array_search($enumValues[$valueIndex], $enum->enumValues, true);
		if($valueOffset === false){
			throw new \UnexpectedValueException("Value \"" . $enumValues[$valueIndex] . "\" does not belong to enum \"$enum->enumName\"");
		}

		$constraintIds = [];
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$constraintIds[] = (\ord($this->get(1)));
		}

		return new CommandEnumConstraint($enum, $valueOffset, $constraintIds);
	}

	/**
	 * @param int[]                 $enumIndexes string enum name -> int index
	 * @param int[]                 $enumValueIndexes string value -> int index
	 */
	protected function putEnumConstraint(CommandEnumConstraint $constraint, array $enumIndexes, array $enumValueIndexes) : void{
		($this->buffer .= (\pack("V", $enumValueIndexes[$constraint->getAffectedValue()])));
		($this->buffer .= (\pack("V", $enumIndexes[$constraint->getEnum()->enumName])));
		$this->putUnsignedVarInt(count($constraint->getConstraints()));
		foreach($constraint->getConstraints() as $v){
			($this->buffer .= \chr($v));
		}
	}

	/**
	 * @param CommandEnum[] $enums
	 * @param string[]      $postfixes
	 *
	 * @throws \UnexpectedValueException
	 * @throws BinaryDataException
	 */
	protected function getCommandData(array $enums, array $postfixes) : CommandData{
		$retval = new CommandData();
		$retval->commandName = $this->getString();
		$retval->commandDescription = $this->getString();
		$retval->flags = (\ord($this->get(1)));
		$retval->permission = (\ord($this->get(1)));
		$retval->aliases = $enums[((\unpack("V", $this->get(4))[1] << 32 >> 32))] ?? null;

		for($overloadIndex = 0, $overloadCount = $this->getUnsignedVarInt(); $overloadIndex < $overloadCount; ++$overloadIndex){
			$retval->overloads[$overloadIndex] = [];
			for($paramIndex = 0, $paramCount = $this->getUnsignedVarInt(); $paramIndex < $paramCount; ++$paramIndex){
				$parameter = new CommandParameter();
				$parameter->paramName = $this->getString();
				$parameter->paramType = ((\unpack("V", $this->get(4))[1] << 32 >> 32));
				$parameter->isOptional = (($this->get(1) !== "\x00"));
				$parameter->flags = (\ord($this->get(1)));

				if(($parameter->paramType & self::ARG_FLAG_ENUM) !== 0){
					$index = ($parameter->paramType & 0xffff);
					$parameter->enum = $enums[$index] ?? null;
					if($parameter->enum === null){
						throw new \UnexpectedValueException("deserializing $retval->commandName parameter $parameter->paramName: expected enum at $index, but got none");
					}
				}elseif(($parameter->paramType & self::ARG_FLAG_POSTFIX) !== 0){
					$index = ($parameter->paramType & 0xffff);
					$parameter->postfix = $postfixes[$index] ?? null;
					if($parameter->postfix === null){
						throw new \UnexpectedValueException("deserializing $retval->commandName parameter $parameter->paramName: expected postfix at $index, but got none");
					}
				}elseif(($parameter->paramType & self::ARG_FLAG_VALID) === 0){
					throw new \UnexpectedValueException("deserializing $retval->commandName parameter $parameter->paramName: Invalid parameter type 0x" . dechex($parameter->paramType));
				}

				$retval->overloads[$overloadIndex][$paramIndex] = $parameter;
			}
		}

		return $retval;
	}

	/**
	 * @param int[]       $enumIndexes string enum name -> int index
	 * @param int[]       $postfixIndexes
	 */
	protected function putCommandData(CommandData $data, array $enumIndexes, array $postfixIndexes) : void{
		$this->putString($data->commandName);
		$this->putString($data->commandDescription);
		($this->buffer .= \chr($data->flags));
		($this->buffer .= \chr($data->permission));

		if($data->aliases !== null){
			($this->buffer .= (\pack("V", $enumIndexes[$data->aliases->enumName] ?? -1)));
		}else{
			($this->buffer .= (\pack("V", -1)));
		}

		$this->putUnsignedVarInt(count($data->overloads));
		foreach($data->overloads as $overload){
			/** @var CommandParameter[] $overload */
			$this->putUnsignedVarInt(count($overload));
			foreach($overload as $parameter){
				$this->putString($parameter->paramName);

				if($parameter->enum !== null){
					$type = self::ARG_FLAG_ENUM | self::ARG_FLAG_VALID | ($enumIndexes[$parameter->enum->enumName] ?? -1);
				}elseif($parameter->postfix !== null){
					$key = $postfixIndexes[$parameter->postfix] ?? -1;
					if($key === -1){
						throw new \InvalidStateException("Postfix '$parameter->postfix' not in postfixes array");
					}
					$type = self::ARG_FLAG_POSTFIX | $key;
				}else{
					$type = $parameter->paramType;
				}

				($this->buffer .= (\pack("V", $type)));
				($this->buffer .= ($parameter->isOptional ? "\x01" : "\x00"));
				($this->buffer .= \chr($parameter->flags));
			}
		}
	}

	/**
	 * @param string[] $postfixes
	 * @phpstan-param array<int, string> $postfixes
	 */
	private function argTypeToString(int $argtype, array $postfixes) : string{
		if(($argtype & self::ARG_FLAG_VALID) !== 0){
			if(($argtype & self::ARG_FLAG_ENUM) !== 0){
				return "stringenum (" . ($argtype & 0xffff) . ")";
			}

			switch($argtype & 0xffff){
				case self::ARG_TYPE_INT:
					return "int";
				case self::ARG_TYPE_FLOAT:
					return "float";
				case self::ARG_TYPE_VALUE:
					return "mixed";
				case self::ARG_TYPE_TARGET:
					return "target";
				case self::ARG_TYPE_STRING:
					return "string";
				case self::ARG_TYPE_POSITION:
					return "xyz";
				case self::ARG_TYPE_MESSAGE:
					return "message";
				case self::ARG_TYPE_RAWTEXT:
					return "text";
				case self::ARG_TYPE_JSON:
					return "json";
				case self::ARG_TYPE_COMMAND:
					return "command";
			}
		}elseif(($argtype & self::ARG_FLAG_POSTFIX) !== 0){
			$postfix = $postfixes[$argtype & 0xffff];

			return "int (postfix $postfix)";
		}else{
			throw new \UnexpectedValueException("Unknown arg type 0x" . dechex($argtype));
		}

		return "unknown ($argtype)";
	}

	protected function encodePayload(){
		/** @var int[] $enumValueIndexes */
		$enumValueIndexes = [];
		/** @var int[] $postfixIndexes */
		$postfixIndexes = [];
		/** @var int[] $enumIndexes */
		$enumIndexes = [];
		/** @var CommandEnum[] $enums */
		$enums = [];

		$addEnumFn = static function(CommandEnum $enum) use (&$enums, &$enumIndexes, &$enumValueIndexes) : void{
			if(!isset($enumIndexes[$enum->enumName])){
				$enums[$enumIndexes[$enum->enumName] = count($enumIndexes)] = $enum;
			}
			foreach($enum->enumValues as $str){
				$enumValueIndexes[$str] = $enumValueIndexes[$str] ?? count($enumValueIndexes);
			}
		};
		foreach($this->hardcodedEnums as $enum){
			$addEnumFn($enum);
		}
		foreach($this->commandData as $commandData){
			if($commandData->aliases !== null){
				$addEnumFn($commandData->aliases);
			}
			/** @var CommandParameter[] $overload */
			foreach($commandData->overloads as $overload){
				/** @var CommandParameter $parameter */
				foreach($overload as $parameter){
					if($parameter->enum !== null){
						$addEnumFn($parameter->enum);
					}

					if($parameter->postfix !== null){
						$postfixIndexes[$parameter->postfix] = $postfixIndexes[$parameter->postfix] ?? count($postfixIndexes);
					}
				}
			}
		}

		$this->putUnsignedVarInt(count($enumValueIndexes));
		foreach($enumValueIndexes as $enumValue => $index){
			$this->putString((string) $enumValue); //stupid PHP key casting D:
		}

		$this->putUnsignedVarInt(count($postfixIndexes));
		foreach($postfixIndexes as $postfix => $index){
			$this->putString((string) $postfix); //stupid PHP key casting D:
		}

		$this->putUnsignedVarInt(count($enums));
		foreach($enums as $enum){
			$this->putEnum($enum, $enumValueIndexes);
		}

		$this->putUnsignedVarInt(count($this->commandData));
		foreach($this->commandData as $data){
			$this->putCommandData($data, $enumIndexes, $postfixIndexes);
		}

		$this->putUnsignedVarInt(count($this->softEnums));
		foreach($this->softEnums as $enum){
			$this->putSoftEnum($enum);
		}

		$this->putUnsignedVarInt(count($this->enumConstraints));
		foreach($this->enumConstraints as $constraint){
			$this->putEnumConstraint($constraint, $enumIndexes, $enumValueIndexes);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAvailableCommands($this);
	}
}
