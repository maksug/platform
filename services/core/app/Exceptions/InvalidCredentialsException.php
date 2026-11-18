<?php

namespace App\Exceptions;

use DomainException;

class InvalidCredentialsException extends DomainException
{
    public function __construct(string $message = 'Неверные учётные данные')
    {
        parent::__construct($message);
    }
}
