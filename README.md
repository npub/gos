# Библиотека для работы с государственными идентификаторами РФ
В настоящий момент реализована проверка и хранение СНИЛСа с поддержкой Symfony Framework и Docrine ORM Entity Custom Type.

## Установка
```bash
composer require npub/gos
```

### Подключение типа в Symfony

```yaml
# config/packages/doctrine.yaml

doctrine:
    dbal:
        types:
            snils: Npub\Gos\Doctrine\Type\SnilsType
```

### Подключение типа Doctrine (без Symfony)
```php
<?php

use Npub\Gos\Doctrine\Type\SnilsType;

\Doctrine\DBAL\Types\Type::addType('snils', SnilsType::class);
```