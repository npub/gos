<?php

declare(strict_types=1);

namespace Npub\Gos\Tests\Twig;

use Npub\Gos\Twig\GosTwigExtension;
use Twig\Test\IntegrationTestCase;

class GosExtensionTest extends IntegrationTestCase
{
    /**
     * @return array<int, mixed>
     */
    public function getExtensions(): array
    {
        return [
            new GosTwigExtension(),
        ];
    }

    public function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/';
    }
}
