# Gos
Библиотека для работы со СНИЛСом и др.

## Установка
`composer require npub/gos`

### Symfony

```
# config/packages/doctrine.yaml
doctrine:
    dbal:
        types:
            snils: Npub\Gos\Doctrine\Type\SnilsType
```