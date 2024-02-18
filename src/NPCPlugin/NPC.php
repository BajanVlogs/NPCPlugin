<?php

namespace NPCPlugin\NPC;

use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent; // Changed from EntityDamageByEntityEvent
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\types\EntityLegacyIds;
use pocketmine\math\Vector3;

class NPC extends Human {

    protected $owner;
    protected $target;

    public function setOwner(string $name) {
        $this->owner = $name;
    }

    public function getOwner(): string {
        return $this->owner;
    }

    public function getNameTag(): string {
        return TextFormat::GREEN . "NPC Bot\n" . TextFormat::GRAY . $this->owner;
    }

    public function getHealth(): float {
        return 100; // Set NPC's health to 100 hearts
    }

    public function attack(EntityDamageEvent $source): void { // Changed parameter type
        $damager = $source->getDamager();
        if ($damager instanceof Player && $damager->getName() !== $this->owner) {
            $source->setBaseDamage(2); // Changed to setBaseDamage
            $this->heal(2); // Heal the NPC by 1 heart (2 health points)
        }
        parent::attack($source);
    }

    public function onUpdate(int $currentTick): bool {
        $owner = $this->getOwner();
        $player = $this->getLevel()->getNearestEntity($this, 5, Player::class); // Changed namespace
        if ($player instanceof Player && $player->getName() === $owner) {
            $this->target = $player;
        } elseif (!$this->target instanceof Player) {
            $this->target = null;
            return parent::onUpdate($currentTick);
        }

        $diffX = $this->target->getX() - $this->getX(); // Changed to getX()
        $diffZ = $this->target->getZ() - $this->getZ(); // Changed to getZ()
        $distance = sqrt($diffX ** 2 + $diffZ ** 2);

        if ($distance > 0.1) { // If distance is greater than 0.1, move towards the target
            $dx = $diffX / $distance;
            $dz = $diffZ / $distance;
            $this->motion->setComponents($dx * 0.2, $this->motion->y, $dz * 0.2); // Adjust speed here (0.2 is normal walking speed)
            $this->yaw = rad2deg(atan2(-$dx, $dz));
            $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        }
        return parent::onUpdate($currentTick);
    }

    public function spawnTo(Player $player): void {
        parent::spawnTo($player);

        $pk = new AddActorPacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->type = EntityLegacyIds::HUMAN;
        $pk->position = $this->asVector3();
        $pk->motion = $this->getMotion();
        $pk->yaw = $this->yaw;
        $pk->pitch = $this->pitch;
        $pk->headYaw = $this->headYaw;
        $pk->metadata = $this->getDataPropertyManager()->getAll();
        $pk->metadata[Entity::DATA_NAMETAG] = [Entity::DATA_TYPE_STRING, $this->getNameTag()]; // Set the name tag
        $player->sendDataPacket($pk);
    }
}

