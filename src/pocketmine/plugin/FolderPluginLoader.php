<?php

/*
 *
 *  _____           _        _ _______ _____
 * |  __ \         | |      | |__   __|  __ \
 * | |__) |__   ___| | _____| |_ | |  | |__) |
 * |  ___/ _ \ / __| |/ / _ \ __|| |  |  _  /
 * | |  | (_) | (__|   <  __/ |_ | |  | | \ \
 * |_|   \___/ \___|_|\_\___|\__||_|  |_|  \_\
 *
*/

declare(strict_types=1);

namespace pocketmine\plugin;

use function file_exists;
use function file_get_contents;
use function is_dir;

class FolderPluginLoader implements PluginLoader{

    private $loader;

    /**
     * FolderPluginLoader constructor.
     * @param \ClassLoader $loader
     */
    public function __construct(\ClassLoader $loader){
        $this->loader = $loader;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function canLoadPlugin(string $path) : bool{
        return is_dir($path) and file_exists($path . "/plugin.yml") and file_exists($path . "/src/");
    }

    /**
     * @param string $file
     */
    public function loadPlugin(string $file) : void{
        $this->loader->addPath("$file/src");
    }

    /**
     * @param string $file
     * @return PluginDescription|null
     */
    public function getPluginDescription(string $file) : ?PluginDescription{
        if(is_dir($file) and file_exists($file . "/plugin.yml")){
            $yaml = @file_get_contents($file . "/plugin.yml");
            if($yaml != ""){
                return new PluginDescription($yaml);
            }
        }

        return null;
    }

    public function getAccessProtocol() : string{
        return "";
    }
}
