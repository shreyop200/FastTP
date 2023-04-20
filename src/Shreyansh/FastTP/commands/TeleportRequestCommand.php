<?php

namespace Shreyansh\FastTP\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Shreyansh\FastTP\managers\DataManager;
use Shreyansh\FastTP\FastTP;

class TeleportRequestCommand extends Command implements PluginOwned {

    public function __construct() {
        parent::__construct("tprequest");
        $this->setDescription(DataManager::getMessage("teleport_request_send_description"));
        $this->setUsage(DataManager::getMessage("teleport_request_send_usage"));
        $this->setAliases(["tpr"]);
        $this->setPermission("fasttp.teleport");
        $this->setPermissionMessage(DataManager::getMessage("no_perm"));
    }


    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$sender instanceof Player) {
            $sender->sendMessage(DataManager::getMessage("not_player"));
            return;
        }

        if(!$this->testPermission($sender))
            return;

        if(count($args) < 1) {
            $sender->sendMessage(TextFormat::RED . "Usage: " . DataManager::getMessage("teleport_request_send_usage"));
            return;
        }

        $receiver = Server::getInstance()->getPlayerExact($args[0]);

        if(!$receiver instanceof Player or !$receiver->isOnline()) {
            $sender->sendMessage(DataManager::getMessage("invalid_player"));
            return;
        }

        if($sender->getName() === $receiver->getName()) {
            $sender->sendMessage(DataManager::getMessage("cannot_send_request_to_yourself"));
            return;
        }

        $teleportRequestManager = TeleportationsX::getInstance()->getTeleportRequestManager();

        if($teleportRequestManager->requestExists($sender, $receiver)) {
            $sender->sendMessage(DataManager::getMessage("already_active_request"));
            return;
        }

        $teleportRequestManager->dispatchRequest($sender, $receiver);
        $sender->sendMessage(DataManager::getMessage("teleport_request_send", ["RECEIVER" => $receiver->getName()]));
        $receiver->sendMessage(DataManager::getMessage("teleport_request_received", [
            "SENDER" => $sender->getName(),
            "VALIDITY_TIME" => TeleportationsX::getInstance()->getConfig()->get("teleport_request_validity")]));
    }


    public function getOwningPlugin(): Plugin {
        return FastTP::getInstance();
    }
}
