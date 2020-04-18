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
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\types\RuntimeBlockMapping;
use function count;
use function file_get_contents;
use function json_decode;
use const pocketmine\RESOURCE_PATH;

class StartGamePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::START_GAME_PACKET;

	/** @var string|null */
	private static $blockTableCache = null;
	/** @var string|null */
	private static $itemTableCache = null;

	/** @var int */
	public $entityUniqueId;
	/** @var int */
	public $entityRuntimeId;
	/** @var int */
	public $playerGamemode;

	/** @var Vector3 */
	public $playerPosition;

	/** @var float */
	public $pitch;
	/** @var float */
	public $yaw;

	/** @var int */
	public $seed;
	/** @var int */
	public $dimension;
	/** @var int */
	public $generator = 1; //default infinite - 0 old, 1 infinite, 2 flat
	/** @var int */
	public $worldGamemode;
	/** @var int */
	public $difficulty;
	/** @var int */
	public $spawnX;
	/** @var int */
	public $spawnY;
	/** @var int */
	public $spawnZ;
	/** @var bool */
	public $hasAchievementsDisabled = true;
	/** @var int */
	public $time = -1;
	/** @var int */
	public $eduEditionOffer = 0;
	/** @var bool */
	public $hasEduFeaturesEnabled = false;
	/** @var float */
	public $rainLevel;
	/** @var float */
	public $lightningLevel;
	/** @var bool */
	public $hasConfirmedPlatformLockedContent = false;
	/** @var bool */
	public $isMultiplayerGame = true;
	/** @var bool */
	public $hasLANBroadcast = true;
	/** @var int */
	public $xboxLiveBroadcastMode = 0; //TODO: find values
	/** @var int */
	public $platformBroadcastMode = 0;
	/** @var bool */
	public $commandsEnabled;
	/** @var bool */
	public $isTexturePacksRequired = true;
	/**
	 * @var mixed[][]
	 * @phpstan-var array<string, array{0: int, 1: bool|int|float}>
	 */
	public $gameRules = [ //TODO: implement this
		"naturalregeneration" => [1, false] //Hack for client side regeneration
	];
	/** @var bool */
	public $hasBonusChestEnabled = false;
	/** @var bool */
	public $hasStartWithMapEnabled = false;
	/** @var int */
	public $defaultPlayerPermission = PlayerPermissions::MEMBER; //TODO

	/** @var int */
	public $serverChunkTickRadius = 4; //TODO (leave as default for now)

	/** @var bool */
	public $hasLockedBehaviorPack = false;
	/** @var bool */
	public $hasLockedResourcePack = false;
	/** @var bool */
	public $isFromLockedWorldTemplate = false;
	/** @var bool */
	public $useMsaGamertagsOnly = false;
	/** @var bool */
	public $isFromWorldTemplate = false;
	/** @var bool */
	public $isWorldTemplateOptionLocked = false;
	/** @var bool */
	public $onlySpawnV1Villagers = false;

	/** @var string */
	public $vanillaVersion = ProtocolInfo::MINECRAFT_VERSION_NETWORK;
	/** @var string */
	public $levelId = ""; //base64 string, usually the same as world folder name in vanilla
	/** @var string */
	public $worldName;
	/** @var string */
	public $premiumWorldTemplateId = "";
	/** @var bool */
	public $isTrial = false;
	/** @var bool */
	public $isMovementServerAuthoritative = false;
	/** @var int */
	public $currentTick = 0; //only used if isTrial is true
	/** @var int */
	public $enchantmentSeed = 0;
	/** @var string */
	public $multiplayerCorrelationId = ""; //TODO: this should be filled with a UUID of some sort

	/** @var ListTag|null */
	public $blockTable = null;
	/**
	 * @var int[]|null string (name) => int16 (legacyID)
	 * @phpstan-var array<string, int>|null
	 */
	public $itemTable = null;

	protected function decodePayload(){
		$this->entityUniqueId = $this->getEntityUniqueId();
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->playerGamemode = $this->getVarInt();

		$this->playerPosition = $this->getVector3();

		$this->pitch = ((\unpack("g", $this->get(4))[1]));
		$this->yaw = ((\unpack("g", $this->get(4))[1]));

		//Level settings
		$this->seed = $this->getVarInt();
		$this->dimension = $this->getVarInt();
		$this->generator = $this->getVarInt();
		$this->worldGamemode = $this->getVarInt();
		$this->difficulty = $this->getVarInt();
		$this->getBlockPosition($this->spawnX, $this->spawnY, $this->spawnZ);
		$this->hasAchievementsDisabled = (($this->get(1) !== "\x00"));
		$this->time = $this->getVarInt();
		$this->eduEditionOffer = $this->getVarInt();
		$this->hasEduFeaturesEnabled = (($this->get(1) !== "\x00"));
		$this->rainLevel = ((\unpack("g", $this->get(4))[1]));
		$this->lightningLevel = ((\unpack("g", $this->get(4))[1]));
		$this->hasConfirmedPlatformLockedContent = (($this->get(1) !== "\x00"));
		$this->isMultiplayerGame = (($this->get(1) !== "\x00"));
		$this->hasLANBroadcast = (($this->get(1) !== "\x00"));
		$this->xboxLiveBroadcastMode = $this->getVarInt();
		$this->platformBroadcastMode = $this->getVarInt();
		$this->commandsEnabled = (($this->get(1) !== "\x00"));
		$this->isTexturePacksRequired = (($this->get(1) !== "\x00"));
		$this->gameRules = $this->getGameRules();
		$this->hasBonusChestEnabled = (($this->get(1) !== "\x00"));
		$this->hasStartWithMapEnabled = (($this->get(1) !== "\x00"));
		$this->defaultPlayerPermission = $this->getVarInt();
		$this->serverChunkTickRadius = ((\unpack("V", $this->get(4))[1] << 32 >> 32));
		$this->hasLockedBehaviorPack = (($this->get(1) !== "\x00"));
		$this->hasLockedResourcePack = (($this->get(1) !== "\x00"));
		$this->isFromLockedWorldTemplate = (($this->get(1) !== "\x00"));
		$this->useMsaGamertagsOnly = (($this->get(1) !== "\x00"));
		$this->isFromWorldTemplate = (($this->get(1) !== "\x00"));
		$this->isWorldTemplateOptionLocked = (($this->get(1) !== "\x00"));
		$this->onlySpawnV1Villagers = (($this->get(1) !== "\x00"));

		$this->vanillaVersion = $this->getString();
		$this->levelId = $this->getString();
		$this->worldName = $this->getString();
		$this->premiumWorldTemplateId = $this->getString();
		$this->isTrial = (($this->get(1) !== "\x00"));
		$this->isMovementServerAuthoritative = (($this->get(1) !== "\x00"));
		$this->currentTick = (Binary::readLLong($this->get(8)));

		$this->enchantmentSeed = $this->getVarInt();

		$blockTable = (new NetworkLittleEndianNBTStream())->read($this->buffer, false, $this->offset, 512);
		if(!($blockTable instanceof ListTag)){
			throw new \UnexpectedValueException("Wrong block table root NBT tag type");
		}
		$this->blockTable = $blockTable;

		$this->itemTable = [];
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$id = $this->getString();
			$legacyId = ((\unpack("v", $this->get(2))[1] << 48 >> 48));

			$this->itemTable[$id] = $legacyId;
		}

		$this->multiplayerCorrelationId = $this->getString();
	}

	protected function encodePayload(){
		$this->putEntityUniqueId($this->entityUniqueId);
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putVarInt($this->playerGamemode);

		$this->putVector3($this->playerPosition);

		($this->buffer .= (\pack("g", $this->pitch)));
		($this->buffer .= (\pack("g", $this->yaw)));

		//Level settings
		$this->putVarInt($this->seed);
		$this->putVarInt($this->dimension);
		$this->putVarInt($this->generator);
		$this->putVarInt($this->worldGamemode);
		$this->putVarInt($this->difficulty);
		$this->putBlockPosition($this->spawnX, $this->spawnY, $this->spawnZ);
		($this->buffer .= ($this->hasAchievementsDisabled ? "\x01" : "\x00"));
		$this->putVarInt($this->time);
		$this->putVarInt($this->eduEditionOffer);
		($this->buffer .= ($this->hasEduFeaturesEnabled ? "\x01" : "\x00"));
		($this->buffer .= (\pack("g", $this->rainLevel)));
		($this->buffer .= (\pack("g", $this->lightningLevel)));
		($this->buffer .= ($this->hasConfirmedPlatformLockedContent ? "\x01" : "\x00"));
		($this->buffer .= ($this->isMultiplayerGame ? "\x01" : "\x00"));
		($this->buffer .= ($this->hasLANBroadcast ? "\x01" : "\x00"));
		$this->putVarInt($this->xboxLiveBroadcastMode);
		$this->putVarInt($this->platformBroadcastMode);
		($this->buffer .= ($this->commandsEnabled ? "\x01" : "\x00"));
		($this->buffer .= ($this->isTexturePacksRequired ? "\x01" : "\x00"));
		$this->putGameRules($this->gameRules);
		($this->buffer .= ($this->hasBonusChestEnabled ? "\x01" : "\x00"));
		($this->buffer .= ($this->hasStartWithMapEnabled ? "\x01" : "\x00"));
		$this->putVarInt($this->defaultPlayerPermission);
		($this->buffer .= (\pack("V", $this->serverChunkTickRadius)));
		($this->buffer .= ($this->hasLockedBehaviorPack ? "\x01" : "\x00"));
		($this->buffer .= ($this->hasLockedResourcePack ? "\x01" : "\x00"));
		($this->buffer .= ($this->isFromLockedWorldTemplate ? "\x01" : "\x00"));
		($this->buffer .= ($this->useMsaGamertagsOnly ? "\x01" : "\x00"));
		($this->buffer .= ($this->isFromWorldTemplate ? "\x01" : "\x00"));
		($this->buffer .= ($this->isWorldTemplateOptionLocked ? "\x01" : "\x00"));
		($this->buffer .= ($this->onlySpawnV1Villagers ? "\x01" : "\x00"));

		$this->putString($this->vanillaVersion);
		$this->putString($this->levelId);
		$this->putString($this->worldName);
		$this->putString($this->premiumWorldTemplateId);
		($this->buffer .= ($this->isTrial ? "\x01" : "\x00"));
		($this->buffer .= ($this->isMovementServerAuthoritative ? "\x01" : "\x00"));
		($this->buffer .= (\pack("VV", $this->currentTick & 0xFFFFFFFF, $this->currentTick >> 32)));

		$this->putVarInt($this->enchantmentSeed);

		if($this->blockTable === null){
			if(self::$blockTableCache === null){
				//this is a really nasty hack, but it'll do for now
				self::$blockTableCache = (new NetworkLittleEndianNBTStream())->write(new ListTag("", RuntimeBlockMapping::getBedrockKnownStates()));
			}
			($this->buffer .= self::$blockTableCache);
		}else{
			($this->buffer .= (new NetworkLittleEndianNBTStream())->write($this->blockTable));
		}
		if($this->itemTable === null){
			if(self::$itemTableCache === null){
				self::$itemTableCache = self::serializeItemTable(json_decode(file_get_contents(RESOURCE_PATH . '/vanilla/item_id_map.json'), true));
			}
			($this->buffer .= self::$itemTableCache);
		}else{
			($this->buffer .= self::serializeItemTable($this->itemTable));
		}

		$this->putString($this->multiplayerCorrelationId);
	}

	/**
	 * @param int[] $table
	 * @phpstan-param array<string, int> $table
	 */
	private static function serializeItemTable(array $table) : string{
		$stream = new NetworkBinaryStream();
		$stream->putUnsignedVarInt(count($table));
		foreach($table as $name => $legacyId){
			$stream->putString($name);
			$stream->putLShort($legacyId);
		}
		return $stream->getBuffer();
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleStartGame($this);
	}
}
