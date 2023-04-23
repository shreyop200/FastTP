<?php

namespace Shreyansh\FastTP\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as C;
use Shreyansh\FastTP\managers\DataManager;
use Shreyansh\FastTP\FastTP;
use pocketmine\form\MenuOption;
use pocketmine\form\SimpleForm;

class TeleportAcceptCommand extends Command implements PluginOwned {

    public function __construct() {
        parent::__construct("tpaccept");
        $this->setDescription(DataManager::getMessage("teleport_request_accept_description"));
        $this->setUsage(DataManager::getMessage("teleport_request_accept_usage"));
        $this->setAliases(["tpa"]);
        $this->setPermission("fasttp.accept");
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
            $sender->sendMessage(TextFormat::RED . "Usage: " . DataManager::getMessage("teleport_request_accept_usage"));
            return;
        }

        $receiver = $sender;
        $senderName = implode(" ", $args);
        $sender = Server::getInstance()->getPlayerExact($senderName);

        if(!$sender instanceof Player or !$sender->isOnline()) {
            $receiver->sendMessage(DataManager::getMessage("invalid_player"));
            return;
        }

        $teleportRequestManager = FastTP::getInstance()->getTeleportRequestManager();

        if(!$teleportRequestManager->requestExists($sender, $receiver)) {
            $receiver->sendMessage(DataManager::getMessage("no_active_request"));
            return;
        }

        $api = $sender->getServer()->getPluginManager()->getPlugin("FormAPI");

        if($api === null) {
            $sender->sendMessage("§cError: FormAPI is not installed.");
            return;
        }

        $form = $api->createModalForm(function(Player $sender, ?bool $data) use ($receiver, $senderName, $teleportRequestManager) {
            if($data === null) {
                return;
            }

            if($data) {
                $sender->teleport($receiver->getPosition());
                $teleportRequestManager->closeRequest($receiver, $sender);
                $sender->sendMessage(DataManager::getMessage("sender_teleport_request_accepted", ["RECEIVER" => $receiver->getName()]));
                $receiver->sendMessage(DataManager::getMessage("receiver_teleport_request_accepted"));
            } else {
                $teleportRequestManager->closeRequest($receiver, $sender);
                $sender->sendMessage(DataManager::getMessage("sender_teleport_request_denied", ["RECEIVER" => $receiver->getName()]));
                $receiver->sendMessage(DataManager::getMessage("receiver_teleport_request_denied"));
            }
        });

        $form->setTitle("Teleport Request from $senderName");
        $form->setContent("Do you want to accept or deny the teleport request from $senderName?");
        $form->setButton1("Accept");
        $form->setButton2("Deny");
        $form->sendToPlayer($receiver);
    }

    public function getOwningPlugin(): Plugin {
        return FastTP::getInstance();
    }
}
