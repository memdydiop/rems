<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class TenantVerifyEmail extends VerifyEmail
{
    /**
     * Get the verification URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function verificationUrl($notifiable)
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable);
        }

        // Apply tenant domain context securely
        if (tenancy()->tenant) {
            $domain = tenancy()->tenant->domains->first()?->domain;

            if ($domain) {
                $protocol = app()->isProduction() ? 'https' : 'http';

                // Parse port from APP_URL for dev environments
                $appUrl = config('app.url');
                $port = parse_url($appUrl, PHP_URL_PORT);
                $portSuffix = $port ? ":{$port}" : '';

                URL::forceRootUrl("{$protocol}://{$domain}{$portSuffix}");
            }
        }

        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
