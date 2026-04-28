<?php

namespace Sterzik\ArchivePostprocessor;

use Exception;

class Flattener
{
    public function flattenDir(string $dir, bool $recursive = false, array $ignoredFiles = []): bool
    {
        do {
            try {
                $ret = $this->doFlattenDir($dir, $ignoredFiles);
            } catch (Exception $e) {
                fprintf(STDERR, "Error: %s\n", $e->getMessage());
                $ret = false;
            }
        } while ($recursive && $ret);

        return true;
    }

    private function doFlattenDir(string $dir, array $ignoredFiles): bool
    {
        $subdir = DirectoryLister::getSingleSubDirectory($dir, $ignoredFiles);
        if ($subdir === null) {
            return false;
        }

        $ignoredFiles = array_fill_keys($ignoredFiles, true);

        $ncSubdir = "";
        $useNcSubdir = false;
        foreach (DirectoryLister::listDirectory($dir . "/" . $subdir) as $file) {
            if (isset($ignoredFiles[$file])) {
                return false;
            }
            if ($file === $subdir) {
                $useNcSubdir = true;
            }
            if (strlen($file) >= strlen($ncSubdir) && strtolower(substr($file, 0, strlen($ncSubdir))) === strtolower($ncSubdir)) {
                $forbiddenChar = substr($file, strlen($ncSubdir), 1);
                $appendChar = $this->randomCharExcept(($forbiddenChar === "") ? null : strtolower($forbiddenChar));
                $ncSubdir .= $appendChar;
            }
        }

        if ($useNcSubdir) {
            if (!@rename($dir . "/" . $subdir, $dir . "/" . $ncSubdir)) {
                return false;
            }
            $subdir = $ncSubdir;
        }
        foreach (DirectoryLister::listDirectory($dir . "/" . $subdir) as $file) {
            if (!@rename($dir . "/" . $subdir . "/" . $file, $dir . "/" . $file)) {
                return false;
            }
        }
        if (!@rmdir($dir . "/" . $subdir)) {
            return false;
        }
        return true;
    }

    private function randomCharExcept(?string $char)
    {
        $ord = $this->charToOrd($char);
        $newOrd = rand(0, 34 + ($char !== null) ? 0 : 1);
        if ($ord !== null && $newOrd >= $ord) {
            $newOrd++;
        }
        $newChar = $this->ordToChar($newOrd);
        if ($char !== null && $newChar === $char) {
            throw new Exception("Bug occured!");
        }
        return $newChar;
    }

    private function charToOrd(?string $char): ?int
    {
        if ($char === null) {
            return null;
        }
        $ord = $this->testCharToOrd($char, '0', 10);
        if ($ord !== null) {
            return $ord;
        }
        $ord = $this->testCharToOrd($char, 'a', 26);
        if ($ord !== null) {
            return $ord + 10;
        }
        return null;
    }

    private function testCharToOrd(string $char, string $baseChar, int $len): ?int
    {
        $n = ord($char) - ord($baseChar);
        if ($n < 0 || $n >= $len) {
            return null;
        }
        return $n;
    }

    private function ordToChar(int $ord): string
    {
        if ($ord < 0 || $ord > 35) {
            throw new Exception("Bug occured!");
        }
        if ($ord < 10) {
            return chr($ord + ord('0'));
        } else {
            return chr($ord - 10 + ord('a'));
        }
    }
}
