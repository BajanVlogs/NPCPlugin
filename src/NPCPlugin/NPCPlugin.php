<?php

namespace NPCPlugin;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;

class NPCPlugin extends PluginBase implements Listener {

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("npcs.yml");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "buynpc" && $sender instanceof Player) {
            // Check if player has enough money to buy NPC
            $npcCost = 100000; // Set the cost of the NPC
            if (!$this->hasEnoughMoney($sender, $npcCost)) {
                $sender->sendMessage("You don't have enough money to buy an NPC.");
                return false;
            }
            
            // Deduct money from player
            if (!$this->reduceMoney($sender, $npcCost)) {
                $sender->sendMessage("Error: Failed to deduct money.");
                return false;
            }
            
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
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $event->getItem();

    }

    // Check if player has enough money
    private function hasEnoughMoney(Player $player, float $amount): bool {
        // Implement your money checking function here
        // Example: return $this->getEconomyAPI()->myMoneyCheckFunction($player->getName()) >= $amount;
        return true; // Placeholder, replace with actual implementation
    }

    // Deduct money from player
    private function reduceMoney(Player $player, float $amount): bool {
        // Implement your money deduction function here
        // Example: return $this->getEconomyAPI()->reduceMoney($player->getName(), $amount);
        return true; // Placeholder, replace with actual implementation
    }
}
