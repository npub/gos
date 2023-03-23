<?php

declare(strict_types=1);

namespace Npub\Gos\Twig;

use Npub\Gos\Snils;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

/**
 * Расширение Twig
 */
final class GosTwigExtension extends AbstractExtension
{
    /**
     * Фильтры расширения
     *
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('snils_format', [Snils::class, 'stringFormat'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    /**
     * Тесты расширения
     *
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest(Snils::NAME, [Snils::class, 'isSnilsObject']),
            new TwigTest('valid_' . Snils::NAME, [Snils::class, 'isValidSnils']),
        ];
    }
}
