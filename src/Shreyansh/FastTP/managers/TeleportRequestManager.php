<?php

namespace Shreyansh\FastTP\managers;

use Shreyansh\FastTP\FastTP;

class TeleportRequestManager {

    private array $requests = [];

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