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
use pocketmine\form\MenuOption;
use pocketmine\form\SimpleForm;

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

        $playerList = [];
        foreach(Server::getInstance()->getOnlinePlayers() as $player) {
            if($player->getName() !== $sender->getName()) {
                $playerList[] = new MenuOption($player->getName());
            }
        }

        if(empty($playerList)) {
            $sender->sendMessage(DataManager::getMessage("no_players_online"));
            return;
        }

        $form = new SimpleForm(function(Player $receiver, ?int $data) use ($sender) {
            if($data !== null) {
                $teleportRequestManager = FastTP::getInstance()->getTeleportRequestManager();
                $chosenPlayerName = $receiver->getDropdown($data)->getText();
                $chosenPlayer = Server::getInstance()->getPlayerExact($chosenPlayerName);

                if(!$chosenPlayer instanceof Player or !$chosenPlayer->isOnline()) {
                    $sender->sendMessage(DataManager::getMessage("invalid_player"));
                    return;
                }

                if($sender->getName() === $chosenPlayer->getName()) {
                    $sender->sendMessage(DataManager::getMessage("cannot_send_request_to_yourself"));
                    return;
                }

                if($teleportRequestManager->requestExists($sender, $chosenPlayer)) {
                    $sender->sendMessage(DataManager::getMessage("already_active_request"));
                    return;
                }


                $this->sendTeleportRequest($sender, $receiver);
                $teleportRequestManager->dispatchRequest($sender, $chosenPlayer);
                $sender->sendMessage(DataManager::getMessage("teleport_request_send", ["RECEIVER" => $chosenPlayer->getName()]));
                $chosenPlayer->sendMessage(DataManager::getMessage("teleport_request_received", [
                    "SENDER" => $sender->getName(),
                    "VALIDITY_TIME" => FastTP::getInstance()->getConfig()->get("teleport_request_validity")]));
            }
        });

        $form->setTitle(DataManager::getMessage("teleport_request_send_title"));
        $form->setContent(DataManager::getMessage("teleport_request_send_content"));
        $form->addDropdown(DataManager::getMessage("teleport_request_send_dropdown"), $playerList);
        $sender->sendForm($form);
    }


    private function sendTeleportRequest(Player $sender, Player $receiver) {
        $teleportRequestManager = FastTP::getInstance()->getTeleportRequestManager();
        $teleportRequestManager->sendRequest($sender, $receiver);

    }

    public function getOwningPlugin(): Plugin {
        return FastTP::getInstance();
    }
}
