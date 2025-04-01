<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

readonly class ButtonRuntime implements RuntimeExtensionInterface
{
    public const array TRANSLATABLE_KEYS = [
        'gate' => 'dockontrol.home.buttons.gate',
        'garage' => 'dockontrol.home.buttons.garage',
        'entrance' => 'dockontrol.home.buttons.entrance',
        'elevator' => 'dockontrol.home.buttons.elevator',
        'building' => 'dockontrol.home.buttons.building',
    ];

    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function translateButtonText(string $buttonText): string
    {
        /** @var string $buttonText */
        $buttonText = preg_replace_callback('/%([^%\s]+)%/', function ($matches) {
            $key = strtolower($matches[1]);
            return isset(self::TRANSLATABLE_KEYS[$key])
                ? $this->translator->trans(self::TRANSLATABLE_KEYS[$key])
                : $matches[0];
        }, $buttonText);

        return $buttonText;
    }

    public function isButtonTextTranslatable(string $buttonText): bool
    {
        preg_match_all('/%([^%\s]+)%/', $buttonText, $matches);

        return empty($matches[1]) || empty(array_diff($matches[1], array_keys(self::TRANSLATABLE_KEYS)));
    }
}
