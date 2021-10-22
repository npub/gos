<?php

declare(strict_types=1);

namespace Npub\Gos\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\IntegerType;
use Npub\Gos\Snils;
use is_null;

/**
 * СНИЛС (тип для Doctrine ORM)
 * Хранит СНИЛС в виде 9 цифр (INT): без ведущих нулей и контрольной суммы.
 *
 * @author Александр Васильев <a.vasilyev@1sept.ru>
 */
class SnilsType extends IntegerType
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
     * Converts a value from its database representation to its PHP representation of this type.
     *
     * @param string $value — The value to convert.
     * @param AbstractPlatform $platform — The currently used database platform.
     * @return Snils|null
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): Snils|null
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
            if ($snils = Snils::createFromFormat($value)) {
                return $snils->getID();
            }
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['null', 'int', 'string', Snils::class]);
    }

    /**
     * @inheritdoc
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
