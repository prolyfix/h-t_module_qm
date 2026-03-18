<?php

namespace Prolyfix\QmBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Prolyfix\HolidayAndTime\Attribute\SearchableEntity;
use Prolyfix\HolidayAndTime\Attribute\SearchableField;
use Prolyfix\HolidayAndTime\Entity\TimeData;
use Prolyfix\QmBundle\Repository\ParagraphRepository;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ParagraphRepository::class)]
#[ORM\Table(name: 'qm_paragraph')]
#[Vich\Uploadable]
#[ApiResource(
    normalizationContext: ['groups' => ['module_configuration_value:read']],
    denormalizationContext: ['groups' => ['module_configuration_value:write']],
)]
#[SearchableEntity(controller: 'Prolyfix\\QmBundle\\Controller\\Admin\\ParagraphCrudController')]
class Paragraph extends TimeData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?self $parentParagraph = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parentParagraph')]
    private Collection $children;

    /**
     * @var Collection<int, LinkedEntity>
     */
    #[ORM\OneToMany(targetEntity: LinkedEntity::class, mappedBy: 'paragraph', orphanRemoval: true)]
    private Collection $linkedEntities;

    /**
     * @var Collection<int, Action>
     */
    #[ORM\OneToMany(targetEntity: Action::class, mappedBy: 'paragraph', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $actions;

    #[ORM\Column(length: 255)]
    #[SearchableField]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[SearchableField]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filename = null;

    #[Vich\UploadableField(mapping: 'medias', fileNameProperty: 'filename')]
    private ?File $file = null;

    public function __construct()
    {
        parent::__construct();
        $this->children = new ArrayCollection();
        $this->linkedEntities = new ArrayCollection();
        $this->actions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParentParagraph(): ?self
    {
        return $this->parentParagraph;
    }

    public function setParentParagraph(?self $parentParagraph): static
    {
        $this->parentParagraph = $parentParagraph;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParentParagraph($this);
        }

        return $this;
    }

    public function removeChild(self $child): static
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParentParagraph() === $this) {
                $child->setParentParagraph(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LinkedEntity>
     */
    public function getLinkedEntities(): Collection
    {
        return $this->linkedEntities;
    }

    public function addLinkedEntity(LinkedEntity $linkedEntity): static
    {
        if (!$this->linkedEntities->contains($linkedEntity)) {
            $this->linkedEntities->add($linkedEntity);
            $linkedEntity->setParagraph($this);
        }

        return $this;
    }

    public function removeLinkedEntity(LinkedEntity $linkedEntity): static
    {
        if ($this->linkedEntities->removeElement($linkedEntity)) {
            if ($linkedEntity->getParagraph() === $this) {
                $linkedEntity->setParagraph(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Action>
     */
    public function getActions(): Collection
    {
        return $this->actions;
    }

    /**
     * @return Collection<int, Action>
     */
    public function getActionsOrderedByCreationDate(): Collection
    {
        $actions = $this->actions->toArray();

        usort($actions, static function (Action $left, Action $right): int {
            $leftDate = $left->getCreationDate();
            $rightDate = $right->getCreationDate();

            $leftTimestamp = $leftDate?->getTimestamp() ?? 0;
            $rightTimestamp = $rightDate?->getTimestamp() ?? 0;

            return $leftTimestamp <=> $rightTimestamp;
        });

        return new ArrayCollection($actions);
    }

    public function addAction(Action $action): static
    {
        if (!$this->actions->contains($action)) {
            $this->actions->add($action);
            $action->setParagraph($this);
        }

        return $this;
    }

    public function removeAction(Action $action): static
    {
        if ($this->actions->removeElement($action)) {
            if ($action->getParagraph() === $this) {
                $action->setParagraph(null);
            }
        }

        return $this;
    }

    public function getDoneActionsCount(): int
    {
        return $this->actions->filter(static fn (Action $action): bool => $action->isDone())->count();
    }

    public function getTotalActionsCount(): int
    {
        return $this->actions->count();
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function __toString(): string
    {
        return $this->title ?? ('Paragraph #' . $this->id);
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): static
    {
        $this->file = $file;
        if ($file !== null) {
            $this->setUpdatedDate(new \DateTime());
        }

        return $this;
    }
}
