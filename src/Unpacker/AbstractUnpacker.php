<?php

namespace Sterzik\ArchivePostprocessor\Unpacker;

use Sterzik\ArchivePostprocessor\UnpackOkException;
use Sterzik\ArchivePostprocessor\DirectoryLister;

abstract class AbstractUnpacker
{
    private ?string $unpackedFilename = null;
    private ?string $unpackedDirectory = null;
    private ?string $extension = null;

    abstract public function getExtensions(): array;
    abstract public function checkFile(string $filename): bool;
    abstract public function unpack(string $filename, string $directory): bool;

    public function doCheckFile(string $filename, string $extension): bool
    {
        $old = [$this->extension];
        $this->extension = $extension;
        try {
            return $this->checkFile($filename);
        } finally {
            $this->extension = $old[0];
        }
    }

    public function doUnpack(string $filename, string $directory, string $extension): bool
    {
        $old = [$this->unpackedFilename, $this->unpackedDirectory, $this->extension];
        $this->unpackedFilename = $filename;
        $this->unpackedDirectory = $directory;
        $this->extension = $extension;
        try {
            return $this->unpack($filename, $directory);
        } catch (UnpackOkException $e) {
            return $e->isSuccess();
        } catch (Exception $e) {
            return false;
        } finally {
            $this->unpackedFilename = $old[0];
            $this->unpackedDirectory = $old[1];
            $this->extension = $old[2];
        }
    }

    protected function getExtension(): string
    {
        if ($this->extension === null) {
            throw new Exception("Cannot call getExtension() outside of an unpack call");
        }
        return $this->extension;
    }

    protected function mimeCheck(string $filename, string $mime, bool $isArchive = false): bool
    {
        $archiveFlag = $isArchive ? ' -z' : '';
        $command = sprintf("file --mime-type -b%s -- %s", $archiveFlag, escapeshellarg($filename));
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

    protected function commandEx(string $template, array $args): bool
    {
        $args = array_map(fn($arg) => escapeshellarg($arg), $args);
        $command = sprintf($template, ...$args);
        $result = 1;
        if (system($command, $result) === false) {
            return false;
        }
        var_dump($result);
        if ($result !== 0) {
            return false;
        }
        return true;
    }

    protected function unpackCommand(string $template, string ...$args): bool
    {
        $success = $this->commandEx($template, $args);
        if ($success) {
            throw new UnpackOkException(true);
        }
        if (is_string($this->unpackedDirectory) && !DirectoryLister::isEmpty($this->unpackedDirectory)) {
            throw new UnpackOkException(false);
        }
        return false;
    }

    protected function command(string $template, string ...$args): bool
    {
        return $this->commandEx($template, $args);
    }

    protected function withCwd(string $dir, callable $function): mixed
    {
        $cwd = getcwd();
        if (!chdir($dir)) {
            throw new Exception("Cannot change directory");
        }
        try {
            return $function();
        } finally {
            if (!chdir($cwd)) {
                throw new Exception("Cannot change directory");
            }
        }
    }
}
