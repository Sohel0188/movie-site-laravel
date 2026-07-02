<?php

namespace App\Support;

class Countries
{
    public const LIST = [
        ['code' => 'US', 'label' => 'United States'],
        ['code' => 'GB', 'label' => 'United Kingdom'],
        ['code' => 'IN', 'label' => 'India'],
        ['code' => 'BD', 'label' => 'Bangladesh'],
        ['code' => 'PK', 'label' => 'Pakistan'],
        ['code' => 'CA', 'label' => 'Canada'],
        ['code' => 'AU', 'label' => 'Australia'],
        ['code' => 'FR', 'label' => 'France'],
        ['code' => 'DE', 'label' => 'Germany'],
        ['code' => 'IT', 'label' => 'Italy'],
        ['code' => 'ES', 'label' => 'Spain'],
        ['code' => 'JP', 'label' => 'Japan'],
        ['code' => 'KR', 'label' => 'South Korea'],
        ['code' => 'CN', 'label' => 'China'],
        ['code' => 'HK', 'label' => 'Hong Kong'],
        ['code' => 'TW', 'label' => 'Taiwan'],
        ['code' => 'TH', 'label' => 'Thailand'],
        ['code' => 'PH', 'label' => 'Philippines'],
        ['code' => 'ID', 'label' => 'Indonesia'],
        ['code' => 'MY', 'label' => 'Malaysia'],
        ['code' => 'SG', 'label' => 'Singapore'],
        ['code' => 'VN', 'label' => 'Vietnam'],
        ['code' => 'RU', 'label' => 'Russia'],
        ['code' => 'TR', 'label' => 'Turkey'],
        ['code' => 'BR', 'label' => 'Brazil'],
        ['code' => 'MX', 'label' => 'Mexico'],
        ['code' => 'AR', 'label' => 'Argentina'],
        ['code' => 'NL', 'label' => 'Netherlands'],
        ['code' => 'SE', 'label' => 'Sweden'],
        ['code' => 'NO', 'label' => 'Norway'],
        ['code' => 'DK', 'label' => 'Denmark'],
        ['code' => 'PL', 'label' => 'Poland'],
        ['code' => 'UA', 'label' => 'Ukraine'],
        ['code' => 'EG', 'label' => 'Egypt'],
        ['code' => 'ZA', 'label' => 'South Africa'],
        ['code' => 'NG', 'label' => 'Nigeria'],
        ['code' => 'AE', 'label' => 'United Arab Emirates'],
        ['code' => 'SA', 'label' => 'Saudi Arabia'],
        ['code' => 'IL', 'label' => 'Israel'],
        ['code' => 'IR', 'label' => 'Iran'],
    ];

    public static function get(string $code): ?array
    {
        $upper = strtoupper($code);
        foreach (self::LIST as $country) {
            if ($country['code'] === $upper) {
                return $country;
            }
        }

        return null;
    }

    public static function label(string $code): string
    {
        return self::get($code)['label'] ?? strtoupper($code);
    }
}
