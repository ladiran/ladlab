<?php
declare(strict_types=1);

/** Licences a shared resource can carry, with a link to the plain-language summary where there is one. */
final class Rights
{
    public const LICENSES = [
        'own' => 'My own work (all rights reserved)',
        'cc-by' => 'Creative Commons BY (credit the author)',
        'cc-by-sa' => 'Creative Commons BY-SA',
        'cc-by-nc' => 'Creative Commons BY-NC (non-commercial)',
        'cc-by-nc-sa' => 'Creative Commons BY-NC-SA',
        'cc-by-nd' => 'Creative Commons BY-ND (no changes)',
        'cc0' => 'Creative Commons CC0 (no rights reserved)',
        'public-domain' => 'Public domain (copyright expired)',
        'permission' => 'Shared with the owner\'s written permission',
    ];
    private const DEEDS = [
        'cc-by' => 'https://creativecommons.org/licenses/by/4.0/', 'cc-by-sa' => 'https://creativecommons.org/licenses/by-sa/4.0/',
        'cc-by-nc' => 'https://creativecommons.org/licenses/by-nc/4.0/', 'cc-by-nc-sa' => 'https://creativecommons.org/licenses/by-nc-sa/4.0/',
        'cc-by-nd' => 'https://creativecommons.org/licenses/by-nd/4.0/', 'cc0' => 'https://creativecommons.org/publicdomain/zero/1.0/',
    ];

    public static function label(string $key): string
    {
        return self::LICENSES[$key] ?? self::LICENSES['own'];
    }

    public static function short(string $key): string
    {
        return match ($key) {
            'own' => 'My own work', 'cc-by' => 'CC BY', 'cc-by-sa' => 'CC BY-SA', 'cc-by-nc' => 'CC BY-NC', 'cc-by-nc-sa' => 'CC BY-NC-SA',
            'cc-by-nd' => 'CC BY-ND', 'cc0' => 'CC0', 'public-domain' => 'Public domain', 'permission' => 'Shared with permission', default => 'My own work',
        };
    }

    public static function deed(string $key): string
    {
        return self::DEEDS[$key] ?? '';
    }
}
