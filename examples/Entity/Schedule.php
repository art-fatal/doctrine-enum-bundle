<?php

/**
 * EXAMPLE FILE - Complete usage example
 *
 * This file demonstrates how to use the DayOfWeek enum
 * in a Doctrine entity with the DoctrineEnumBundle.
 *
 * Copy the relevant parts to your own entities.
 */

namespace YourApp\Entity;

use YourApp\Constant\Enum\DayOfWeek;
use YourApp\Type\Enum\DayOfWeekEnumType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Example entity representing a weekly schedule.
 */
#[ORM\Entity]
#[ORM\Table(name: 'schedule')]
class Schedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * The day of the week for this schedule entry.
     *
     * You can use either:
     * - DayOfWeekEnumType::NAME (type-safe, IDE autocomplete)
     * - 'day_of_week' (string literal)
     *
     * Database column will be:
     * ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')
     */
    #[ORM\Column(
        type: DayOfWeekEnumType::NAME,
        options: ['default' => DayOfWeek::MONDAY->value]
    )]
    #[Assert\NotNull]
    #[Assert\Choice(choices: DayOfWeek::ALL)]
    private ?DayOfWeek $dayOfWeek = DayOfWeek::MONDAY;

    #[ORM\Column(type: 'time')]
    #[Assert\NotNull]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: 'time')]
    #[Assert\NotNull]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $activity = null;

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDayOfWeek(): ?DayOfWeek
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(?DayOfWeek $dayOfWeek): self
    {
        $this->dayOfWeek = $dayOfWeek;
        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getActivity(): ?string
    {
        return $this->activity;
    }

    public function setActivity(string $activity): self
    {
        $this->activity = $activity;
        return $this;
    }

    /**
     * Example business logic using enum methods
     */
    public function isWeekendActivity(): bool
    {
        return $this->dayOfWeek?->isWeekend() ?? false;
    }

    /**
     * Example method showing enum usage
     */
    public function getDayLabel(): string
    {
        return $this->dayOfWeek?->label() ?? 'Unknown';
    }
}