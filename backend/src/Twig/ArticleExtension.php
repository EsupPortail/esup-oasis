<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;

class ArticleExtension
{
    #[AsTwigFilter('articlePartitif')]
    public function partitif(string $sujet, string $defini): string
    {
        return match ($defini) {
            'le' => 'du ' . $sujet,
            'la' => 'de la ' . $sujet,
            default => "de l'" . $sujet,
        };
    }

    #[AsTwigFilter('articleDefini')]
    public function defini(string $sujet, string $defini): string
    {
        return match (strtolower($defini)) {
            'le', 'la' => $defini . ' ' . $sujet,
            default => $defini . $sujet,
        };
    }

    #[AsTwigFilter('articleDefiniContracte')]
    public function definiContracte(string $sujet, string $defini): string
    {
        return match ($defini) {
            'le' => 'au ' . $sujet,
            'la' => 'à la ' . $sujet,
            default => "à l'" . $sujet,
        };
    }
}
