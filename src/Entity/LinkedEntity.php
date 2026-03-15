<?php

namespace Prolyfix\QmBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Prolyfix\HolidayAndTime\Entity\TimeData;
use Prolyfix\QmBundle\Repository\LinkedEntityRepository;

#[ORM\Entity(repositoryClass: LinkedEntityRepository::class)]
#[ORM\Table(name: 'qm_linked_entity')]
#[ORM\Index(columns: ['entity', 'entity_id'], name: 'idx_qm_linked_entity_target')]
#[ApiResource(
    normalizationContext: ['groups' => ['module_configuration_value:read']],
    denormalizationContext: ['groups' => ['module_configuration_value:write']],
)]
class LinkedEntity extends TimeData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'linkedEntities')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Paragraph $paragraph = null;

    #[ORM\Column(length: 255)]
    private ?string $entity = null;

    #[ORM\Column(name: 'entity_id')]
    private ?int $entityId = null;

    #[ORM\Column(length: 255)]
    private ?string $documentType = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isTemplate = false;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $url = null;

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

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function setEntity(string $entity): static
    {
        $this->entity = $entity;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getDocumentType(): ?string
    {
        return $this->documentType;
    }

    public function setDocumentType(string $documentType): static
    {
        $this->documentType = $documentType;

        return $this;
    }

    public function isTemplate(): bool
    {
        return $this->isTemplate;
    }

    public function setIsTemplate(bool $isTemplate): static
    {
        $this->isTemplate = $isTemplate;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s#%d (%s)', $this->entity ?? 'Entity', $this->entityId ?? 0, $this->documentType ?? 'n/a');
    }
}
