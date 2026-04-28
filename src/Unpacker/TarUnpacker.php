<?php

namespace Sterzik\ArchivePostprocessor\Unpacker;

use Sterzik\ArchivePostprocessor\DirectoryLister;

class TarUnpacker extends AbstractUnpacker
{
    const EXTENSIONS = [
        ".tar" => "",
        ".tgz" => "-z",
        ".tar.gz" => "-z",
        ".tar.bz2" => "-j",
        ".tbz2" => "-j",
        ".tar.xz" => "-J",
        ".txz" => "-J",
    ];

    const MIME_TYPES = [
        "" => "application/x-tar",
        "-z" => "application/gzip",
        "-j" => "application/x-bzip2",
        "-J" => "application/x-xz",
    ];

    public function getExtensions(): array
    {
        return array_keys(self::EXTENSIONS);
    }

    public function checkFile(string $filename): bool
    {
        $flag = self::EXTENSIONS[$this->getExtension()] ?? null;

        if ($flag === null) {
            return false;
        }

        $mime = self::MIME_TYPES[$flag] ?? null;

        if ($mime === null) {
            return false;
        }

        if (!$this->mimeCheck($filename, $mime)) {
            return false;
        }

        if ($flag !== '' && !$this->mimeCheck($filename, 'application/x-tar', true)) {
            return false;
        }

        return true;
    }

    public function unpack(string $filename, string $directory): bool
    {
        $flag = self::EXTENSIONS[$this->getExtension()] ?? null;

        if ($flag === null) {
            return false;
        }
        
        $this->unpackCommand("tar -xf %s %s -C %s", $filename, $flag, $directory);
        return false;
    }
}

