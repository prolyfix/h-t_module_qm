<?php

namespace Prolyfix\QmBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Prolyfix\HolidayAndTime\Entity\TimeData;
use Prolyfix\QmBundle\Repository\ActionRepository;

#[ORM\Entity(repositoryClass: ActionRepository::class)]
#[ORM\Table(name: 'qm_action')]
#[ApiResource(
    normalizationContext: ['groups' => ['module_configuration_value:read']],
    denormalizationContext: ['groups' => ['module_configuration_value:write']],
)]
class Action extends TimeData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'actions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Paragraph $paragraph = null;

    #[ORM\Column(length: 255)]
    private ?string $indication = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $hint = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $done = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParagraph(): ?Paragraph
    {
        return $this->paragraph;
    }

    public function setParagraph(?Paragraph $paragraph): static
    {
        $this->paragraph = $paragraph;

        return $this;
    }

    public function getIndication(): ?string
    {
        return $this->indication;
    }

    public function setIndication(string $indication): static
    {
        $this->indication = $indication;

        return $this;
    }

    public function getHint(): ?string
    {
        return $this->hint;
    }

    public function setHint(?string $hint): static
    {
        $this->hint = $hint;

        return $this;
    }

    public function isDone(): bool
    {
        return $this->done;
    }

    public function setDone(bool $done): static
    {
        $this->done = $done;

        return $this;
    }

    public function __toString(): string
    {
        return $this->indication ?? ('Action #' . $this->id);
    }
}
