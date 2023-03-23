<?php

declare(strict_types=1);

namespace Npub\Gos\Tests;

use Npub\Gos\Snils;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Snils
 * @group object
 * @group Snils
 * @small
 */
final class SnilsTest extends TestCase
{
    public function getValidTestSnilsID(): int
    {
        return 123456789;
    }

    public function getValidTestSnilsChecksum(): string
    {
        return '64';
    }

    public function getValidTestSnilsCanonical(): string
    {
        return (string) $this->getValidTestSnilsID() . $this->getValidTestSnilsChecksum();
    }

    public function getValidTestSnils(): string
    {
        return '123-456-789 64';
    }

    /**
     * Форматы валидного СНИЛСа 123-456-789 64 с указанием имени формата
     *
     * @return array<string, mixed> 'Описание' => ['СНИЛС', 'Формат', СНИЛС ID]
     */
    public function validTestSnilsFormatsProvider(): array
    {
        return [
            'auto'       => ['123-456-789 64', null, 123456789],
            'dirty auto' => [" \t 123_456*789=64\n&", null, 123456789],
            'hyphen'     => ['123-456-789-64', Snils::FORMAT_HYPHEN, 123456789],
            'space'      => ['123-456-789 64', Snils::FORMAT_SPACE, 123456789],
            'canonical'  => ['12345678964', Snils::FORMAT_CANONICAL, 123456789],
        ];
    }

    /**
     * Форматы невалидных СНИЛС 123-456-789 64 или их форматов
     *
     * @return array<string, mixed> 'Описание' => ['СНИЛС', 'Формат', false]
     */
    public function invalidTestSnilsesAndFormatsProvider(): array
    {
        return [
            'null / auto' => [null, null, false],
            'empty / auto' => ['', null, false],
            'dirty empty / auto' => [' - - ', null, false],
            'too short / auto' => ['12345', null, false],
            'too long / auto'  => ['123456789012', null, false],
            'too long / canonical'  => ['1234567890123', Snils::FORMAT_CANONICAL, false],

            'zero int'  => [0, null, false],

            'zero / auto'  => ['000-000-000 00', null, false],
            'zero / space'  => ['000-000-000 00', Snils::FORMAT_SPACE, false],
            'zero / canonical'  => ['00000000000', Snils::FORMAT_CANONICAL, false],

            'range / auto' => ['001-000-050 00', null, false],
            'range / space' => ['001-000-050 00', Snils::FORMAT_SPACE, false],
            'range / canonical'  => ['00100005000', Snils::FORMAT_CANONICAL, false],

            'dirty & too long / auto' => ["\n\t  =0100/000_050  19\n", null, false],
            'dirty / space' => [" 001-000-050 10\t", Snils::FORMAT_SPACE, false],
            'dirty / canonical'  => ["00100005019\t  ", Snils::FORMAT_CANONICAL, false],
        ];
    }

    /**
     * @covers ::validate
     * @dataProvider validTestSnilsFormatsProvider
     * @dataProvider invalidTestSnilsesAndFormatsProvider
     */
    public function testValidation(Snils|string|int|null $snils, string|null $format, int|false $snilsID): void
    {
        self::assertEquals($snilsID, Snils::validate($snils, $format));
    }

    /**
     * Контрольные суммы различных СНИЛС
     *
     * @return array<array<mixed>>
     */
    public function snilsChecksumsProvider(): array
    {
        return [
            [null, null],
            ['', null],
            [0, null],
            ['000000000', null],
            ['123', null],
            ['123456789_invalid', null],
            [Snils::ID_MIN - 1, null],
            [Snils::ID_MIN, '65'],
            [1002300, '24'],
            ['1002300.5', null],
            ['113353201', '00'],
            ['113353202', '00'],
            [$this->getValidTestSnilsID(), $this->getValidTestSnilsChecksum()],
            ['432112345', '14'],
            ['987654321', '83'],
            [Snils::ID_MAX, '01'],
            [Snils::ID_MAX + 1, null],
        ];
    }

    /**
     * @covers ::checksum
     * @dataProvider snilsChecksumsProvider
     */
    public function testChecksums(string|int|null $snils, string|null $checksum): void
    {
        self::assertEquals($checksum, Snils::checksum($snils));
    }

    /**
     * @covers ::__construct
     * @dataProvider snilsChecksumsProvider
     */
    public function testCreationSnilsFromID(): void
    {
        self::assertInstanceOf(
            Snils::class,
            new Snils($this->getValidTestSnilsID()), // СНИЛС ID
        );
    }

    /**
     * @covers ::createFromFormat
     * @dataProvider validTestSnilsFormatsProvider
     */
    public function testCreationFromValidSnilsString(string|int $snils, string|null $format): void
    {
        self::assertInstanceOf(
            Snils::class,
            Snils::createFromFormat($snils, $format),
        );
    }

    /**
     * @covers ::createFromFormat
     * @covers ::__toString
     * @dataProvider validTestSnilsFormatsProvider
     */
    public function testOutputAsString(string $snils, string|null $format): void
    {
        self::assertEquals(
            $this->getValidTestSnils(),
            Snils::createFromFormat($snils, $format),
        );
    }

    /**
     * @covers ::checksum
     * @covers ::__toString
     * @dataProvider validTestSnilsFormatsProvider
     */
    public function testInvalidSnilsString(): void
    {
        self::assertEquals(
            null,
            Snils::checksum('12345'),
        );
    }
}
