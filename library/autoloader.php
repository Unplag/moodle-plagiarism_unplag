<?php

namespace plagiarism_unplag\library;

use SplFileInfo;

/**
 * Class unplag_autoloader
 * @package plagiarism_unplag\library
 */
class unplag_autoloader {
    /**
     * @var array
     */
    private static $excludefiles = [
        'autoloader.php',
    ];

    /**
     * @param $directorypath
     */
    public static function init($directorypath) {
        $directory = new \RecursiveDirectoryIterator($directorypath);
        foreach (new \RecursiveIteratorIterator($directory) as $info) {
            /** @var SplFileInfo $info */
            if (in_array($info->getFilename(), self::$excludefiles) || $info->getExtension() != 'php') {
                continue;
            }

            require_once($info->getPathname());
        }
    }
}