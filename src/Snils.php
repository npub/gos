<?php

declare(strict_types=1);

namespace Npub\Gos;

use JsonSerializable;
use Serializable;
use Stringable;
use UnexpectedValueException;

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
 * Номер, присвоенный лицевому счету конкретного лица в системе пенсионного страхования РФ,
 * состоящий из 11 цифр и имеющий формат «AAA-AAA-AA ББ», где ББ — контрольная сумма.
 *
 * @see https://ru.wikipedia.org/wiki/Страховой_номер_индивидуального_лицевого_счёта
 */
class Snils implements Serializable, Stringable, JsonSerializable
{
    public const ID_MIN = 1001999;
    public const ID_MAX = 999999999;

    /**
     * ID / Страховой номер (от 7 до 9 разрядов, не содержит контрольную сумму)
     */
    protected int $id;

    /**
     * Создание СНИЛСа из ID
     *
     * ВНИМАНИЕ: Проверка на диапазон ID [self::ID_MIN – self::ID_MAX] в конструкторе не производится
     * (только в функции `createFromFormat()`). Это необходимо для возможности поиска и замены ошибочно хранимых
     * в БД значений. Для проверки после создания объекта можно воспользоваться функцией `isValid()`.
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
    public function getCanonical(): string|null
    {
        return $this->format(self::FORMAT_CANONICAL);
    }

    /**
     * Вывод СНИЛСа в формате «XXX-XXX-XXX YY»
     */
    public function __toString(): string
    {
        return $this->format(self::FORMAT_SPACE) ?? '';
    }

    /**
     * Контрольная сумма СНИЛСа
     *
     * @return  string|null 2 последние цифры СНИЛСа
     */
    public function getChecksum(): string|null
    {
        return static::checksum($this->id);
    }

    /**
     * Контрольная сумма для ID СНИЛСа
     *
     * @param string|int $id ID СНИЛСа
     *
     * @return string|null 2 цифры (контрольная сумма) | null — если невалидный ID СНИЛСа
     */
    public static function checksum(string|int $id): string|null
    {
        if (! static::isIdValid($id)) {
            return null;
        }

        $snils9 = str_pad((string) $id, 9, '0', STR_PAD_LEFT);

        $sum = 0;
        for ($pos = 9; $pos > 0; $pos--) {
            $sum += (int) $snils9[9 - $pos] * $pos;
        }

        return substr(str_pad((string) ($sum % 101), 2, '0', STR_PAD_LEFT), -2);
    }

    /**
     * Проверка СНИЛСа (c контрольной суммой) по формальным признакам (без проверки на существование).
     *
     * ВНИМАНИЕ: для проверки существования СНИЛСа и привязки к ФИО / ДР человека нужно отправлять запрос
     * в Пенсионный фонд РФ
     *
     * @see https://es.pfrf.ru/checkSnils/
     *
     * @param self|string|int|null $snils      СНИЛС
     * @param string|null          $formatHint Код формата: Snils::FORMAT_* (если не задан (null),
     *                                         то из значения удаляются все знаки-нецифры, а потом используется
     *                                         Snils::FORMAT_CANONICAL)
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
    public static function validate(self|string|int|null $snils, string|null $formatHint = null): int|false
    {
        if ($snils instanceof self) {
            return static::isIdValid($snils->getID()) ? $snils->getID() : false;
        }

        if ($snils === null) {
            return false;
        }

        if ($formatHint === null) {
            $snils      = preg_replace('/[^0-9]/', '', (string) $snils);
            $formatHint = self::FORMAT_CANONICAL;
        }

        $snils = (string) $snils;

        [$id, $checksum] = match ($formatHint) {
            self::FORMAT_CANONICAL => call_user_func(static function (string $snils): array {
                $snils = str_pad($snils, 11, '0', STR_PAD_LEFT);

                return [substr($snils, 0, 9), substr($snils, -2)];
            }, $snils),

            self::FORMAT_SPACE,
            self::FORMAT_HYPHEN => call_user_func(static function (string $snils, string $formatHint): array {
                $separator = $formatHint === self::FORMAT_SPACE ? '\\s' : self::SEPARATOR_HYPHEN;

                if (preg_match('/^(\d{3})-(\d{3})-(\d{3})' . $separator . '(\d{2})$/', $snils, $matches) > 0) {
                    return [$matches[1] . $matches[2] . $matches[3], $matches[4]];
                }

                return [null, null];
            }, $snils, $formatHint),

            default => throw new UnexpectedValueException('Неизвестный формат СНИЛСа')
        };

        if ($id === null || $checksum === null || ! static::isIdValid($id)) {
            return false;
        }

        return $checksum === static::checksum($id) ? (int) $id : false;
    }

    /**
     * Проверка произвольного ID СНИЛСа
     *
     * @param string|int|null $id ID СНИЛСа
     */
    public static function isIdValid(string|int|null $id): bool
    {
        return $id !== null && self::ID_MIN <= $id && $id <= self::ID_MAX;
    }

    /**
     * Проверка СНИЛСа
     */
    public function isValid(): bool
    {
        return self::isIdValid($this->id);
    }

    /**
     * Форматированный СНИЛС
     *
     * @param string $format|null Код формата: Snils::FORMAT_* | null — в случае ошибочного ID СНИЛСа)
     */
    public function format(string $format = self::FORMAT_SPACE): string|null
    {
        $checksum = $this->getChecksum();
        if ($checksum === null) {
            return null;
        }

        $snils = str_pad((string) $this->id, 9, '0', STR_PAD_LEFT) . $checksum;

        return match ($format) {
            self::FORMAT_CANONICAL => $snils,

            self::FORMAT_SPACE,
            self::FORMAT_HYPHEN => substr($snils, 0, 3)
                . '-' . substr($snils, 3, 3)
                . '-' . substr($snils, 6, 3)
                . ($format === self::FORMAT_SPACE ? self::SEPARATOR_SPACE : self::SEPARATOR_HYPHEN)
                . substr($snils, 9, 2)
            ,

            default => throw new UnexpectedValueException('Неизвестный формат СНИЛСа')
        };
    }

    /**
     * Форматированный СНИЛС из произвольно строки
     *
     * @param Snils|string|int|null $snils           СНИЛС
     * @param string                $format          Код формата вывода: Snils::FORMAT_*
     * @param string|null           $inputFormatHint Код входного формата: Snils::FORMAT_*
     */
    public static function stringFormat(
        Snils|string|int|null $snils,
        string $format = self::FORMAT_SPACE,
        string|null $inputFormatHint = null
    ): string|null {
        if ($snils === null) {
            return null;
        }

        if (is_int($snils) || is_string($snils)) {
            $snils = self::createFromFormat($snils, $inputFormatHint);
        }

        if ($snils instanceof self) {
            return $snils->format($format);
        }

        return null;
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

    /**
     * Сериализация для JSON
     */
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
        $isValid = $this->isValid();

        return [
            '_id' => $this->id,
            '_is_valid' => $isValid,
            '_checksum' => $this->getChecksum(),
            '_canonical' => $this->getCanonical(),
            '__toString' => $this->__toString(),
        ];
    }
}
