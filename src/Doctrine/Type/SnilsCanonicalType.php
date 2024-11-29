<?php

declare(strict_types=1);

namespace Npub\Gos\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;
use Npub\Gos\Snils;

use function implode;
use function is_int;
use function is_string;
use function sprintf;

/**
 * СНИЛС (тип для Doctrine ORM, каноническая запись)
 * Тип хранит СНИЛС в виде строки из 11 цифр (VARCHAR(11)): c ведущими нулями и контрольной суммой.
 *
 * Рекомендуется использовать тип SnilsType ("snils") как более оптимальный с точки зрения производительности,
 * а данный тип использовать только для обратной совместимости со старым кодом в процессе миграции.
 */
class SnilsCanonicalType extends StringType
{
    public const NAME = 'snils_canonical';

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * Converts a value from its database representation to its PHP representation of this type.
     *
     * @param mixed            $value    — The value to convert.
     * @param AbstractPlatform $platform — The currently used database platform.
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): Snils|null
    {
        if ($value === null || $value instanceof Snils) {
            return $value;
        }

        if (! is_int($value) && ! is_string($value)) {
            throw new ConversionException('Unknown format to convert to PHP value.');
        }

        $snils = Snils::createFromFormat($value, Snils::FORMAT_CANONICAL);

        return $snils instanceof Snils ? $snils : null;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string|null
    {
        if ($value === null) {
            return $value;
        }

        if ($value instanceof Snils) {
            return $value->getCanonical();
        }

        if (is_int($value) || is_string($value)) {
            $snils = Snils::createFromFormat($value);
            if ($snils instanceof Snils) {
                return $snils->getCanonical();
            }
        }

        throw new ConversionException(sprintf(
            'Unknown format to convert `%s` to `%s` database value. Available types: `%s`',
            $value,
            $this->getName(),
            implode('`, `', ['null', 'int', 'string', Snils::class]),
        ));
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
