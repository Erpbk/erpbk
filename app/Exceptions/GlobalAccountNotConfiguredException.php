<?php

namespace App\Exceptions;

use RuntimeException;

class GlobalAccountNotConfiguredException extends RuntimeException
{
    public function __construct(string $accountLabel)
    {
        parent::__construct(self::messageFor($accountLabel));
    }

    public static function messageFor(string $accountLabel): string
    {
        return 'Contact ERP Administrator to Setup '.$accountLabel.' Account';
    }
}
