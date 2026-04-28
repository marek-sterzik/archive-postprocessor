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

    public static function getSingleSubDirectory(string $dir, array &$ignoredFiles): ?string
    {
        $ignoredFilesFound = [];
        $ignoredFiles = array_fill_keys($ignoredFiles, true);
        $dirFound = null;
        foreach (self::listDirectory($dir) as $file) {
            if (isset($ignoredFiles[$file])) {
                $ignoredFilesFound[$file] = true;
                continue;
            }
            if ($dirFound !== null) {
                return null;
            }
            if (!is_dir($dir . "/" . $file)) {
                return null;
            }
            $dirFound = $file;
        }
        $ignredFiles = array_keys($ignoredFilesFound);
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
