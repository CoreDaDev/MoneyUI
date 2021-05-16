<?php

namespace OguzhanUmutlu\MoneyUI;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use onebone\economyapi\EconomyAPI;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class MoneyUI extends PluginBase {
    /*** @var MoneyUI */
    private static $i;

    public function onEnable() {
        self::$i=$this;
        $this->saveResource("messages.yml");
        $this->getServer()->getCommandMap()->register($this->getName(), new Cmd());
    }

    /**
     * @param Player $player
     */
    public static function mainMenu(Player $player) {
        $buttons = [];
        if($player->hasPermission("economyapi.command.mymoney") && self::translate("main-menu.buttons.my")) $buttons[] = new MenuOption(self::translate("main-menu.buttons.my"));
        if($player->hasPermission("economyapi.command.seemoney") && self::translate("main-menu.buttons.look")) $buttons[] = new MenuOption(self::translate("main-menu.buttons.look"));
        if($player->hasPermission("economyapi.command.pay") && self::translate("main-menu.buttons.pay")) $buttons[] = new MenuOption(self::translate("main-menu.buttons.pay"));
        if($player->hasPermission("economyapi.command.topmoney") && self::translate("main-menu.buttons.top")) $buttons[] = new MenuOption(self::translate("main-menu.buttons.top"));
        if($player->hasPermission("economyapi.command.givemoney") && self::translate("main-menu.buttons.add")) $buttons[] = new MenuOption(self::translate("main-menu.buttons.add"));
        if($player->hasPermission("economyapi.command.takemoney") && self::translate("main-menu.buttons.subtract")) $buttons[] = new MenuOption(self::translate("main-menu.buttons.subtract"));
        if($player->hasPermission("economyapi.command.setmoney") && self::translate("main-menu.buttons.set")) $buttons[] = new MenuOption(self::translate("main-menu.buttons.set"));

        $player->sendForm(new MenuForm(
            self::translate("main-menu.title"),
            str_replace("{money}", EconomyAPI::getInstance()->myMoney($player), self::translate("main-menu.content")),
            $buttons,
            function(Player $player, int $button)use($buttons): void {
                $button = $buttons[$button]->getText();
                switch($button) {
                    case self::translate("main-menu.buttons.my"):
                        if(!$player->hasPermission("economyapi.command.mymoney")) {
                            self::sendMessage($player, self::translate("no-perm"));
                            return;
                        }
                        self::sendMessage($player, str_replace("{money}", EconomyAPI::getInstance()->myMoney($player), self::translate("mymoney-menu.content")));
                        break;
                    case self::translate("main-menu.buttons.look"):
                        if(!$player->hasPermission("economyapi.command.seemoney")) {
                            self::sendMessage($player, self::translate("no-perm"));
                            return;
                        }
                        self::seeMoneyMenu($player);
                        break;
                    case self::translate("main-menu.buttons.pay"):
                        if(!$player->hasPermission("economyapi.command.pay")) {
                            self::sendMessage($player, self::translate("no-perm"));
                            return;
                        }
                        self::payMoneyMenu($player);
                        break;
                    case self::translate("main-menu.buttons.top"):
                        if(!$player->hasPermission("economyapi.command.topmoney")) {
                            self::sendMessage($player, self::translate("no-perm"));
                            return;
                        }
                        self::topMoneyMenu($player);
                        break;
                    case self::translate("main-menu.buttons.add"):
                        if(!$player->hasPermission("economyapi.command.givemoney")) {
                            self::sendMessage($player, self::translate("no-perm"));
                            return;
                        }
                        self::addMoneyMenu($player);
                        break;
                    case self::translate("main-menu.buttons.subtract"):
                        if(!$player->hasPermission("economyapi.command.takemoney")) {
                            self::sendMessage($player, self::translate("no-perm"));
                            return;
                        }
                        self::subtractMoneyMenu($player);
                        break;
                    case self::translate("main-menu.buttons.set"):
                        if(!$player->hasPermission("economyapi.command.setmoney")) {
                            self::sendMessage($player, self::translate("no-perm"));
                            return;
                        }
                        self::setMoneyMenu($player);
                        break;
                }
            }
        ));
    }

    public static function seeMoneyMenu(Player $player) {
        $players = [];
        // somewhy its not working with just using array map
        foreach(array_map(function($n){return $n->getName();},self::$i->getServer()->getOnlinePlayers()) as $x) {
            $players[] = $x;
        }
        $player->sendForm(new CustomForm(self::translate("seemoney-menu.title"), [
            new Dropdown("player", self::translate("seemoney-menu.content"), $players)
        ], function(Player $player, CustomFormResponse $response)use($players): void{
            $selected = $players[$response->getInt("player")];
            self::sendMessage($player, str_replace(["{player}", "{money}"], [$selected, EconomyAPI::getInstance()->myMoney($selected) ?? 0], self::translate("seemoney-menu.result")));
        }));
    }

    public static function payMoneyMenu(Player $player) {
        $players = [];
        // somewhy its not working with just using array map
        foreach(array_map(function($n){return $n->getName();},self::$i->getServer()->getOnlinePlayers()) as $x) {
            if($x != $player->getName())$players[] = $x;
        }
        if(empty($players)) {
            self::sendMessage($player, self::translate("paymoney-menu.error-warn"));
            return;
        }
        $player->sendForm(new CustomForm(
            self::translate("paymoney-menu.title"),
            [
                new Dropdown("player", self::translate("paymoney-menu.content"), $players),
                new Input("money", self::translate("paymoney-menu.content1"), "10")
            ], function(Player $player, CustomFormResponse $response)use($players): void{
                $selected = $players[$response->getInt("player")];
                $money = $response->getString("money");
                if(!is_numeric($money) || (int)$money <= 0) {
                    self::sendMessage($player, self::translate("paymoney-menu.error"));
                    return;
                }
                $money = (float)$money;
                if($money > EconomyAPI::getInstance()->myMoney($player)) {
                    self::sendMessage($player, self::translate("paymoney-menu.error1"));
                    return;
                }
                if(strtolower($player->getName()) == strtolower($selected)) {
                    self::sendMessage($player, self::translate("paymoney-menu.error2"));
                    return;
                }
                EconomyAPI::getInstance()->addMoney($selected, $money);
                EconomyAPI::getInstance()->reduceMoney($player->getName(), $money);
                self::sendMessage($player, str_replace(["{player}", "{money}"], [$selected, $money], self::translate("paymoney-menu.success")));
                if(self::$i->getServer()->getPlayerExact($selected))self::sendMessage(self::$i->getServer()->getPlayerExact($selected), str_replace(["{player}", "{money}"], [$player->getName(), $money], self::translate("paymoney-menu.success1")));
        }));
    }

    public static function topMoneyMenu(Player $player, int $page = 1) {
        $result = "";
        $moneys = [];
        foreach(self::getTopMoneys() as $p => $m) {
            $moneys[] = [$p, $m];
        }
        $pages = array_chunk($moneys, (int)self::translate("topmoney-menu.page-size"));
        if(!isset($pages[$page-1])) $page = 1;
        foreach($pages[$page-1] as $dat){
            $pl = $dat[0];
            $money = $dat[1];
            $league = array_search($pl, array_keys(self::getTopMoneys()))+1;
            $result .= str_replace(["{league}", "{player}", "{money}"], [$league, $pl, $money], self::translate("topmoney-menu.format"))."\n";
        }
        $buttons = [];
        if(count($pages) < $page) {
            $buttons = [new MenuOption(self::translate("topmoney-menu.previous"))];
        }
        if($page < count($pages)) {
            $buttons = [new MenuOption(self::translate("topmoney-menu.next"))];
        }
        if(count($pages) != 1){
            $buttons[] = new MenuOption(self::translate("topmoney-menu.previous"));
            $buttons[] = new MenuOption(self::translate("topmoney-menu.next"));
        }
        $buttons[] = new MenuOption(self::translate("topmoney-menu.main-menu"));
        $player->sendForm(new MenuForm(
            str_replace(["{page}", "{maxpage}"], [$page, count($pages)], self::translate("topmoney-menu.title")),
            $result, $buttons,
            function (Player $player, int $t)use($buttons,$page): void {
                switch($buttons[$t]->getText()) {
                    case self::translate("topmoney-menu.previous"):
                        self::topMoneyMenu($player, $page-1);
                        break;
                    case self::translate("topmoney-menu.next"):
                        self::topMoneyMenu($player, $page+1);
                        break;
                    case self::translate("topmoney-menu.main-menu"):
                        self::mainMenu($player);
                        break;
                }
        }));
    }

    public static function addMoneyMenu(Player $player) {
        $players = [];
        // somewhy its not working with just using array map
        foreach(array_map(function($n){return $n->getName();},self::$i->getServer()->getOnlinePlayers()) as $x) {
            $players[] = $x;
        }
        $player->sendForm(new CustomForm(
            self::translate("addmoney-menu.title"),
            [
                new Dropdown("player", self::translate("addmoney-menu.content"), $players),
                new Input("money", self::translate("addmoney-menu.content1"), "10")
            ], function(Player $player, CustomFormResponse $response)use($players): void{
            $selected = $players[$response->getInt("player")];
            $money = $response->getString("money");
            if(!is_numeric($money)) {
                self::sendMessage($player, self::translate("addmoney-menu.error"));
                return;
            }
            $money = (float)$money;
            EconomyAPI::getInstance()->addMoney($selected, $money);
            self::sendMessage($player, str_replace(["{player}", "{money}"], [$selected, $money], self::translate("addmoney-menu.success")));
        }));
    }

    public static function subtractMoneyMenu(Player $player) {
        $players = [];
        // somewhy its not working with just using array map
        foreach(array_map(function($n){return $n->getName();},self::$i->getServer()->getOnlinePlayers()) as $x) {
            $players[] = $x;
        }
        $player->sendForm(new CustomForm(
            self::translate("subtractmoney-menu.title"),
            [
                new Dropdown("player", self::translate("subtractmoney-menu.content"), $players),
                new Input("money", self::translate("subtractmoney-menu.content1"), "10")
            ], function(Player $player, CustomFormResponse $response)use($players): void{
            $selected = $players[$response->getInt("player")];
            $money = $response->getString("money");
            if(!is_numeric($money)) {
                self::sendMessage($player, self::translate("subtractmoney-menu.error"));
                return;
            }
            $money = (float)$money;
            EconomyAPI::getInstance()->reduceMoney($selected, $money);
            self::sendMessage($player, str_replace(["{player}", "{money}"], [$selected, $money], self::translate("subtractmoney-menu.success")));
        }));
    }

    public static function setMoneyMenu(Player $player) {
        $players = [];
        // somewhy its not working with just using array map
        foreach(array_map(function($n){return $n->getName();},self::$i->getServer()->getOnlinePlayers()) as $x) {
            $players[] = $x;
        }
        $player->sendForm(new CustomForm(
            self::translate("setmoney-menu.title"),
            [
                new Dropdown("player", self::translate("setmoney-menu.content"), $players),
                new Input("money", self::translate("setmoney-menu.content1"), "10")
            ], function(Player $player, CustomFormResponse $response)use($players): void{
            $selected = $players[$response->getInt("player")];
            $money = $response->getString("money");
            if(!is_numeric($money)) {
                self::sendMessage($player, self::translate("setmoney-menu.error"));
                return;
            }
            $money = (float)$money;
            EconomyAPI::getInstance()->setMoney($selected, $money);
            self::sendMessage($player, str_replace(["{player}", "{money}"], [$selected, $money], self::translate("setmoney-menu.success")));
        }));
    }

    private static function sendMessage(Player $player, string $message) {
        $player->sendForm(new MenuForm("", $message, [new MenuOption(self::translate("ok"))], function(Player $player, int $t): void{}));
    }

    private static function getTopMoneys(): array {
        $moneys = EconomyAPI::getInstance()->getAllMoney();
        arsort($moneys);
        return $moneys;
    }

    /**
     * @param string $key
     * @return array|string|string[]
     */
    public static function translate(string $key) {
        return str_replace("{line}", "\n", ((new Config(self::$i->getDataFolder()."messages.yml"))->getNested($key) ?? ""));
    }
}