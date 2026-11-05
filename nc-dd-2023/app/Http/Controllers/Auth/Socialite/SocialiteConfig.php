<?php declare(strict_types=1);

namespace App\Http\Controllers\Auth\Socialite;


class SocialiteConfig
{
    /**
     * Driver for google
     */
    const SOCIALITE_GOOGLE = 'google';

    /**
     * Driver for github
     */
    const SOCIALITE_GITHUB = 'github';

    /**
     * Driver for facebook
     */
    const SOCIALITE_FACEBOOK = 'facebook';

    /**
     * All socialite drivers
     */
    const ALL_SOCIALITE_DRIVERS = [
        self::SOCIALITE_GOOGLE,
        self::SOCIALITE_GITHUB,
        self::SOCIALITE_FACEBOOK
    ];
}
