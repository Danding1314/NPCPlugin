<?php

namespace ojy\npc;

use pocketmine\entity\Living;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\Player;

class EntityNPC extends Living
{

    public $height = 1;

    public $width = 1;

    public const NETWORK_ID = 4949;

    public function getName(): string
    {
        return "EntityNPC";
    }

    public function initEntity(): void
    {
        parent::initEntity(); // TODO: Change the autogenerated stub
        $this->setImmobile();

        if ($this->namedtag->getTag('npcEntityType', StringTag::class) !== null) {
            $type = $this->namedtag->getString('npcEntityType');
            $id = array_search($type, AddActorPacket::LEGACY_ID_MAP_BC);
            $height = EntityConfig::HEIGHTS[$id] ?? 1;
            $width = EntityConfig::WIDTHS[$id] ?? 1;
            $this->width = $width;
            $this->height = $height;
            $this->propertyManager->setFloat(self::DATA_BOUNDING_BOX_WIDTH, $width);
            $this->propertyManager->setFloat(self::DATA_BOUNDING_BOX_HEIGHT, $height);
        }
        $data = $this->getData();
        if ($data instanceof NPCData) {
            $this->setScale($data->getScale());
            $name = $data->getName();
            $d = explode("(줄바꿈)", $name);
            $d = implode("\n", $d);
            $this->setNameTag($d);
            $this->setNameTagAlwaysVisible();
        }
    }

    public function sendSpawnPacket(Player $player): void
    {
        $packet = new AddActorPacket ();
        $packet->entityRuntimeId = $this->getId();
        $packet->type = $this->namedtag->getString("npcEntityType");
        $packet->metadata = $this->getDataPropertyManager()->getAll();
        $packet->yaw = $this->yaw;
        $packet->pitch = $this->pitch;
        $packet->motion = $this->getMotion();
        $packet->position = $this->getPosition();
        $player->dataPacket($packet);
    }

    public function getData(): ?NPCData
    {
        $tag = $this->namedtag->getTag('NPCData');
        if ($tag instanceof StringTag) {
            return NPCData::deserialize($tag->getValue());
        }
        return null;
    }

    public function setData(NPCData $data)
    {
        $this->namedtag->setString('NPCData', $data->serialize());
    }

    public function saveNBT(): void
    {
        parent::saveNBT();
        $data = $this->getData();
        if ($data instanceof NPCData) {
            $this->namedtag->setString('NPCData', $data->serialize());
        }
        $this->namedtag->setString("npcEntityType", $this->namedtag->getString("npcEntityType"));
    }
}