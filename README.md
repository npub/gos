# Библиотека для работы с государственными идентификаторами РФ
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
use Npub\Gos\Doctrine\Entity\SnilsTrait;

/**
 * @ORM\Entity
 */
class Person
{
    use SnilsTrait;
    …
}
```

## Использование объекта Snils
```php
<?php

use Npub\Gos\Snils;

$snils = new Snils(123456789);
echo $snils->getCanonical();  // 12345678964
echo $snils->getID();  // 123456789
echo $snils->getChecksum();  // 64
echo $snils->format(Snils::FORMAT_HYPHEN);  // 123-456-789-64
echo $snils;  // 123-456-789 64

echo Snils::validate('123-456-789 64');  // 123456789

$snils = Snils::createFromFormat('123_456_789 64');
print_r($snils);
// Outputs additional info:
//
// Npub\Gos\Snils Object
// (
//     [_id] => 123456789
//     [_checksum] => 64
//     [_canonical] => 12345678964
//     [__toString] => 123-456-789 64
// )
```
