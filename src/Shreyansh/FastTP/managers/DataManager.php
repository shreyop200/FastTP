<?php

declare(strict_types=1);

namespace Shreyansh\FastTP\managers;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\Server;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use Shreyansh\FastTP\FastTP;
use Shreyansh\FastTP\tasks\SQLiteTask;

class DataManager {

    public static function init(): void {
        FastTP::getInstance()->saveDefaultConfig();
        FastTP::getInstance()->saveResource("setup.yml");

        $query = "CREATE TABLE IF NOT EXISTS teleport_requests (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    sender_uuid TEXT NOT NULL,
                    sender_name TEXT NOT NULL,
                    recipient_uuid TEXT NOT NULL,
                    recipient_name TEXT NOT NULL,
                    x FLOAT NOT NULL,
                    y FLOAT NOT NULL,
                    z FLOAT NOT NULL,
                    world TEXT NOT NULL
                )";
        self::executeQuery($query);

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

    public static function executeQuery(string $query, ?callable $callback = null): void {
        $plugin = FastTP::getInstance();
        $task = new SQLiteTask($query);
        if ($callback !== null) {
            $task->setOnCompletion($callback);
        }
        Server::getInstance()->getAsyncPool()->submitTask($task);
    }

    public static function executeQuerySync(string $query): \Generator {
        $plugin = FastTP::getInstance();
        $task = new SQLiteTask($query);
        $handler = Server::getInstance()->getAsyncPool()->submitTask($task);
        while(!$handler->isCompleted()) {
            yield;
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
