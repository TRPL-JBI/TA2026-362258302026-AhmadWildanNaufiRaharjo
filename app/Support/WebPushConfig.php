<?php

namespace App\Support;

class WebPushConfig
{
    public static function isConfigured(): bool
    {
        $publicKey = config('webpush.vapid.public_key');
        $privateKey = config('webpush.vapid.private_key');

        return filled($publicKey) && filled($privateKey);
    }
}
