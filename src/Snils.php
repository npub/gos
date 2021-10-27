<?php

declare(strict_types=1);

namespace Npub\Gos;

use JsonSerializable;
use Serializable;
use Stringable;
use ValueError;

use function call_user_func;
use function is_int;
use function is_string;
use function preg_match;
use function preg_replace;
use function serialize;
use function str_pad;
use function substr;
use function unserialize;

use const STR_PAD_LEFT;

/**
 * СНИЛС
 *
 * Номер, присвоенный лицевому счету конкретного лица в системе пенсионного страхования.
 * Состоит из 11 цифр и имеет формат «AAA-AAA-AA ББ», где ББ — контрольная сумма.
 */
class Snils implements Serializable, Stringable, JsonSerializable
{
    public const ID_MIN = 1001999;
    public const ID_MAX = 999999999;

    /**
     * ID / Страховой номер (до 9 разрядов, без контрольной суммы)
     */
    protected int $id;

    /**
     * Создание СНИЛСа из ID
     *
     * @param int $id ID СНИЛСа
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public const FORMAT_CANONICAL = 'C';
    public const FORMAT_SPACE     = 'S';
    public const FORMAT_HYPHEN    = 'H';

    public const SEPARATOR_SPACE  = ' ';
    public const SEPARATOR_HYPHEN = '-';

    /**
     * Создание объекта из строки
     *
     * @param string|int  $snils  СНИЛС
     * @param string|null $format Код формата: Snils::FORMAT_* (если null, то из значения удаляются все знаки-нецифры)
     */
    public static function createFromFormat(string|int $snils, ?string $format = null): Snils|false
    {
        $id = static::validate($snils, $format);

        return is_int($id) ? new Snils($id) : false;
    }

    /**
     * ID / Страховой номер
     */
    public function getID(): int
    {
        return $this->id;
    }

    /**
     * Задать ID / Страховой номер (без проверки контрольной суммы)
     *
     * @param  int $id ID СНИЛСа
     */
    public function setID(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Вывод СНИЛСа в формате «XXXXXXXXXYY»
     */
    public function getCanonical(): string
    {
        return $this->format(self::FORMAT_CANONICAL);
    }

    /**
     * Вывод СНИЛСа в формате «XXX-XXX-XXX YY»
     */
    public function __toString(): string
    {
        return $this->format(self::FORMAT_SPACE);
    }

    /**
     * Контрольная сумма СНИЛСа
     *
     * @return  string 2 последние цифры СНИЛСа
     */
    public function getChecksum(): string
    {
        return static::checksum($this->id);
    }

    /**
     * Контрольная сумма для ID СНИЛСа
     *
     * @param string|int $id ID СНИЛСа
     *
     * @return string 2 цифры
     *
     * @throws ValueError
     */
    public static function checksum(string|int $id): string
    {
        if (! static::isIdValid($id)) {
            throw new ValueError('Недопустимое значение ID СНИЛСа: ' . (string) $id);
        }

        $snils9 = str_pad((string) $id, 9, '0', STR_PAD_LEFT);

        $sum = 0;
        for ($pos = 9; $pos > 0; $pos--) {
            $sum += (int) $snils9[9 - $pos] * $pos;
        }

        return substr(str_pad((string) ($sum % 101), 2, '0', STR_PAD_LEFT), -2);
    }

    /**
     * Проверка СНИЛСа (c контрольной суммой)
     *
     * @param self|string|int|null $snils  СНИЛС
     * @param string|null          $format Код формата: Snils::FORMAT_* (если null,
     *                                     то из значения удаляются все знаки-нецифры)
     *
     * @return int|false ID СНИЛСа
     *
     * Из «<Информационное сообщение> ПФ РФ от 20.12.2011 (с изм. от 21.02.2013) "Особенности представления
     * страхователями отчетности в органы ПФР в 2012 году" (вместе с "Правилами проверки документов
     * персонифицированного учета, представляемых в электронной форме")»:
     *
     * ## 8. Алгоритм формирования контрольного числа Страхового номера
     * Проверка контрольного числа Страхового номера проводится только для номеров больше номера 001-001-998
     * Контрольное число Страхового номера рассчитывается следующим образом:
     * - каждая цифра Страхового номера умножается на номер своей позиции (позиции отсчитываются с конца)
     * - полученные произведения суммируются
     * - сумма делится на 101
     * - последние две цифры остатка от деления являются Контрольным числом.
     *
     * Например:
     * Указан страховой номер 112-233-445 95
     * Проверяем правильность контрольного числа:
     *   цифры номера     1 1 2 2 3 3 4 4 5
     *   номер позиции    9 8 7 6 5 4 3 2 1
     *
     * 1 x 9 + 1 x 8 + 2 x 7 + 2 x 6 + 3 x 5 + 3 x 4 + 4 x 3 + 4 x 2 + 5 x 1 = 95
     *   95 % 101 = 95
     * Контрольное число = 95 - указано верно
     *
     * Некоторые частные случаи:
     *   99  % 101 = 99
     *   100 % 101 = 00
     *   101 % 101 = 00
     *   102 % 101 = 01
     */
    public static function validate(self|string|int|null $snils, string|null $format = null): int|false
    {
        if ($snils instanceof self) {
            return static::isIdValid($snils->getID()) ? $snils->getID() : false;
        }

        if ($snils === null) {
            return false;
        }

        if ($format === null) {
            $snils  = preg_replace('/[^0-9]/', '', (string) $snils);
            $format = self::FORMAT_CANONICAL;
        }

        $snils = (string) $snils;

        [$id, $checksum] = match ($format) {
            self::FORMAT_CANONICAL => call_user_func(static function (string $snils): array {
                $snils = str_pad($snils, 11, '0', STR_PAD_LEFT);

                return [substr($snils, 0, 9), substr($snils, -2)];
            }, $snils),

            self::FORMAT_SPACE,
            self::FORMAT_HYPHEN => call_user_func(static function (string $snils, string $format): array {
                $separator = $format === self::FORMAT_SPACE ? '\\s' : self::SEPARATOR_HYPHEN;

                if (preg_match('/^(\d{3})-(\d{3})-(\d{3})' . $separator . '(\d{2})$/', $snils, $matches) > 0) {
                    return [$matches[1] . $matches[2] . $matches[3], $matches[4]];
                }

                return [null, null];
            }, $snils, $format),
        };

        if (! static::isIdValid($id)) {
            return false;
        }

        return $checksum === static::checksum($id) ? (int) $id : false;
    }

    /**
     * Проверка ID СНИЛСа
     *
     * @param string|int|null $id ID СНИЛСа
     */
    protected static function isIdValid(string|int|null $id): bool
    {
        return $id !== null && self::ID_MIN <= $id && $id <= self::ID_MAX;
    }

    /**
     * Форматированный СНИЛС
     *
     * @param string $format Код формата: Snils::FORMAT_*
     */
    public function format(string $format = self::FORMAT_CANONICAL): string
    {
        $snils = str_pad((string) $this->id, 9, '0', STR_PAD_LEFT) . $this->getChecksum();

        return match ($format) {
            self::FORMAT_CANONICAL => $snils,

            self::FORMAT_SPACE,
            self::FORMAT_HYPHEN => substr($snils, 0, 3)
                . '-' . substr($snils, 3, 3)
                . '-' . substr($snils, 6, 3)
                . ($format === self::FORMAT_SPACE ? self::SEPARATOR_SPACE : self::SEPARATOR_HYPHEN)
                . substr($snils, 9, 2)
            ,
        };
    }

    /**
     * Сравнение с другим СНИЛСом
     */
    public function isEqual(Snils|string|int|null $snils): bool
    {
        if ($snils === null) {
            return false;
        }

        if (is_string($snils) || is_int($snils)) {
            $snils = static::createFromFormat($snils);
        }

        return $this === $snils;
    }

    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    /**
     * Данные объекта для сериализации
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
        ];
    }

    public function jsonSerialize(): string
    {
        return $this->__toString();
    }

    /** @inheritDoc */
    public function unserialize($data): void
    {
        $this->__unserialize(unserialize($data));
    }

    /**
     * Перенос данных сериализации в объект
     *
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
    }

    /**
     * Данные для var_dump() (for DEBUG)
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            '_id' => $this->id,
            '_checksum' => $this->getChecksum(),
            '_canonical' => $this->getCanonical(),
            '__toString' => $this->__toString(),
        ];
    }
}
