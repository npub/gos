<?php

declare(strict_types=1);

namespace Npub\Gos\Doctrine\Type;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Npub\Gos\Snils;

use is_null;

/**
 * СНИЛС (тип для Doctrine ORM)
 * Хранит значение в виде 9 цифр СНИЛСа без лидирующих нулей и контрольной суммы (INT UNSIGNED).
 *
 * @author Александр Васильев <a.vasilyev@1sept.ru>
 */
class SnilsType extends Type
{
    /** @var string Имя типа */
    const NAME = 'snils';

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['unsigned'] = true;

        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    /**
     * Converts a value from its database representation to its PHP representation of this type.
     * 
     * @param string $value — The value to convert.
     * @param AbstractPlatform $platform — The currently used database platform.
     * @return Snils|null
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): Snils|null
    {
        return $value === null ? null : new Snils((int) $value);
    }

    /**
     * {@inheritdoc}
     *
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): int|null
    {
        if (!($value instanceof Snils || is_null($value))) {
            throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['null', Snils::class]);
        }

        if ($value === null) {
            return null;
        }

        return $value->getID();
    }

    /**
     * {@inheritdoc}
     */
    public function getBindingType(): int
    {
        return ParameterType::INTEGER;
    }
}
