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

namespace pocketmine\network\mcpe;

use pocketmine\utils\Binary;

use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\mcpe\protocol\types\CommandOriginData;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\network\mcpe\protocol\types\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\SkinData;
use pocketmine\network\mcpe\protocol\types\SkinImage;
use pocketmine\network\mcpe\protocol\types\StructureEditorData;
use pocketmine\network\mcpe\protocol\types\StructureSettings;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\UUID;
use function count;
use function strlen;

class NetworkBinaryStream extends BinaryStream{

	private const DAMAGE_TAG = "Damage"; //TAG_Int
	private const DAMAGE_TAG_CONFLICT_RESOLUTION = "___Damage_ProtocolCollisionResolution___";

	public function getString() : string{
		return $this->get($this->getUnsignedVarInt());
	}

	public function putString(string $v) : void{
		$this->putUnsignedVarInt(strlen($v));
		($this->buffer .= $v);
	}

	public function getUUID() : UUID{
		//This is actually two little-endian longs: UUID Most followed by UUID Least
		$part1 = ((\unpack("V", $this->get(4))[1] << 32 >> 32));
		$part0 = ((\unpack("V", $this->get(4))[1] << 32 >> 32));
		$part3 = ((\unpack("V", $this->get(4))[1] << 32 >> 32));
		$part2 = ((\unpack("V", $this->get(4))[1] << 32 >> 32));

		return new UUID($part0, $part1, $part2, $part3);
	}

	public function putUUID(UUID $uuid) : void{
		($this->buffer .= (\pack("V", $uuid->getPart(1))));
		($this->buffer .= (\pack("V", $uuid->getPart(0))));
		($this->buffer .= (\pack("V", $uuid->getPart(3))));
		($this->buffer .= (\pack("V", $uuid->getPart(2))));
	}

	public function getSkin() : SkinData{
		$skinId = $this->getString();
		$skinResourcePatch = $this->getString();
		$skinData = $this->getSkinImage();
		$animationCount = ((\unpack("V", $this->get(4))[1] << 32 >> 32));
		$animations = [];
		for($i = 0; $i < $animationCount; ++$i){
			$animations[] = new SkinAnimation(
				$skinImage = $this->getSkinImage(),
				$animationType = ((\unpack("V", $this->get(4))[1] << 32 >> 32)),
				$animationFrames = ((\unpack("g", $this->get(4))[1]))
			);
		}
		$capeData = $this->getSkinImage();
		$geometryData = $this->getString();
		$animationData = $this->getString();
		$premium = (($this->get(1) !== "\x00"));
		$persona = (($this->get(1) !== "\x00"));
		$capeOnClassic = (($this->get(1) !== "\x00"));
		$capeId = $this->getString();
		$fullSkinId = $this->getString();
		$armSize = $this->getString();
		$skinColor = $this->getString();
		$personaPieceCount = ((\unpack("V", $this->get(4))[1] << 32 >> 32));
		$personaPieces = [];
		for($i = 0; $i < $personaPieceCount; ++$i){
			$personaPieces[] = new PersonaSkinPiece(
				$pieceId = $this->getString(),
				$pieceType = $this->getString(),
				$packId = $this->getString(),
				$isDefaultPiece = (($this->get(1) !== "\x00")),
				$productId = $this->getString()
			);
		}
		$pieceTintColorCount = ((\unpack("V", $this->get(4))[1] << 32 >> 32));
		$pieceTintColors = [];
		for($i = 0; $i < $pieceTintColorCount; ++$i){
			$pieceType = $this->getString();
			$colorCount = ((\unpack("V", $this->get(4))[1] << 32 >> 32));
			$colors = [];
			for($j = 0; $j < $colorCount; ++$j){
				$colors[] = $this->getString();
			}
			$pieceTintColors[] = new PersonaPieceTintColor(
				$pieceType,
				$colors
			);
		}

		return new SkinData($skinId, $skinResourcePatch, $skinData, $animations, $capeData, $geometryData, $animationData, $premium, $persona, $capeOnClassic, $capeId, $fullSkinId, $armSize, $skinColor, $personaPieces, $pieceTintColors);
	}

	/**
	 * @return void
	 */
	public function putSkin(SkinData $skin){
		$this->putString($skin->getSkinId());
		$this->putString($skin->getResourcePatch());
		$this->putSkinImage($skin->getSkinImage());
		($this->buffer .= (\pack("V", count($skin->getAnimations()))));
		foreach($skin->getAnimations() as $animation){
			$this->putSkinImage($animation->getImage());
			($this->buffer .= (\pack("V", $animation->getType())));
			($this->buffer .= (\pack("g", $animation->getFrames())));
		}
		$this->putSkinImage($skin->getCapeImage());
		$this->putString($skin->getGeometryData());
		$this->putString($skin->getAnimationData());
		($this->buffer .= ($skin->isPremium() ? "\x01" : "\x00"));
		($this->buffer .= ($skin->isPersona() ? "\x01" : "\x00"));
		($this->buffer .= ($skin->isPersonaCapeOnClassic() ? "\x01" : "\x00"));
		$this->putString($skin->getCapeId());
		$this->putString($skin->getFullSkinId());
		$this->putString($skin->getArmSize());
		$this->putString($skin->getSkinColor());
		($this->buffer .= (\pack("V", count($skin->getPersonaPieces()))));
		foreach($skin->getPersonaPieces() as $piece){
			$this->putString($piece->getPieceId());
			$this->putString($piece->getPieceType());
			$this->putString($piece->getPackId());
			($this->buffer .= ($piece->isDefaultPiece() ? "\x01" : "\x00"));
			$this->putString($piece->getProductId());
		}
		($this->buffer .= (\pack("V", count($skin->getPieceTintColors()))));
		foreach($skin->getPieceTintColors() as $tint){
			$this->putString($tint->getPieceType());
			($this->buffer .= (\pack("V", count($tint->getColors()))));
			foreach($tint->getColors() as $color){
				$this->putString($color);
			}
		}
	}

	private function getSkinImage() : SkinImage{
		$width = ((\unpack("V", $this->get(4))[1] << 32 >> 32));
		$height = ((\unpack("V", $this->get(4))[1] << 32 >> 32));
		$data = $this->getString();
		return new SkinImage($height, $width, $data);
	}

	private function putSkinImage(SkinImage $image) : void{
		($this->buffer .= (\pack("V", $image->getWidth())));
		($this->buffer .= (\pack("V", $image->getHeight())));
		$this->putString($image->getData());
	}

	public function getSlot() : Item{
		$id = $this->getVarInt();
		if($id === 0){
			return ItemFactory::get(0, 0, 0);
		}

		$auxValue = $this->getVarInt();
		$data = $auxValue >> 8;
		$cnt = $auxValue & 0xff;

		$nbtLen = ((\unpack("v", $this->get(2))[1]));

		/** @var CompoundTag|null $nbt */
		$nbt = null;
		if($nbtLen === 0xffff){
			$c = (\ord($this->get(1)));
			if($c !== 1){
				throw new \UnexpectedValueException("Unexpected NBT count $c");
			}
			$decodedNBT = (new NetworkLittleEndianNBTStream())->read($this->buffer, false, $this->offset, 512);
			if(!($decodedNBT instanceof CompoundTag)){
				throw new \UnexpectedValueException("Unexpected root tag type for itemstack");
			}
			$nbt = $decodedNBT;
		}elseif($nbtLen !== 0){
			throw new \UnexpectedValueException("Unexpected fake NBT length $nbtLen");
		}

		//TODO
		for($i = 0, $canPlaceOn = $this->getVarInt(); $i < $canPlaceOn; ++$i){
			$this->getString();
		}

		//TODO
		for($i = 0, $canDestroy = $this->getVarInt(); $i < $canDestroy; ++$i){
			$this->getString();
		}

		if($id === ItemIds::SHIELD){
			$this->getVarLong(); //"blocking tick" (ffs mojang)
		}
		if($nbt !== null){
			if($nbt->hasTag(self::DAMAGE_TAG, IntTag::class)){
				$data = $nbt->getInt(self::DAMAGE_TAG);
				$nbt->removeTag(self::DAMAGE_TAG);
				if($nbt->count() === 0){
					$nbt = null;
					goto end;
				}
			}
			if(($conflicted = $nbt->getTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION)) !== null){
				$nbt->removeTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION);
				$conflicted->setName(self::DAMAGE_TAG);
				$nbt->setTag($conflicted);
			}
		}
		end:
		return ItemFactory::get($id, $data, $cnt, $nbt);
	}

	public function putSlot(Item $item) : void{
		if($item->getId() === 0){
			$this->putVarInt(0);

			return;
		}

		$this->putVarInt($item->getId());
		$auxValue = (($item->getDamage() & 0x7fff) << 8) | $item->getCount();
		$this->putVarInt($auxValue);

		$nbt = null;
		if($item->hasCompoundTag()){
			$nbt = clone $item->getNamedTag();
		}
		if($item instanceof Durable and $item->getDamage() > 0){
			if($nbt !== null){
				if(($existing = $nbt->getTag(self::DAMAGE_TAG)) !== null){
					$nbt->removeTag(self::DAMAGE_TAG);
					$existing->setName(self::DAMAGE_TAG_CONFLICT_RESOLUTION);
					$nbt->setTag($existing);
				}
			}else{
				$nbt = new CompoundTag();
			}
			$nbt->setInt(self::DAMAGE_TAG, $item->getDamage());
		}

		if($nbt !== null){
			($this->buffer .= (\pack("v", 0xffff)));
			($this->buffer .= \chr(1)); //TODO: some kind of count field? always 1 as of 1.9.0
			($this->buffer .= (new NetworkLittleEndianNBTStream())->write($nbt));
		}else{
			($this->buffer .= (\pack("v", 0)));
		}

		$this->putVarInt(0); //CanPlaceOn entry count (TODO)
		$this->putVarInt(0); //CanDestroy entry count (TODO)

		if($item->getId() === ItemIds::SHIELD){
			$this->putVarLong(0); //"blocking tick" (ffs mojang)
		}
	}

	public function getRecipeIngredient() : Item{
		$id = $this->getVarInt();
		if($id === 0){
			return ItemFactory::get(ItemIds::AIR, 0, 0);
		}
		$meta = $this->getVarInt();
		if($meta === 0x7fff){
			$meta = -1;
		}
		$count = $this->getVarInt();
		return ItemFactory::get($id, $meta, $count);
	}

	public function putRecipeIngredient(Item $item) : void{
		if($item->isNull()){
			$this->putVarInt(0);
		}else{
			$this->putVarInt($item->getId());
			$this->putVarInt($item->getDamage() & 0x7fff);
			$this->putVarInt($item->getCount());
		}
	}

	/**
	 * Decodes entity metadata from the stream.
	 *
	 * @param bool $types Whether to include metadata types along with values in the returned array
	 *
	 * @return mixed[]|mixed[][]
	 * @phpstan-return array<int, mixed>|array<int, array{0: int, 1: mixed}>
	 */
	public function getEntityMetadata(bool $types = true) : array{
		$count = $this->getUnsignedVarInt();
		$data = [];
		for($i = 0; $i < $count; ++$i){
			$key = $this->getUnsignedVarInt();
			$type = $this->getUnsignedVarInt();
			$value = null;
			switch($type){
				case Entity::DATA_TYPE_BYTE:
					$value = (\ord($this->get(1)));
					break;
				case Entity::DATA_TYPE_SHORT:
					$value = ((\unpack("v", $this->get(2))[1] << 48 >> 48));
					break;
				case Entity::DATA_TYPE_INT:
					$value = $this->getVarInt();
					break;
				case Entity::DATA_TYPE_FLOAT:
					$value = ((\unpack("g", $this->get(4))[1]));
					break;
				case Entity::DATA_TYPE_STRING:
					$value = $this->getString();
					break;
				case Entity::DATA_TYPE_COMPOUND_TAG:
					$value = (new NetworkLittleEndianNBTStream())->read($this->buffer, false, $this->offset, 512);
					break;
				case Entity::DATA_TYPE_POS:
					$value = new Vector3();
					$this->getSignedBlockPosition($value->x, $value->y, $value->z);
					break;
				case Entity::DATA_TYPE_LONG:
					$value = $this->getVarLong();
					break;
				case Entity::DATA_TYPE_VECTOR3F:
					$value = $this->getVector3();
					break;
				default:
					throw new \UnexpectedValueException("Invalid data type " . $type);
			}
			if($types){
				$data[$key] = [$type, $value];
			}else{
				$data[$key] = $value;
			}
		}

		return $data;
	}

	/**
	 * Writes entity metadata to the packet buffer.
	 *
	 * @param mixed[][] $metadata
	 * @phpstan-param array<int, array{0: int, 1: mixed}> $metadata
	 */
	public function putEntityMetadata(array $metadata) : void{
		$this->putUnsignedVarInt(count($metadata));
		foreach($metadata as $key => $d){
			$this->putUnsignedVarInt($key); //data key
			$this->putUnsignedVarInt($d[0]); //data type
			switch($d[0]){
				case Entity::DATA_TYPE_BYTE:
					($this->buffer .= \chr($d[1]));
					break;
				case Entity::DATA_TYPE_SHORT:
					($this->buffer .= (\pack("v", $d[1]))); //SIGNED short!
					break;
				case Entity::DATA_TYPE_INT:
					$this->putVarInt($d[1]);
					break;
				case Entity::DATA_TYPE_FLOAT:
					($this->buffer .= (\pack("g", $d[1])));
					break;
				case Entity::DATA_TYPE_STRING:
					$this->putString($d[1]);
					break;
				case Entity::DATA_TYPE_COMPOUND_TAG:
					($this->buffer .= (new NetworkLittleEndianNBTStream())->write($d[1]));
					break;
				case Entity::DATA_TYPE_POS:
					$v = $d[1];
					if($v !== null){
						$this->putSignedBlockPosition($v->x, $v->y, $v->z);
					}else{
						$this->putSignedBlockPosition(0, 0, 0);
					}
					break;
				case Entity::DATA_TYPE_LONG:
					$this->putVarLong($d[1]);
					break;
				case Entity::DATA_TYPE_VECTOR3F:
					$this->putVector3Nullable($d[1]);
					break;
				default:
					throw new \UnexpectedValueException("Invalid data type " . $d[0]);
			}
		}
	}

	/**
	 * Reads a list of Attributes from the stream.
	 * @return Attribute[]
	 *
	 * @throws \UnexpectedValueException if reading an attribute with an unrecognized name
	 */
	public function getAttributeList() : array{
		$list = [];
		$count = $this->getUnsignedVarInt();

		for($i = 0; $i < $count; ++$i){
			$min = ((\unpack("g", $this->get(4))[1]));
			$max = ((\unpack("g", $this->get(4))[1]));
			$current = ((\unpack("g", $this->get(4))[1]));
			$default = ((\unpack("g", $this->get(4))[1]));
			$name = $this->getString();

			$attr = Attribute::getAttributeByName($name);
			if($attr !== null){
				$attr->setMinValue($min);
				$attr->setMaxValue($max);
				$attr->setValue($current);
				$attr->setDefaultValue($default);

				$list[] = $attr;
			}else{
				throw new \UnexpectedValueException("Unknown attribute type \"$name\"");
			}
		}

		return $list;
	}

	/**
	 * Writes a list of Attributes to the packet buffer using the standard format.
	 *
	 * @param Attribute ...$attributes
	 */
	public function putAttributeList(Attribute ...$attributes) : void{
		$this->putUnsignedVarInt(count($attributes));
		foreach($attributes as $attribute){
			($this->buffer .= (\pack("g", $attribute->getMinValue())));
			($this->buffer .= (\pack("g", $attribute->getMaxValue())));
			($this->buffer .= (\pack("g", $attribute->getValue())));
			($this->buffer .= (\pack("g", $attribute->getDefaultValue())));
			$this->putString($attribute->getName());
		}
	}

	/**
	 * Reads and returns an EntityUniqueID
	 */
	public function getEntityUniqueId() : int{
		return $this->getVarLong();
	}

	/**
	 * Writes an EntityUniqueID
	 */
	public function putEntityUniqueId(int $eid) : void{
		$this->putVarLong($eid);
	}

	/**
	 * Reads and returns an EntityRuntimeID
	 */
	public function getEntityRuntimeId() : int{
		return $this->getUnsignedVarLong();
	}

	/**
	 * Writes an EntityRuntimeID
	 */
	public function putEntityRuntimeId(int $eid) : void{
		$this->putUnsignedVarLong($eid);
	}

	/**
	 * Reads an block position with unsigned Y coordinate.
	 *
	 * @param int $x reference parameter
	 * @param int $y reference parameter
	 * @param int $z reference parameter
	 */
	public function getBlockPosition(&$x, &$y, &$z) : void{
		$x = $this->getVarInt();
		$y = $this->getUnsignedVarInt();
		$z = $this->getVarInt();
	}

	/**
	 * Writes a block position with unsigned Y coordinate.
	 */
	public function putBlockPosition(int $x, int $y, int $z) : void{
		$this->putVarInt($x);
		$this->putUnsignedVarInt($y);
		$this->putVarInt($z);
	}

	/**
	 * Reads a block position with a signed Y coordinate.
	 *
	 * @param int $x reference parameter
	 * @param int $y reference parameter
	 * @param int $z reference parameter
	 */
	public function getSignedBlockPosition(&$x, &$y, &$z) : void{
		$x = $this->getVarInt();
		$y = $this->getVarInt();
		$z = $this->getVarInt();
	}

	/**
	 * Writes a block position with a signed Y coordinate.
	 */
	public function putSignedBlockPosition(int $x, int $y, int $z) : void{
		$this->putVarInt($x);
		$this->putVarInt($y);
		$this->putVarInt($z);
	}

	/**
	 * Reads a floating-point Vector3 object with coordinates rounded to 4 decimal places.
	 */
	public function getVector3() : Vector3{
		return new Vector3(
			((\round((\unpack("g", $this->get(4))[1]),  4))),
			((\round((\unpack("g", $this->get(4))[1]),  4))),
			((\round((\unpack("g", $this->get(4))[1]),  4)))
		);
	}

	/**
	 * Writes a floating-point Vector3 object, or 3x zero if null is given.
	 *
	 * Note: ONLY use this where it is reasonable to allow not specifying the vector.
	 * For all other purposes, use the non-nullable version.
	 *
	 * @see NetworkBinaryStream::putVector3()
	 */
	public function putVector3Nullable(?Vector3 $vector) : void{
		if($vector !== null){
			$this->putVector3($vector);
		}else{
			($this->buffer .= (\pack("g", 0.0)));
			($this->buffer .= (\pack("g", 0.0)));
			($this->buffer .= (\pack("g", 0.0)));
		}
	}

	/**
	 * Writes a floating-point Vector3 object
	 */
	public function putVector3(Vector3 $vector) : void{
		($this->buffer .= (\pack("g", $vector->x)));
		($this->buffer .= (\pack("g", $vector->y)));
		($this->buffer .= (\pack("g", $vector->z)));
	}

	public function getByteRotation() : float{
		return ((\ord($this->get(1))) * (360 / 256));
	}

	public function putByteRotation(float $rotation) : void{
		($this->buffer .= \chr((int) ($rotation / (360 / 256))));
	}

	/**
	 * Reads gamerules
	 * TODO: implement this properly
	 *
	 * @return mixed[][], members are in the structure [name => [type, value]]
	 * @phpstan-return array<string, array{0: int, 1: bool|int|float}>
	 */
	public function getGameRules() : array{
		$count = $this->getUnsignedVarInt();
		$rules = [];
		for($i = 0; $i < $count; ++$i){
			$name = $this->getString();
			$type = $this->getUnsignedVarInt();
			$value = null;
			switch($type){
				case 1:
					$value = (($this->get(1) !== "\x00"));
					break;
				case 2:
					$value = $this->getUnsignedVarInt();
					break;
				case 3:
					$value = ((\unpack("g", $this->get(4))[1]));
					break;
			}

			$rules[$name] = [$type, $value];
		}

		return $rules;
	}

	/**
	 * Writes a gamerule array, members should be in the structure [name => [type, value]]
	 * TODO: implement this properly
	 *
	 * @param mixed[][] $rules
	 * @phpstan-param array<string, array{0: int, 1: bool|int|float}> $rules
	 */
	public function putGameRules(array $rules) : void{
		$this->putUnsignedVarInt(count($rules));
		foreach($rules as $name => $rule){
			$this->putString($name);
			$this->putUnsignedVarInt($rule[0]);
			switch($rule[0]){
				case 1:
					($this->buffer .= ($rule[1] ? "\x01" : "\x00"));
					break;
				case 2:
					$this->putUnsignedVarInt($rule[1]);
					break;
				case 3:
					($this->buffer .= (\pack("g", $rule[1])));
					break;
			}
		}
	}

	protected function getEntityLink() : EntityLink{
		$link = new EntityLink();

		$link->fromEntityUniqueId = $this->getEntityUniqueId();
		$link->toEntityUniqueId = $this->getEntityUniqueId();
		$link->type = (\ord($this->get(1)));
		$link->immediate = (($this->get(1) !== "\x00"));

		return $link;
	}

	protected function putEntityLink(EntityLink $link) : void{
		$this->putEntityUniqueId($link->fromEntityUniqueId);
		$this->putEntityUniqueId($link->toEntityUniqueId);
		($this->buffer .= \chr($link->type));
		($this->buffer .= ($link->immediate ? "\x01" : "\x00"));
	}

	protected function getCommandOriginData() : CommandOriginData{
		$result = new CommandOriginData();

		$result->type = $this->getUnsignedVarInt();
		$result->uuid = $this->getUUID();
		$result->requestId = $this->getString();

		if($result->type === CommandOriginData::ORIGIN_DEV_CONSOLE or $result->type === CommandOriginData::ORIGIN_TEST){
			$result->varlong1 = $this->getVarLong();
		}

		return $result;
	}

	protected function putCommandOriginData(CommandOriginData $data) : void{
		$this->putUnsignedVarInt($data->type);
		$this->putUUID($data->uuid);
		$this->putString($data->requestId);

		if($data->type === CommandOriginData::ORIGIN_DEV_CONSOLE or $data->type === CommandOriginData::ORIGIN_TEST){
			$this->putVarLong($data->varlong1);
		}
	}

	protected function getStructureSettings() : StructureSettings{
		$result = new StructureSettings();

		$result->paletteName = $this->getString();

		$result->ignoreEntities = (($this->get(1) !== "\x00"));
		$result->ignoreBlocks = (($this->get(1) !== "\x00"));

		$this->getBlockPosition($result->structureSizeX, $result->structureSizeY, $result->structureSizeZ);
		$this->getBlockPosition($result->structureOffsetX, $result->structureOffsetY, $result->structureOffsetZ);

		$result->lastTouchedByPlayerID = $this->getEntityUniqueId();
		$result->rotation = (\ord($this->get(1)));
		$result->mirror = (\ord($this->get(1)));
		$result->integrityValue = ((\unpack("G", $this->get(4))[1]));
		$result->integritySeed = ((\unpack("N", $this->get(4))[1] << 32 >> 32));
		$result->pivot = $this->getVector3();

		return $result;
	}

	protected function putStructureSettings(StructureSettings $structureSettings) : void{
		$this->putString($structureSettings->paletteName);

		($this->buffer .= ($structureSettings->ignoreEntities ? "\x01" : "\x00"));
		($this->buffer .= ($structureSettings->ignoreBlocks ? "\x01" : "\x00"));

		$this->putBlockPosition($structureSettings->structureSizeX, $structureSettings->structureSizeY, $structureSettings->structureSizeZ);
		$this->putBlockPosition($structureSettings->structureOffsetX, $structureSettings->structureOffsetY, $structureSettings->structureOffsetZ);

		$this->putEntityUniqueId($structureSettings->lastTouchedByPlayerID);
		($this->buffer .= \chr($structureSettings->rotation));
		($this->buffer .= \chr($structureSettings->mirror));
		($this->buffer .= (\pack("G", $structureSettings->integrityValue)));
		($this->buffer .= (\pack("N", $structureSettings->integritySeed)));
		$this->putVector3($structureSettings->pivot);
	}

	protected function getStructureEditorData() : StructureEditorData{
		$result = new StructureEditorData();

		$result->structureName = $this->getString();
		$result->structureDataField = $this->getString();

		$result->includePlayers = (($this->get(1) !== "\x00"));
		$result->showBoundingBox = (($this->get(1) !== "\x00"));

		$result->structureBlockType = $this->getVarInt();
		$result->structureSettings = $this->getStructureSettings();
		$result->structureRedstoneSaveMove = $this->getVarInt();

		return $result;
	}

	protected function putStructureEditorData(StructureEditorData $structureEditorData) : void{
		$this->putString($structureEditorData->structureName);
		$this->putString($structureEditorData->structureDataField);

		($this->buffer .= ($structureEditorData->includePlayers ? "\x01" : "\x00"));
		($this->buffer .= ($structureEditorData->showBoundingBox ? "\x01" : "\x00"));

		$this->putVarInt($structureEditorData->structureBlockType);
		$this->putStructureSettings($structureEditorData->structureSettings);
		$this->putVarInt($structureEditorData->structureRedstoneSaveMove);
	}
}
