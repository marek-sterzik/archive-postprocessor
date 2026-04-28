<?php

namespace Sterzik\ArchivePostprocessor;

use Generator;
use Exception;

class DirectoryLister
{
    public static function listDirectory(string $dir): Generator
    {
        $dd = opendir($dir);
        if (!$dd) {
            throw new Exception(sprintf("Cannot open directory: %s", $dir));
        }
        while ($file = readdir($dd)) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            yield $file;
        }
        closedir($dd);
    }

    public static function getSingleSubDirectory(string $dir): ?string
    {
        $dirFound = null;
        foreach (self::listDirectory($dir) as $file) {
            if ($dirFound !== null) {
                return null;
            }
            if (!is_dir($dir . "/" . $file)) {
                return null;
            }
            $dirFound = $file;
        }
        return $dirFound;
    }

    public static function isEmpty(string $dir): bool
    {
        foreach (self::listDirectory($dir) as $file) {
            return false;
        }
        return true;
    }
}
