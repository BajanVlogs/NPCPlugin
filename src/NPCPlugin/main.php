<?php

namespace NPCPlugin;

use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\types\EntityLegacyIds;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class NPCPlugin extends PluginBase implements Listener {

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("npcs.yml");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "buynpc" && $sender instanceof Player) {
            // Check if player has enough money to buy NPC
            $money = $this->getEconomyAPI()->myMoneyFunction($sender->getName()); // Implement your money checking function here
            $npcCost = 100; // Set the cost of the NPC
            if ($money < $npcCost) {
                $sender->sendMessage("You don't have enough money to buy an NPC.");
                return false;
            }
            
            // Deduct money from player
            $this->getEconomyAPI()->reduceMoney($sender->getName(), $npcCost); // Implement your money deduction function here
            
            // Spawn NPC
            $npc = new NPC($sender->getLevel(), Entity::createEntity("Human", $sender->getLevel()->getChunk($sender->getX() >> 4, $sender->getZ() >> 4), Human::createBaseNBT($sender->getPosition()->asVector3()->add(0, 1))));
            $npc->setOwner($sender->getName());
            $npc->spawnToAll();
            $sender->sendMessage("You have purchased an NPC companion!");
            return true;
        }
        return false;
    }

    // Handle player interaction event to make NPC follow the player
    public function onPlayerInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $event->getItem();

        // Implement NPC interaction logic here
    }
}

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

    public function attack(EntityDamageByEntityEvent $source): void {
        $damager = $source->getDamager();
        if ($damager instanceof Player && $damager->getName() !== $this->owner) {
            $source->setDamage(2); // Deal 1 heart (2 damage points) of damage to the target
            $this->heal(2); // Heal the NPC by 1 heart (2 health points)
        }
        parent::attack($source);
    }

    public function onUpdate(int $currentTick): bool {
        $owner = $this->getOwner();
        $player = $this->getLevel()->getNearestEntity($this, 5, Player::class);
        if ($player instanceof Player && $player->getName() === $owner) {
            $this->target = $player;
        } elseif (!$this->target instanceof Player) {
            $this->target = null;
            return parent::onUpdate($currentTick);
        }

        $diffX = $this->target->x - $this->x;
        $diffZ = $this->target->z - $this->z;
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
