<?php

namespace NPCPlugin;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\utils\Config;

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

    }
}
