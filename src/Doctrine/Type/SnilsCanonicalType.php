<?php

declare(strict_types=1);

namespace Npub\Gos\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;
use is_null;
use Npub\Gos\Snils;

/**
 * СНИЛС (тип для Doctrine ORM, каноническая запись)
 * Хранит СНИЛС в виде строки из 11 цифр (VARCHAR(11)): c ведущими нулями и контрольной суммой.
 *
 * Рекомендуется использзовать тип SnilsType ("snils") как более оптимальный с точки зрения производительности, а данный тип
 * использовать только для обратной совместимости со старым кодом в процессе миграции.
 *
 * @author Александр Васильев <a.vasilyev@1sept.ru>
 */
class SnilsCanonicalType extends StringType
{
    /** @var string Имя типа */
    const NAME = 'snils_canonical';

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

        return Snils::createFromFormat($value, Snils::FORMAT_CANONICAL);
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
            if ($snils = Snils::createFromFormat($value)) {
                return $snils->getCanonical();
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
