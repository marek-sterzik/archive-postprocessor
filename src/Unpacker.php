<?php

namespace Sterzik\ArchivePostprocessor;

use ReflectionClass;

class Unpacker
{
    const UNPACKERS = [Unpacker\ZipUnpacker::class];
    
    public function unpack(string $dir): bool
    {
        $unpackers = $this->createUnpackers();

        foreach(DirectoryLister::listDirectory($dir) as $file) {
            $path = $dir . "/" . $file;
            if (!is_file($path)) {
                continue;
            }
            $fileLc = strtolower($file);
            foreach ($unpackers as $extension => $unpacker) {
                if (strlen($fileLc) >= strlen($extension) && substr($fileLc, -strlen($extension)) === $extension) {
                    if ($unpacker->checkFile($path)) {
                        $unpackedDirBase = substr($file, 0, strlen($file) - strlen($extension));
                        $unpackedDir = $this->createUnpackedDir($unpackedDirBase, $dir);
                        if ($unpackedDir === null) {
                            fprintf(STDERR, "Error: cannot find directory to unpack archive: %s\n", $file);
                            return false;
                        }
                        if ($unpacker->unpack($path, $unpackedDir)) {
                            @unlink($path);
                        }
                        break;
                    }
                }
            }
        }
        return true;
    }

    private function listUnpackerClasses(): array
    {
        $classes = [];
        foreach (glob(__DIR__ . "/Unpacker/*.php") as $file) {
            $class = "Sterzik\\ArchivePostprocessor\\Unpacker\\" . basename($file, ".php");
            if (class_exists($class) && is_a($class, Unpacker\AbstractUnpacker::class, true)) {
                $rc = new ReflectionClass($class);
                if ($rc->isAbstract()) {
                    continue;
                }
                $classes[] = $class;
            }
        }
        return $classes;
    }

    private function createUnpackers(): array
    {
        $unpackers = [];

        foreach ($this->listUnpackerClasses() as $unpackerClass) {
            $unpacker = new $unpackerClass();
            foreach ($unpacker->getExtensions() as $extension) {
                $extension = strtolower($extension);
                $unpackers[$extension] = $unpacker;
            }
        }

        uksort($unpackers, fn($a, $b) => strlen($b) - strlen($a));

        return $unpackers;
    }

    private function createUnpackedDir(string $base, string $dir): ?string
    {
        for ($i = 0; $i < 100000; $i++) {
            if ($i === 0) {
                $name = $base;
            } else {
                $name = sprintf("%s-%d", $base, $i);
            }
            $path = sprintf("%s/%s", $dir, $name);
            if (file_exists($path)) {
                continue;
            }
            if (!@mkdir($path)) {
                return null;
            }
            return $path;
        }
        return null;
    }
}
