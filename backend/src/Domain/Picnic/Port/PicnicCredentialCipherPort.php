<?php

declare(strict_types=1);

namespace App\Domain\Picnic\Port;

interface PicnicCredentialCipherPort
{
    public function encryptAuthKeyForStorage(string $plainText): string;

    public function encryptPasswordForStorage(string $plainText): string;
}
