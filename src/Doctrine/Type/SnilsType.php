<?php

declare(strict_types=1);

namespace Npub\Gos\Doctrine\Type;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Npub\Gos\Snils;

use function implode;
use function is_int;
use function is_string;
use function sprintf;

/**
 * СНИЛС (тип для Doctrine ORM)
 * Хранит СНИЛС в виде 9 цифр (INT): без ведущих нулей и контрольной суммы.
 */
class SnilsType extends Type
{
    public const NAME = 'snils';

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
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

        throw new ConversionException(sprintf(
            'Unknown format to convert `%s` to `%s` database value. Available types: `%s`',
            $value,
            $this->getName(),
            implode('`, `', ['null', 'int', 'string', Snils::class]),
        ));
    }

    public function getBindingType(): ParameterType
    {
        return ParameterType::INTEGER;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
