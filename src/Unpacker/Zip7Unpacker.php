<?php

namespace Sterzik\ArchivePostprocessor\Unpacker;

class Zip7Unpacker extends AbstractUnpacker
{
    public function getExtensions(): array
    {
        return ['.7z'];
    }

    public function checkFile(string $filename): bool
    {
        return $this->mimeCheck($filename, "application/x-7z-compressed");
    }

    public function unpack(string $filename, string $directory): bool
    {
        $this->unpackCommand("7z x -o%s -- %s", $directory, $filename);
        return false;
    }
}
