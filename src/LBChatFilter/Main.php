<?php

namespace LBChatFilter;

use ChatFilter\ChatFilter;
use ChatFilter\ChatFilterTask;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerChatEvent;

/**
 * Main antihacks plugin class
 */
class Main extends PluginBase implements Listener {

    /**
     * The main users array
     *
     * @var array
     */
    public $users = [];

    /**
     * Loads the plugin
     *
     * @return null
     */
    public function onLoad() {
        $this->getLogger()->info(TextFormat::WHITE . "Loaded");
    }

    /**
     * Enables the plugin
     *
     * @return null
     */
    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
        $this->reloadConfig();

        /**
         * Disable the plugin if it's disabled in the plugin
         */
        if($this->getConfig()->get('lbchatfilter') == false) {
            $this->setEnabled(false);
            return;
        }

        foreach($this->getConfig()->get('users') as $v => $k) {
            $this->users[] = $k;
        }

        /**
         * Initalize the ChatFilter
         *
         * @type ChatFilter
         */
        $this->filter = new ChatFilter();

        $this->getServer()->getScheduler()->scheduleRepeatingTask(new ChatFilterTask($this), 30);

        $this->getLogger()->info(TextFormat::DARK_GREEN . "Enabled");
    }

    /**
     * Handles the commands sent to the plugin
     *
     * @param  CommandSender $sender  The person issuing the command
     * @param  Command       $command The command object
     * @param  string        $label   The command label
     * @param  array         $args    An array of arguments
     * @return boolean                True allows the command to go through, false sends an error
     */
    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        $subcommand = strtolower(array_shift($args));
        switch ($subcommand) {
            case "adduser":
                if(isset($args[0])) {
                    if(($player = $this->getServer()->getPlayerExact($args[0])) instanceof Player) {
                        $this->users[] = $player->getDisplayName();
                    } else {
                        $this->users[] = $args[0];
                    }
                    $sender->sendMessage(TextFormat::BLUE . '[LBChatFilter] Added user: ' . $args[0]);
                    return true;
                } else {
                    return false;
                }
                break;
            case "remuser":
                if(isset($args[0])) {
                    if(($player = $this->getServer()->getPlayerExact($args[0])) instanceof Player) {
                        if(($key = array_search($player->getDisplayName(), $this->users)) !== false)
                            unset($this->users[$key]);
                    } else {
                        if(($key = array_search($args[0], $this->users)) !== false)
                            unset($this->users[$key]);
                    }
                    $sender->sendMessage(TextFormat::RED . '[LBChatFilter] Removed user: ' . $args[0]);
                    return true;
                } else {
                    return false;
                }
                break;
            case "listusers":
                $sender->sendMessage(TextFormat::GREEN . '[LBChatFilter] Users on the whitelist: ' . implode(', ', $this->users));
                return true;
                break;
            case "help":
                $sender->sendMessage(TextFormat::GREEN . '[LBChatFilter] Available commands: adduser, listusers, remuser');
                return true;
                break;
            default:
                return false;
        }
    }

    /**
     * Runs the actual check
     *
     * @param  PlayerChatEvent $event The event
     * @return null                   Nothing to return
     */
    public function onPlayerChat(PlayerChatEvent $event) {
        if (!in_array($event->getPlayer()->getDisplayName(), $this->users) && !$this->filter->check($event->getPlayer(), $event->getMessage())) {
            $event->setCancelled(true);
            $event->getPlayer()->sendMessage(TextFormat::RED . "[LBCF] I'm sorry, I can't let you say that.");
        }
    }

    /**
     * Disables the plguin
     *
     * @return null
     */
    public function onDisable() {
        $this->getConfig()->set('users', $this->users);
        $this->getConfig()->save();

        $this->getLogger()->info(TextFormat::DARK_RED . "Disabled");
    }
}
