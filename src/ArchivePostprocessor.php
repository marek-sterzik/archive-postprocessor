<?php

namespace Sterzik\ArchivePostprocessor;

use Exception;

class ArchivePostprocessor
{
    const FLAG_HANDLERS = [
        "check" => "doCheck",
        "unpack" => "doUnpack",
        "flatten" => "doFlatten",
        "flatten-root" => "doFlattenRoot",
    ];

    private array $handlers = [];
    private bool $flattenArchives = false;
        
    public function __construct(private array $flags)
    {
        $flags = array_fill_keys($flags, true);
        $flags['check'] = true;

        $this->handlers = [];
        foreach (self::FLAG_HANDLERS as $flag => $handler) {
            if (isset($flags[$flag])) {
                $this->handlers[] = $handler;
            }
        }

        $this->flattenArchives = ($flags['flatten'] ?? false) ? true : false;
    }

    public function postprocessDirectory(string $dir): bool
    {
        try {
            foreach ($this->handlers as $handler) {
                if (!$this->$handler($dir)) {
                    return false;
                }
            }
        } catch (Exception $e) {
            fprintf(STDERR, "Error: %s\n", $e->getMessage());
            return false;
        }
        return true;
    }

    private function doCheck(string $dir): bool
    {
        if (!is_dir($dir)) {
            fprintf(STDERR, "Error: not a directory: %s\n", $dir);
            return false;
        }
        return true;
    }

    private function doUnpack(string $dir): bool
    {
        $unpacker = new Unpacker();
        return $unpacker->unpack($dir);
    }

    private function doFlattenRoot(string $dir): bool
    {
        $flattener = new Flattener();
        return $flattener->flattenDir($dir, false);
    }

    private function doFlatten(string $dir): bool
    {
        $flattener = new Flattener();
        foreach (DirectoryLister::listDirectory($dir) as $subdir) {
            $path = $dir . "/" . $subdir;
            if (is_dir($path)) {
                if (!$flattener->flattenDir($path, true)) {
                    return false;
                }
            }
        }
        return true;
    }
}
