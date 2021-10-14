<?php

declare(strict_types=1);

namespace Npub\Gos\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Npub\Gos\Snils;

/**
 * Ген СНИЛСа для Doctrine ORM Entity, хранящий в БД полный СНИЛС в виде строки из 11 цифр
 */
trait SnilsCanonicalTrait
{
    use SnilsTrait;

    /**
     * @var Snils|null СНИЛС
     * @ORM\Column(type="snils_сanonical", length=11, nullable=true, options={"fixed": true, "comment": "СНИЛС"})
     */
    protected Snils|null $snils = null;
}
