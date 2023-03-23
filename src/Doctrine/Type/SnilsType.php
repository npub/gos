<?php

declare(strict_types=1);

namespace Npub\Gos\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\IntegerType;
use Npub\Gos\Snils;

use function is_int;
use function is_string;

/**
 * СНИЛС (тип для Doctrine ORM)
 * Хранит СНИЛС в виде 9 цифр (INT): без ведущих нулей и контрольной суммы.
 */
class SnilsType extends IntegerType
{
    public const NAME = 'snils';

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

        return new Snils((int) $value);
    }

    /**
     * {@inheritdoc}
     *
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): int|null
    {
        if ($value === null) {
            return $value;
        }

        if ($value instanceof Snils) {
            return $value->getID();
        }

        if (is_int($value) || is_string($value)) {
            $snils = Snils::createFromFormat($value);
            if ($snils instanceof Snils) {
                return $snils->getID();
            }
        }

        throw ConversionException::conversionFailedInvalidType(
            value: $value,
            toType: $this->getName(),
            possibleTypes: ['null', 'int', 'string', Snils::class],
        );
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
