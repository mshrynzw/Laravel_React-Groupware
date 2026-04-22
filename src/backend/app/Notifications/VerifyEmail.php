<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends VerifyEmailBase
{
    /**
     * SPA の /verify-email/{id}/{hash} へ誘導する。署名は API の verification.verify と同一クエリを付与する。
     */
    protected function verificationUrl($notifiable): string
    {
        $id = $notifiable->getKey();
        $hash = sha1($notifiable->getEmailForVerification());

        $apiUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes((int) Config::get('auth.verification.expire', 60)),
            [
                'id' => $id,
                'hash' => $hash,
            ]
        );

        $parsed = parse_url($apiUrl);
        $query = [];
        if (! empty($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }

        $base = rtrim((string) Config::get('app.frontend_url', Config::get('app.url')), '/');

        return $base.'/verify-email/'.$id.'/'.$hash.'?'.http_build_query($query);
    }
}
