<?php

namespace pocketmine\entity\ai;

use pocketmine\entity\Entity;

interface Linkable{

    public function getLink() : ?Linkable;

    public function setLink(?Linkable $entity) : Linkable;

    public function unlink() : bool;
}