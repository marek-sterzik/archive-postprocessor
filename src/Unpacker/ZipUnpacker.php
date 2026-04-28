<?php

namespace Sterzik\ArchivePostprocessor\Unpacker;

use Sterzik\ArchivePostprocessor\DirectoryLister;

class ZipUnpacker extends AbstractUnpacker
{
    const UNPACKERS = ["unpackWithUnzip", "unpackWithJar"];

    public function getExtensions(): array
    {
        return ['.zip'];
    }

    public function checkFile(string $filename): bool
    {
        return $this->mimeCheck($filename, "application/zip");
    }

    public function unpack(string $filename, string $directory): bool
    {
        foreach (self::UNPACKERS as $unpacker) {
            $result = $this->$unpacker($filename, $directory);
            if ($result) {
                return true;
            }
            if (!DirectoryLister::isEmpty($directory)) {
                return false;
            }
        }
        return false;
    }

    private function unpackWithUnzip(string $filename, string $directory): bool
    {
        $command = sprintf("unzip -d %s -- %s", escapeshellarg($directory), escapeshellarg($filename));
        $result = 1;
        if (system($command, $result) === false) {
            return false;
        }
        if ($result !== 0) {
            return false;
        }
        return true;
    }

    private function unpackWithJar(string $filename, string $directory): bool
    {
        $command = sprintf("jar xf %s -C %s", escapeshellarg($filename), escapeshellarg($directory));
        $result = 1;
        if (system($command, $result) === false) {
            return false;
        }
        if ($result !== 0) {
            return false;
        }
        return true;
    }
}
