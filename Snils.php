<?php

declare(strict_types=1);

namespace Npub\Gos;

use str_pad;
use substr;

/**
 * СНИЛС
 * Номер, присвоенный лицевому счету конкретного лица в системе пенсионного страхования.
 * Состоит из 11 цифр и имеет формат «AAA-AAA-AA ББ», где ББ — контрольная сумма.
 * 
 * @author Александр Васильев <av@zbox.ru>
 */
class Snils implements \Serializable, \Stringable
{
    /** @var int Минимальный страховой номер (001-001-998 XX) */
    const ID_MIN = 1001999;

    /** @var int Максимальный страховой номер (999-999-999 XX) */
    const ID_MAX = 999999999;

    /**
     * ID / Страховой номер (до 9 разрядов, без контрольной суммы)
     *
     * @var int
     */
    protected int $id;

    /**
     * Создание СНИЛСа из ID
     *
     * @param integer $id ID СНИЛСа
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /** @var string Канонический формат СНИЛСа: XXXXXXXXXYY (строка из цифр с ведущими нулями) */
    const FORMAT_CANONICAL = 'C';

    /** @var string Формат СНИЛСа: XXX-XXX-XXX YY (строка из цифр с ведущими нулями, разделённая дефисами и пробелом от контрольной суммы) */
    const FORMAT_SPACE = 'S';

    /** @var string Формат СНИЛСа: XXX-XXX-XXX-YY (строка из цифр с ведущими нулями, разделённая дефисами) */
    const FORMAT_HYPHEN = 'H';

    /**
     * Создание объекта из строки
     *
     * @param string|integer $snils СНИЛС
     * @param string|null $format Код формата: Snils::FORMAT_* (если null, то из значения удаляются все знаки-нецифры)
     * @return Snils|false
     */
    public static function createFromFormat(string|int $snils, string $format = null): Snils|false
    {
        $id = static::validate($snils, $format);
        return $id ? new Snils($id) : false;
    }

    /**
     * ID / Страховой номер
     *
     * @return  int
     */ 
    public function getID(): int
    {
        return $this->id;
    }

    /**
     * Задать ID / Страховой номер (без проверки контрольной суммы)
     *
     * @param  int  $id  ID СНИЛСа
     * @return  self
     */ 
    public function setID(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Вывод СНИЛСа в формате «XXXXXXXXXYY»
     *
     * @return  string
     */
    public function getCanonical(): string
    {
        return $this->format(self::FORMAT_CANONICAL);
    }

    /**
     * Вывод СНИЛСа в формате «XXX-XXX-XXX YY»
     *
     * @return  string
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
     * @return string 2 цифры
     */
    public static function checksum(string|int $id): string
    {
        $snils9 = str_pad((string) $id, 9, '0', STR_PAD_LEFT);

        $sum = 0;
        for ($pos = 9; $pos > 0; $pos--) {
            $sum += (int) ($snils9[9 - $pos] * $pos);
        }

        return substr(str_pad((string) ($sum % 101), 2, '0', STR_PAD_LEFT), -2);
    }

    /**
     * Проверка СНИЛСа (c контрольной суммой)
     *
     * @param string|int $snils СНИЛС
     * @param string|null $format Код формата: Snils::FORMAT_* (если null, то из значения удаляются все знаки-нецифры)
     * @return int|false ID СНИЛСа
     */
    public static function validate(string|int $snils, string $format = null): int|false
    {
        if ($format === null) {
            $snils = preg_replace('/[^0-9]/', '', (string) $snils);
            $format = static::FORMAT_CANONICAL;
        }

        [ $id, $checksum ] = match ($format) {
            static::FORMAT_CANONICAL => call_user_func(function(string|int $snils) {
                $snils = str_pad((string) $snils, 11, '0', STR_PAD_LEFT);
                return [ substr($snils, 0, 9), substr($snils, -2) ];
            }, $snils),

            static::FORMAT_SPACE,
            static::FORMAT_HYPHEN => call_user_func(function(string|int $snils, string $format) {
                $separator = $format === static::FORMAT_SPACE ? ' ' : '-';

                if (preg_match("/^(\d{3})-(\d{3})-(\d{3}){$separator}(\d{2})$/", $snils, $matches)) {
                    return [ $matches[1].$matches[2].$matches[3], $matches[4]];
                }
                return false;

            }, $snils, $format),
        };

        if ($id < static::ID_MIN || $id > static::ID_MAX) {
            return false;
        }

        return $checksum === static::checksum($id) ? (int) $id : false;
    }

    /**
     * Форматированный СНИЛС
     * 
     * @param string $format Код формата: Snils::FORMAT_*
     * @return  string
     */
    public function format(string $format = self::FORMAT_CANONICAL): string
    {
        $snils = str_pad((string) $this->id, 9, '0', STR_PAD_LEFT) . $this->getChecksum();

        return match ($format) {
            static::FORMAT_CANONICAL => $snils,

            static::FORMAT_SPACE,
            static::FORMAT_HYPHEN => substr($snils, 0, 3)
                .'-'.substr($snils, 3, 3)
                .'-'.substr($snils, 6, 3)
                .($format === static::FORMAT_HYPHEN ? '-' : ' ')
                .substr($snils, 9, 2)
            ,
        };
    }

    /** @inheritDoc */
    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    /**
     * Данные объекта для сериализации
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
        ];
    }

    /** @inheritDoc */
    public function unserialize($data): void
    {
        $this->__unserialize(unserialize($data));
    }

    /**
     * Перенос данных сериализации в объект
     *
     * @return array
     */
    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
    }

    /**
     * Данные для var_dump() (for DEBUG)
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            'id' => $this->id,

            '_canonical' => $this->getCanonical(),
            '_format'    => $this->format(self::FORMAT_SPACE),
            '_checksum'  => $this->getChecksum(),
        ];
    }
}
