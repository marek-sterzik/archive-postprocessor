<?php

namespace Sterzik\ArchivePostprocessor;

use Exception;

class UnpackOkException extends Exception
{
    public function __construct(private bool $success)
    {
        parent::__construct($success ? 'ok' : 'error');
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}
