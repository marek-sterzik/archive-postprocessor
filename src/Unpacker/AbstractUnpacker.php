<?php

namespace Sterzik\ArchivePostprocessor\Unpacker;

abstract class AbstractUnpacker
{
    abstract public function getExtensions(): array;
    abstract public function checkFile(string $filename): bool;
    abstract public function unpack(string $filename, string $directory): bool;

    protected function mimeCheck(string $filename, string $mime): bool
    {
        $command = "file --mime-type -b -- " . escapeshellarg($filename);
        $output = "";
        $result = 1;
        if (exec($command, $output, $result) === false) {
            return false;
        }
        if ($result !== 0) {
            return false;
        }
        $output = trim(implode("\n", $output));

        if (strtolower($output) !== strtolower($mime)) {
            return false;
        }

        return true;
    }
}
