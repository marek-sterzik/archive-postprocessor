<?php

namespace Sterzik\ArchivePostprocessor\Unpacker;

use Sterzik\ArchivePostprocessor\DirectoryLister;

class ZipUnpacker extends AbstractUnpacker
{
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
        $this->unpackCommand("unzip -d %s -- %s", $directory, $filename);
        $this->unpackCommand("jar xf %s -C %s", $filename, $directory);
        return false;
    }
}
