# Библиотека для работы со СНИЛСом (РФ)
[![Latest Stable Version](http://poser.pugx.org/npub/gos/v)](https://packagist.org/packages/npub/gos)
[![PHP Version Require](http://poser.pugx.org/npub/gos/require/php)](https://packagist.org/packages/npub/gos)
[![PHP Composer](https://github.com/npub/gos/actions/workflows/php.yml/badge.svg)](https://github.com/npub/gos/actions/workflows/php.yml)
[![License](http://poser.pugx.org/npub/gos/license)](https://packagist.org/packages/npub/gos)

В настоящий момент реализована проверка и хранение СНИЛСа с поддержкой Symfony Framework и Docrine ORM Entity Custom Type.

## Установка
```bash
composer require npub/gos
```

### Подключение Doctrine типа к Symfony

```yaml
# config/packages/doctrine.yaml

doctrine:
  dbal:
    types:
      snils: Npub\Gos\Doctrine\Type\SnilsType
      snils_canonical: Npub\Gos\Doctrine\Type\SnilsCanonicalType
```

### Подключение Doctrine типа без Symfony
```php
<?php

use Doctrine\DBAL\Types\Type;
use Npub\Gos\Doctrine\Type\SnilsCanonicalType;
use Npub\Gos\Doctrine\Type\SnilsType;

Type::addType('snils', SnilsType::class);
Type::addType('snils_canonical', SnilsCanonicalType::class);

```

## Использование типа поля Snils в Entity
```php
# Entity/Person.php
<?php

use Doctrine\ORM\Mapping as ORM;
use Npub\Gos\Doctrine\Trait\Snilsable;

/**
 * @ORM\Entity
 */
class Person
{
    use Snilsable;
    …
}
```

## Использование объекта Snils
```php
<?php

use Npub\Gos\Snils;

// Валидация строки СНИЛСв
echo Snils::validate('123-456-789 64');  // 123456789
echo Snils::validate(12345678964, Snils::FORMAT_CANONICAL);  // 123456789
var_dump(Snils::validate('123-456-789 55'));  // bool(false)

// Форматирование СНИЛСа из строки
echo Snils::stringFormat('12345678955');  // 123-456-789 64

// Создание объекта сущности СНИЛСа из его ID (7–9 цифр)
$snils = new Snils(123456789);
var_dump($snils->isValid());  // bool(true)
echo $snils->getCanonical();  // 12345678964
echo $snils->getID();  // 123456789
echo $snils->getChecksum();  // 64
echo $snils->format(Snils::FORMAT_SPACE);  // 123-456-789 64
echo $snils->format(Snils::FORMAT_HYPHEN);  // 123-456-789-64
echo $snils;  // 123-456-789 64

// Создание объекта сущности СНИЛСа из строки
/** @var Snils|false $snils */
$snils = Snils::createFromFormat('123-456-789 64');
print_r($snils);
// Outputs additional info:
//
// Npub\Gos\Snils Object
// (
//     [_id] => 123456789
//     [_is_valid] => true
//     [_checksum] => 64
//     [_canonical] => 12345678964
//     [__toString] => 123-456-789 64
// )
```
