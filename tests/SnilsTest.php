<?php

declare(strict_types=1);

namespace Npub\Gos\Tests;

use Npub\Gos\Snils;
use PHPUnit\Framework\TestCase;
use ValueError;

final class SnilsTest extends TestCase
{
    public function getValidTestSnilsID(): int
    {
        return 123456789;
    }

    public function getValidTestSnilsCanonical(): string
    {
        return '12345678964';
    }

    public function getValidTestSnils(): string
    {
        return '123-456-789 64';
    }

    /**
     * Форматы валидного СНИЛСа 123-456-789 64 с указанием имени формата
     *
     * @return array<string, array<int, string|null>>
     */
    public function validTestSnilsFormatsProvider(): array
    {
        return [
            'auto format'      => ['123-456-789 64', null],
            'hyphen format'    => ['123-456-789-64', Snils::FORMAT_HYPHEN],
            'space format'     => ['123-456-789 64', Snils::FORMAT_SPACE],
            'canonical format' => ['12345678964', Snils::FORMAT_CANONICAL],
        ];
    }

    public function testCreationSnilsFromID(): void
    {
        self::assertInstanceOf(
            Snils::class,
            new Snils($this->getValidTestSnilsID()) // СНИЛС ID
        );
    }

    /**
     * @dataProvider validTestSnilsFormatsProvider
     */
    public function testCreationFromValidSnilsString(string $snils, string|null $format): void
    {
        self::assertInstanceOf(
            Snils::class,
            Snils::createFromFormat($snils, $format)
        );
    }

    /**
     * @dataProvider validTestSnilsFormatsProvider
     */
    public function testToString(string $snils, string|null $format): void
    {
        self::assertEquals(
            $this->getValidTestSnils(),
            Snils::createFromFormat($snils, $format)
        );
    }

    public function testInvalidSnilsString(): void
    {
        self::expectException(ValueError::class);
        Snils::checksum('12345');
        // Snils::checksum('123456789');
        // Snils::checksum('12345678901');
    }
}
