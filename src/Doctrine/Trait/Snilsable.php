<?php declare(strict_types=1);

namespace Npub\Gos\Doctrine\Trait;

use Doctrine\ORM\Mapping as ORM;
use Npub\Gos\Snils;
use ValueError;

use function is_int;
use function is_string;

/**
 * Ген значимой части СНИЛСа как свойства объекта Doctrine ORM Entity
 *
 * В БД хранит ID СНИЛСа в виде числа из 7–9 цифр (без контрольной суммы).
 */
trait Snilsable
{
    /**
     * @var Snils СНИЛС
	 *
	 * @ORM\Column(type="snils", nullable=true, options={"unsigned": true, "comment": "СНИЛС"})
     */
	#[ORM\Column(type: 'snils', nullable: true, options: ['unsigned' => true, 'comment' => 'СНИЛС'])]
    protected ?Snils $snils = null;

    /**
     * СНИЛС
     */
    public function getSnils(): ?Snils
    {
        return $this->snils;
    }

    /**
     * СНИЛС задан?
     */
    public function hasSnils(): bool
    {
        return (bool) $this->snils;
    }

    /**
     * Задать СНИЛС
     *
     * @param  Snils|string|int|null $snils СНИЛС
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
