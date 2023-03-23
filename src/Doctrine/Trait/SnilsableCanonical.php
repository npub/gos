<?php

declare(strict_types=1);

namespace Npub\Gos\Doctrine\Trait;

use Doctrine\ORM\Mapping as ORM;
use Npub\Gos\Snils;

/**
 * Ген полного СНИЛСа как свойства объекта Doctrine ORM Entity
 *
 * В БД хранит ID СНИЛСа в виде числа из 11 цифр (с контрольной суммой).
 * Рекомендуется использовать Trait Snilsable как более оптимальный способ хранения.
 */
trait SnilsableCanonical
{
    use Snilsable;

    /**
     * @ORM\Column(type="snils_canonical", length=11, nullable=true, options={"fixed": true, "comment": "СНИЛС"})
     *
     * @var Snils СНИЛС
     */
    #[ORM\Column(type: 'snils_canonical', length: 11, nullable: true, options: ['fixed' => true, 'comment' => 'СНИЛС'])]
    protected ?Snils $snils = null;
}
