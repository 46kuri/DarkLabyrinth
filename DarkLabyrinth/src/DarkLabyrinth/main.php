<?php

namespace Darklabyrinth;

use pocketmine\item\Item;

use pocketmine\block\Block;

use pocketmine\event\Listener;

use pocketmine\{Player,server};

use pocketmine\plugin\PluginBase;

use pocketmine\inventory\ChestInventory;

use pocketmine\event\block\BlockBreakEvent;

use pocketmine\entity\{Effect,EffectInstance};

use pocketmine\event\inventory\InventoryCloseEvent;

use pocketmine\command\{Command,CommandSender,CommandExecutor};

use pocketmine\item\enchantment\{Enchantment,EnchantmentInstance};

use pocketmine\scheduler\{Task,PluginTask,ClosureTask,CallbackTask};

use pocketmine\event\player\{PlayerInteractEvent,PlayerJoinEvent,PlayerQuitEvent,PlayerItemHeldEvent};

class main extends PluginBase implements Listener{
	
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->notice("Darklabyrinthプラグインは使用可能です。お楽しみください 製作者:siropan");
        $this->Killer = null;
    }

    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        $event->setQuitMessage($name . " Quit Darklabyrinth");
    }

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        $event->setJoinMessage($name . " join Darklabyrinth");
        $player->getInventory()->clearAll();
        if($player->isOp()){
            $item = Item::get(Item::WRITTEN_BOOK, 0, 1);
            $item->setTitle("OP用ガイドbook");
            $item->setPageText(0, "ゲームを始めるにあたってのコマンド順序\n/killer\n/timer 土日限定で開きます");
            $item->setAuthor("sirokuripan");
            $player->getInventory()->addItem($item);
        }else{
            $item = Item::get(Item::WRITTEN_BOOK, 0, 1);
            $item->setTitle("ようこそDarkLabytinthへ");
            $item->setPageText(0, "当鯖は土日限定の鯖です\n\nサバイバー側の進め方\n\nまずはゲームが始まったら\n 金ブロックを見つけクリック\n交換アイテムが手に入るのでエメラルドブロックを見つけクリック\n\n次のページへ");
            $item->setPageText(1, "鍵が手に入るのでダイヤモンドブロックを見つけ\nエメラルドブロック同様にクリックすることで脱出\nちなみにTNTブロックやレッドストーンブロックが置いてあるが\nそれは生存者にとってきっと有利になるであろう...\n(一回の使用につきクールタイムが存在します)");
            $item->setPageText(2, "キラー側の進め方\n\nサバイバーを見つけたら己の能力を駆使しサバイバーの全滅を目指せ\nサバイバー同様TNTブロッククリックで\n特殊効果付与");
            $item->setAuthor("siropan");
            $player->getInventory()->addItem($item);
        }
    }

    public function onTouch(PlayerInteractEvent $event){
        $id = $event->getItem()->getId();
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        $block = $event->getBlock();
        if($player->getInventory()->getItemInHand()->getCustomName() == "隠れ玉"){
            $name = $player->getName();
            if(isset($this->flag[$name])){
                $event->setCancelled();
                $player->sendPopup("クールタイム中は特殊効果を得られません");
            return;
            }
            $this->flag[$name] = true;
            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                function (int $currentTick) 
                    use ($name): void {
                        unset($this->flag[$name]);
                    }
            ), 15 * 15);
            $player->addEffect(new EffectInstance(Effect::getEffect(14), 400, 400, true));
            $player->getInventory()->removeItem($item);
        }

        if($player->getInventory()->getItemInHand()->getCustomName() == "回復玉"){
            $name = $player->getName();
            if(isset($this->flag[$name])){
                $event->setCancelled();
                $player->sendPopup("クールタイム中は特殊効果を得られません");
            return;
            }
            $this->flag[$name] = true;
            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                function (int $currentTick) 
                    use ($name): void {
                        unset($this->flag[$name]);
                    }
            ), 15 * 15);
            $health = $player->getHealth();
            $player->setHealth($health + 10);
            $player->getInventory()->removeItem($item);
        }
        
        if($block->getID() == 133/*EmeraldBlock*/){
            if($player->getDisplayName() == "キラー"){
                $event->setCancelled();
                $player->sendMessage("あなたはキラーなので何ももらえません");
            }else{
                if($player->getInventory()->getItemInHand()->getCustomName() == "交換アイテム"){
                    $event->setCancelled();
                    $player->getInventory()->addItem(Item::get(119/*End Portal*/, 0/*メタ値*/, 1/*個数*/)->setCustomName("鍵"));
                    $player->addTitle("§e鍵を入手しました\nキラーに気を付けながら脱出しましょう");
                }
            }
        }

        if($block->getID() == 41/*GoldBlock*/){
            if($player->getDisplayName() == "キラー"){
                $event->setCancelled();
                $player->sendMessage("あなたはキラーなので何ももらえません");
            }else{
                $player->getInventory()->addItem(Item::get(76, 0, 1)->setCustomName("交換アイテム"));
                $event->setCancelled();
                $player->addTitle("§eキラーに気を付けながら\n交換アイテムをもって鍵を入手しましょう");
            }
        }

        if($block->getID() == 152/*Redstone Block*/){
            $name = $player->getName();
            if(isset($this->flag[$name])){
                $event->setCancelled();
                $player->sendPopup("クールタイム中は入手できません");
            return;
            }
            $this->flag[$name] = true;
            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                function (int $currentTick) 
                    use ($name): void {
                        unset($this->flag[$name]);
                    }
                ), 15 * 15);
            $player->getInventory()->addItem(Item::get(320, 0, 10)->setCustomName("エネルギーチャージ"));
            $player->addTitle("§e空腹用のアイテムを配布しました");
        }

        if($block->getID() == 57/*DiamondBlock*/){
            if($player->getDisplayName() == "キラー"){
                $event->setCancelled();
                $player->sendMessage("あなたはキラーなので何ももらえません");
            }else{
                if($player->getInventory()->getItemInHand()->getCustomName() == "鍵"){
                    $player->setGamemode(3);
                    $player->addTitle("§a脱出成功");
                    $player->getInventory()->clearAll();
                }
            }
        }

        if($block->getID() == 46/*TNT*/){
            $player = $event->getPlayer();
            if($player->getDisplayName() == "キラー"){
                $player->addEffect(new EffectInstance(Effect::getEffect(5), 400, 400, true));
                $player->addTitle("§c特殊効果 : §b攻撃力上昇 ");
            }else{
                $math = mt_rand(1,4);
                switch ($math) {
                    case "1":
                        $name = $player->getName();
                        if(isset($this->flag[$name])){
                            $event->setCancelled();
                            $player->sendPopup("クールタイム中は特殊効果を得られません");
                        return;
                        }
                        $this->flag[$name] = true;
                        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                            function (int $currentTick) 
                                use ($name): void {
                                    unset($this->flag[$name]);
                                }
                        ), 15 * 15);
                        $player->addEffect(new EffectInstance(Effect::getEffect(1), 100, 20, true));
                        $player->addTitle("§c特殊効果 : §b移動速度上昇 ");
                    break;

                    case "2":
                        $name = $player->getName();
                        if(isset($this->flag[$name])){
                            $event->setCancelled();
                            $player->sendPopup("クールタイム中は特殊効果を得られません");
                        return;
                        }
                        $this->flag[$name] = true;
                        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                            function (int $currentTick) 
                                use ($name): void {
                                    unset($this->flag[$name]);
                                }
                        ), 15 * 15);
                        $player->setMaxHealth(40);
                        $player->setHealth(40);
                        $player->addTitle("§c特殊効果 : §b体力上昇 ");
                    break;

                    case "3":
                        $name = $player->getName();
                        if(isset($this->flag[$name])){
                            $event->setCancelled();
                            $player->sendPopup("クールタイム中は特殊効果を得られません");
                        return;
                        }
                        $this->flag[$name] = true;
                        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                            function (int $currentTick) 
                                use ($name): void {
                                    unset($this->flag[$name]);
                                }
                        ), 15 * 15);
                        $item = Item::get(402/*Firework Star*/, 0/*メタ値*/, 1/*個数*/);
                        $item->setCustomName("隠れ玉");
                        $player->getInventory()->addItem($item);
                        $player->addTitle("§c特殊効果 : §b隠れ玉付与 ");
                    break;

                    case "4":
                        $name = $player->getName();
                        if(isset($this->flag[$name])){
                            $event->setCancelled();
                            $player->sendPopup("クールタイム中は特殊効果を得られません");
                        return;
                        }
                        $this->flag[$name] = true;
                        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                            function (int $currentTick) 
                                use ($name): void {
                                    unset($this->flag[$name]);
                                }
                        ), 15 * 15);
                        $item = Item::get(402/*Firework Star*/, 0/*メタ値*/, 1/*個数*/);
                        $item->setCustomName("回復玉");
                        $player->getInventory()->addItem($item);
                        $player->addTitle("§c特殊効果 : §b回復玉付与 ");
                    break;
                }
            }
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        switch(strtolower($command->getName())){

            /*OP用　
                ゲームを始めるにあたってのコマンド順序
                /killer , /timer
            */

            case "killer":
                if($sender->isOp()){
                    $players = $this->getServer()->getOnlinePlayers();
                    $key = array_rand($players);
                    $player = $players[$key];
                    $name = $player->getName();
                    $this->Killer = $player;
                    $this->getServer()->broadcastMessage($name."§dさんがキラーになりました");
                    $player->setDisplayName("キラー");
                }else{
                    $sender->sendMessage("§b権限者のみが使用できます");
                }
            return true;

            case "timer":
                if($sender->isOp()){
                    $this->getServer()->broadcastMessage("§dタイマーを開始しました");
                    $players = $this->getServer()->getOnlinePlayers();
                    foreach ($players as $player){
                        $time = 500;
                        while($time > 0){
                            $time--;
                        }

                        if($time = 50){
                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function (int $currentTick) use ($player): void {
                                    $this->getServer()->broadcastMessage("§dゲーム終了まであと450秒");
                                }
                            ), 20 * $time);
                        }
                        if($time = 100){
                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function (int $currentTick) use ($player): void {
                                    $this->getServer()->broadcastMessage("§dゲーム終了まであと400秒");
                                }
                            ), 20 * $time);
                        }

                        if($time = 200){
                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function (int $currentTick) use ($player): void {
                                    $this->getServer()->broadcastMessage("§dゲーム終了まであと300秒");
                                }
                            ), 20 * $time);
                            }

                        if($time = 300){
                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function (int $currentTick) use ($player): void {
                                    $this->getServer()->broadcastMessage("§dゲーム終了まであと200秒");
                                }
                            ), 20 * $time);
                        }

                        if($time = 400){
                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function (int $currentTick) use ($player): void {
                                    $this->getServer()->broadcastMessage("§dゲーム終了まであと100秒");
                                }
                            ), 20 * $time);
                        }

                        if($time = 450){
                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function (int $currentTick) use ($player): void {
                                    $this->getServer()->broadcastMessage("§dゲーム終了まであと50秒");
                                }
                            ), 20 * $time);
                        }

                        if($time = 490){
                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function (int $currentTick) use ($player): void {
                                    $this->getServer()->broadcastMessage("§dゲーム終了まであと10秒");
                                }
                            ), 20 * $time);
                        }

                        if($time = 491){
                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function (int $currentTick) use ($player): void {
                                    $this->getServer()->broadcastMessage("§dゲーム終了まであと9秒");
                                }
                            ), 20 * $time);
                        }

                        if($time = 492){
                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function (int $currentTick) use ($player): void {
                                    $this->getServer()->broadcastMessage("§dゲーム終了まであと8秒");
                                }
                            ), 20 * $time);
                        }

                        if($time = 493){
                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function (int $currentTick) use ($player): void {
                                    $this->getServer()->broadcastMessage("§dゲーム終了まであと7秒");
                                }
                            ), 20 * $time);
                        }

                        if($time = 494){
                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function (int $currentTick) use ($player): void {
                                    $this->getServer()->broadcastMessage("§dゲーム終了まであと6秒");
                                }
                            ), 20 * $time);
                        }

                        if($time = 495){
                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function (int $currentTick) use ($player): void {
                                    $this->getServer()->broadcastMessage("§dゲーム終了まであと5秒");
                                }
                            ), 20 * $time);
                        }

                        if($time = 496){
                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function (int $currentTick) use ($player): void {
                                    $this->getServer()->broadcastMessage("§dゲーム終了まであと4秒");
                                }
                            ), 20 * $time);
                        }

                        if($time = 497){
                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function (int $currentTick) use ($player): void {
                                    $this->getServer()->broadcastMessage("§dゲーム終了まであと3秒");
                                }
                            ), 20 * $time);
                        }

                        if($time = 498){
                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function (int $currentTick) use ($player): void {
                                    $this->getServer()->broadcastMessage("§dゲーム終了まであと2秒");
                                }
                            ), 20 * $time);
                        }

                        if($time = 499){
                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function (int $currentTick) use ($player): void {
                                    $this->getServer()->broadcastMessage("§dゲーム終了まであと1秒");
                                }
                            ), 20 * $time);
                        }

                        if($time = 500){
                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function (int $currentTick) use ($player): void {
                                    $this->getServer()->broadcastMessage("§dゲーム終了");
                                }
                            ), 20 * $time);
                        }
                    }
                }else{
                    $sender->sendMessage("§b権限者のみが使用できます");
                }
            return true;
        }
    }
}