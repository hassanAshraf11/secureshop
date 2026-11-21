<?php

namespace App\Services;

class Catalog
{
    public const CATEGORIES = ['Keyboards', 'Mice', 'Headphones', 'Accessories', 'Speakers', 'Monitors'];

    public const IMAGES = [
        'Keyboards' => 'keyboard.webp', 'Mice' => 'mouse.webp', 'Headphones' => 'headphones.webp',
        'Accessories' => 'hub.webp', 'Speakers' => 'speaker.webp', 'Monitors' => 'monitor.webp',
    ];

    public static function money(int $piastres): string
    {
        return number_format($piastres / 100, $piastres % 100 ? 2 : 0).' EGP';
    }
}
