<?php

declare(strict_types=1);

namespace Npub\Gos\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Npub\Gos\Snils;
use ValueError;

/**
 * Ген СНИЛСа для Doctrine ORM Entity, хранящий в БД ID СНИЛСа в виде числа из 7–9 цифр
 */
trait SnilsTrait
{
    /**
     * @var Snils|null СНИЛС
     * @ORM\Column(type="snils", nullable=true, options={"unsigned": true, "comment": "СНИЛС"})
     */
    protected Snils|null $snils = null;

    /**
     * СНИЛС
     *
     * @return Snils|null
     */
    public function getSnils(): Snils|null
    {
        return $this->snils;
    }

    /**
     * СНИЛС задан?
     *
     * @return bool
     */
    public function hasSnils(): bool
    {
        return (bool) $this->snils;
    }

    /**
     * Задать СНИЛС
     *
     * @param  Snils|string|int|null  $snils  СНИЛС
     * @return  self
     *
     * @throws ValueError
     */
    public function setSnils(Snils|string|int|null $snils): self
    {
        if (is_string($snils) || is_int($snils)) {
            if ($snils === '') {
                $this->snils = null;
                return $this;
            }

            $snils = Snils::createFromFormat($snils);
            if (! $snils) {
                throw new ValueError('Некорректный СНИЛС');
            }
        }

        if (! ($this->snils instanceof Snils && $this->snils->isEqual($snils))) {
            // Заменяем значение только если оно изменилось
            $this->snils = $snils;
        }
        return $this;
    }
}
