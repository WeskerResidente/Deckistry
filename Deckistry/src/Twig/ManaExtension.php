<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ManaExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('mana_symbols', [$this, 'convertManaSymbols'], ['is_safe' => ['html']]),
        ];
    }

    public function convertManaSymbols(string $text): string
    {
        // Map mana symbols to icon files
        $manaMap = [
            '{W}' => '<img src="/icons/plain.webp" alt="White" class="mana-symbol-inline" title="White mana">',
            '{U}' => '<img src="/icons/Island.webp" alt="Blue" class="mana-symbol-inline" title="Blue mana">',
            '{B}' => '<img src="/icons/Swamp.webp" alt="Black" class="mana-symbol-inline" title="Black mana">',
            '{R}' => '<img src="/icons/Mountain.webp" alt="Red" class="mana-symbol-inline" title="Red mana">',
            '{G}' => '<img src="/icons/Forest.webp" alt="Green" class="mana-symbol-inline" title="Green mana">',
            '{C}' => '<img src="/icons/unncolor.webp" alt="Colorless" class="mana-symbol-inline" title="Colorless mana">',
        ];

        // Replace numbers {0} to {20}
        for ($i = 0; $i <= 20; $i++) {
            $text = str_replace('{'.$i.'}', '<span class="mana-number">'.$i.'</span>', $text);
        }

        // Replace colored mana symbols
        foreach ($manaMap as $symbol => $replacement) {
            $text = str_replace($symbol, $replacement, $text);
        }

        // Handle hybrid mana (e.g., {W/U}, {2/W})
        $text = preg_replace('/\{([WUBRGC])\/([WUBRGC])\}/', '<span class="mana-hybrid">$1/$2</span>', $text);
        $text = preg_replace('/\{(\d+)\/([WUBRGC])\}/', '<span class="mana-hybrid">$1/$2</span>', $text);

        // Handle Phyrexian mana
        $text = preg_replace('/\{([WUBRGC])\/P\}/', '<span class="mana-phyrexian">$1/P</span>', $text);

        // Handle X, Y, Z
        $text = str_replace('{X}', '<span class="mana-variable">X</span>', $text);
        $text = str_replace('{Y}', '<span class="mana-variable">Y</span>', $text);
        $text = str_replace('{Z}', '<span class="mana-variable">Z</span>', $text);

        // Handle tap symbol
        $text = str_replace('{T}', '<span class="mana-tap">⟳</span>', $text);

        return $text;
    }
}
