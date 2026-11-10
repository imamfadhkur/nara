<?php

namespace App\Domain\Projects;

use App\Domain\DomainRuleException;

class WipLimitExceededException extends DomainRuleException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 'status');
    }
}

