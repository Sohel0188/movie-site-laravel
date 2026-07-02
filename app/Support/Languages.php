<?php

namespace App\Support;

class Languages
{
    public const LIST = [
        ['code' => 'en', 'label' => 'English', 'native' => 'English'],
        ['code' => 'es', 'label' => 'Spanish', 'native' => 'Español'],
        ['code' => 'fr', 'label' => 'French', 'native' => 'Français'],
        ['code' => 'de', 'label' => 'German', 'native' => 'Deutsch'],
        ['code' => 'it', 'label' => 'Italian', 'native' => 'Italiano'],
        ['code' => 'pt', 'label' => 'Portuguese', 'native' => 'Português'],
        ['code' => 'ru', 'label' => 'Russian', 'native' => 'Русский'],
        ['code' => 'ja', 'label' => 'Japanese', 'native' => '日本語'],
        ['code' => 'ko', 'label' => 'Korean', 'native' => '한국어'],
        ['code' => 'zh', 'label' => 'Chinese', 'native' => '中文'],
        ['code' => 'hi', 'label' => 'Hindi', 'native' => 'हिन्दी'],
        ['code' => 'bn', 'label' => 'Bengali', 'native' => 'বাংলা'],
        ['code' => 'ar', 'label' => 'Arabic', 'native' => 'العربية'],
        ['code' => 'tr', 'label' => 'Turkish', 'native' => 'Türkçe'],
        ['code' => 'th', 'label' => 'Thai', 'native' => 'ไทย'],
        ['code' => 'vi', 'label' => 'Vietnamese', 'native' => 'Tiếng Việt'],
        ['code' => 'id', 'label' => 'Indonesian', 'native' => 'Bahasa Indonesia'],
        ['code' => 'nl', 'label' => 'Dutch', 'native' => 'Nederlands'],
        ['code' => 'pl', 'label' => 'Polish', 'native' => 'Polski'],
        ['code' => 'uk', 'label' => 'Ukrainian', 'native' => 'Українська'],
        ['code' => 'ta', 'label' => 'Tamil', 'native' => 'தமிழ்'],
        ['code' => 'te', 'label' => 'Telugu', 'native' => 'తెలుగు'],
        ['code' => 'ml', 'label' => 'Malayalam', 'native' => 'മലയാളം'],
        ['code' => 'mr', 'label' => 'Marathi', 'native' => 'मराठी'],
    ];

    public static function get(string $code): ?array
    {
        $lower = strtolower($code);
        foreach (self::LIST as $lang) {
            if ($lang['code'] === $lower) {
                return $lang;
            }
        }

        return null;
    }

    public static function label(string $code): string
    {
        return self::get($code)['label'] ?? strtoupper($code);
    }
}
