<?php

declare(strict_types=1);

namespace Npub\Gos\Tests\Twig;

use Npub\Gos\Twig\GosTwigExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Test\IntegrationTestCase;

/**
 * @covers GosTwigExtension
 * @group twig
 * @small
 */
class GosTwigExtensionTest extends IntegrationTestCase
{
    /** @return array<ExtensionInterface> */
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

    /** @return array<mixed> */
    public function getLegacyTests(): array
    {
        return $this->getTests('testLegacyIntegration', false);
    }
}
