<?php

namespace Shreyansh\FastTP\managers;

use Shreyansh\FastTP\FastTP;

class TeleportRequestManager {

    private array $requests = [];

    public function sendRequest(Player $sender, Player $receiver) {
        $this->requests[$sender->getName()][$receiver->getName()] = true;

        $api = $sender->getServer()->getPluginManager()->getPlugin("FormAPI");

        if($api === null) {
            $sender->sendMessage("§cError: FormAPI is not installed.");
            return;
        }

        $form = $api->createModalForm(function(Player $sender, ?bool $data) use ($receiver) {
            if($data === null) {
                return;
            }

            if($data) {
                $sender->teleport($receiver->getPosition());
                $this->closeRequest($sender, $receiver);
                $sender->sendMessage(DataManager::getMessage("sender_teleport_request_accepted", ["RECEIVER" => $receiver->getName()]));
                $receiver->sendMessage(DataManager::getMessage("receiver_teleport_request_accepted"));
            } else {
                $this->closeRequest($sender, $receiver);
                $sender->sendMessage(DataManager::getMessage("sender_teleport_request_denied", ["RECEIVER" => $receiver->getName()]));
                $receiver->sendMessage(DataManager::getMessage("receiver_teleport_request_denied"));
            }
        });

        $form->setTitle("Teleport Request from " . $sender->getName());
        $form->setContent("Do you want to accept or deny the teleport request from " . $sender->getName() . "?");
        $form->setButton1("Accept");
        $form->setButton2("Deny");
        $form->sendToPlayer($receiver);
    }

    public function dispatchRequest(string $sender, string $receiver): void {
        $this->requests[$receiver][] = [
            $sender,
            $this->getRequestValidTill()];
    }

    private function getRequestValidTill(): int {
        return time() + FastTP::getInstance()->getConfig()->get("timer");
    }

    public function closeRequest(string $sender, string $receiver): void {
        foreach ($this->requests[$receiver] as $key => $request) {
            if($sender === $request[0]) {
                unset($this->requests[$receiver][$key]);
            }
        }
    }

    public function requestExists(string $sender, string $receiver): bool {
        if(isset($this->requests[$receiver])) {
            foreach ($this->requests[$receiver] as $key => $request) {
                if($sender === $request[0] and $request[1] >= time()) {
                    return true;
                }
            }
        }
        return false;
    }

}
