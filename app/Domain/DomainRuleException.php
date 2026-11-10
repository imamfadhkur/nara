<?php

namespace App\Domain;

use RuntimeException;

/**
 * Pelanggaran aturan bisnis (mis. WIP penuh, transisi status tidak sah).
 *
 * Dirender otomatis sebagai error validasi pada field terkait
 * (lihat bootstrap/app.php), sehingga controller tetap tipis.
 */
class DomainRuleException extends RuntimeException
{
    public function __construct(string $message, public readonly string $field = 'general')
    {
        parent::__construct($message);
    }
}

