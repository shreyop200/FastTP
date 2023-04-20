<?php

declare(strict_types=1);

namespace Shreyansh\FastTP\managers;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use Shreyansh\FastTP\FastTP;

class DataManager {

    public static function init() {
        FastTP::getInstance()->saveDefaultConfig();
        FastTP::getInstance()->saveResource("setup.yml");

        FastTP::getInstance()->prepare = FastTP::getInstance()->db2->prepare("SELECT * FROM sqlite_master WHERE type = 'table' AND name = 'spawn'");
        FastTP::getInstance()->result = FastTP::getInstance()->prepare->execute();
        $sql = DataManager::rowsCount();
        $count = count($sql);
        if($count === 0) {
            FastTP::getInstance()->prepare = FastTP::getInstance()->db2->prepare("CREATE TABLE spawn (
                id INTEGER PRIMARY KEY,
                x INTEGER,
                y INTEGER,
                z INTEGER,
                world TEXT)");
            FastTP::getInstance()->result = FastTP::getInstance()->prepare->execute();
        }

        FastTP::getInstance()->prepare = FastTP::getInstance()->db2->prepare("SELECT * FROM sqlite_master WHERE type = 'table' AND name = 'warps'");
        FastTP::getInstance()->result = FastTP::getInstance()->prepare->execute();
        $sql = DataManager::rowsCount();
        $count = count($sql);
        if($count === 0) {
            FastTP::getInstance()->prepare = FastTP::getInstance()->db2->prepare("CREATE TABLE warps (
                id INTEGER PRIMARY KEY,
                label TEXT,
                x INTEGER,
                y INTEGER,
                z INTEGER,
                world TEXT)");
            FastTP::getInstance()->result = FastTP::getInstance()->prepare->execute();
        }

        FastTP::getInstance()->prepare = FastTP::getInstance()->db2->prepare("SELECT * FROM sqlite_master WHERE type = 'table' AND name = 'homes'");
        FastTP::getInstance()->result = FastTP::getInstance()->prepare->execute();
        $sql = DataManager::rowsCount();
        $count = count($sql);
        if($count === 0) {
            FastTP::getInstance()->prepare = FastTP::getInstance()->db2->prepare("CREATE TABLE homes (
                id INTEGER PRIMARY KEY,
                owner TEXT,
                label TEXT,
                x INTEGER,
                y INTEGER,
                z INTEGER,
                world TEXT)");
            FastTP::getInstance()->result = FastTP::getInstance()->prepare->execute();
        }
    }


    public static function rowsCount(): array {
        $row = [];

        $i = 0;

        while ($res = FastTP::getInstance()->result->fetchArray(SQLITE3_ASSOC)) {

            $row[$i] = $res;
            $i++;

        }

        return $row;
    }

    public static function getMessage(string $identifier, array $placeHolders = null): string {
        $msg = (new Config(FastTP::getInstance()->getDataFolder() . "setup.yml", Config::YAML))->get($identifier);
        if($msg === null) {
            return "Error while archiving all setup messages!";
        }
        if(is_array($placeHolders)) {
            foreach ($placeHolders as $placeHolder => $value) {
                $msg = str_replace("{" . $placeHolder . "}", (string)$value, $msg);
            }
        }
        return TextFormat::colorize($msg);
    }

}
