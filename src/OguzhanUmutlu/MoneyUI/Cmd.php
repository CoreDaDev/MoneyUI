<?php

namespace OguzhanUmutlu\MoneyUI;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class Cmd extends Command {
    public function __construct() {
        parent::__construct(MoneyUI::translate("command.name"), MoneyUI::translate("command.description"), null, MoneyUI::translate("command.aliases"));
        $this->setPermission("economyapi.command.mymoney");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$sender instanceof Player) {
            $sender->sendMessage(MoneyUI::translate("use-in-game"));
            return;
        }
        MoneyUI::mainMenu($sender);
    }
}