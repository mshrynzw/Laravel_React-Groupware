<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Config;

class ResetPassword extends ResetPasswordNotification
{
    /**
     * SPA の /password-reset?token=&email= へ誘導する。
     */
    protected function resetUrl($notifiable): string
    {
        $base = rtrim((string) Config::get('app.frontend_url', Config::get('app.url')), '/');

        return $base.'/password-reset?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
