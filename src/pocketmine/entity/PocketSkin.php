<?php

declare(strict_types=1);

namespace pocketmine\entity;

use InvalidArgumentException;
use pocketmine\network\mcpe\protocol\types\SkinData;

class PocketSkin extends Skin{

  public $data;

  public function __construct(SkinData $data){
    $this->data = $data;
    $geometryName = "";
    $resourcePatch = json_decode($data->getResourcePatch(), true);
    if (is_array($resourcePatch["geometry"]) && is_string($resourcePatch["geometry"]["default"])) {
      $geometryName = $resourcePatch["geometry"]["default"];
    }
    parent::__construct($data->getSkinId(), $data->getSkinImage()->getData(), $data->getCapeImage()->getData(), $geometryName, $data->getGeometryData());
  }

  public function validate(): void{
    if($this->getSkinId() == ""){
      throw new InvalidArgumentException("Skin ID boş olamaz!");
    }
    if($this->data->getSkinImage()->getWidth() < 64){
      throw new InvalidArgumentException("Skin yüksekliği 64 veya daha büyük olmalıdır!");
    }
    if($this->data->getSkinImage()->getHeight() < 32){
      throw new InvalidArgumentException("Skin genişliği 32 veya daha büyük olmalıdır!");
    }
    if(strlen($this->data->getSkinImage()->getData()) < 64 * 32 * 4){
      throw new InvalidArgumentException("Skin dataları 8192  bit'ten daha uzun olmalıdır!");
    }
  }
}
