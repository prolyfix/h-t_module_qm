<?php

namespace Prolyfix\QmBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Prolyfix\HolidayAndTime\Entity\Module\Module;
use Prolyfix\HolidayAndTime\Entity\Module\ModuleConfigurationValue;
use Prolyfix\HolidayAndTime\Event\ModifiableArrayEvent;
use Prolyfix\QmBundle\Attribute\BelongToQm;
use Prolyfix\QmBundle\Controller\Admin\ParagraphCrudController;
use Prolyfix\QmBundle\Entity\Paragraph;
use Prolyfix\QmBundle\ProlyfixQmBundle;
use Prolyfix\QmBundle\Repository\LinkedEntityRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ContextHeaderListener
{
    public function __construct(
        private readonly LinkedEntityRepository $linkedEntityRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security
    ) {
    }

    public function onAppContextHeader(ModifiableArrayEvent $event): void
    {
        $data = $event->getData();
        $entity = $data['entity'] ?? null;

        $module = $this->entityManager->getRepository(Module::class)->findOneBy(['class' => ProlyfixQmBundle::class]);
        if(!$this->entityManager->getRepository(ModuleConfigurationValue::class)->hasModuleEnabled($this->security->getUser()->getCompany(),$module ))
        {
            return;
        }

        if (!\is_object($entity) || !method_exists($entity, 'getId')) {
            return;
        }

        if (!$this->hasBelongToQmAttribute($entity)) {
            return;
        }

        // Paragraph is the target container itself, so offering self-linking is not useful.
        if ($entity instanceof Paragraph) {
            return;
        }

        $entityId = $entity->getId();
        if (!\is_int($entityId)) {
            return;
        }

        $headerHtml = $data['html'] ?? [];
        if (!\is_array($headerHtml)) {
            $headerHtml = [];
        }

        $linkedEntity = $this->linkedEntityRepository->findOneForEntity($entity::class, $entityId);
        if ($linkedEntity !== null && $linkedEntity->getParagraph() !== null) {
            $paragraph = $linkedEntity->getParagraph();
            $title = htmlspecialchars($paragraph->getTitle() ?? 'Untitled paragraph', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $documentType = htmlspecialchars($linkedEntity->getDocumentType() ?? 'n/a', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $paragraphUrl = htmlspecialchars((clone $this->adminUrlGenerator)
                ->setController(ParagraphCrudController::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($paragraph->getId())
                ->generateUrl(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $headerHtml[] = sprintf(
                '<div class="alert py-2 px-3 mb-2" style="background-color:#f672a7;color:#fff;border:none;"><strong><i class="fas fa-book-open me-1"></i>QM:</strong> Linked to paragraph <a href="%s" class="fw-semibold" style="color:#fff;text-decoration:underline;">%s</a> (%s)</div>',
                $paragraphUrl,
                $title,
                $documentType
            );
        } else {
            $currentUrl = $this->requestStack->getCurrentRequest()?->getUri() ?? '';
            $sidebarUrl = htmlspecialchars($this->urlGenerator->generate('qm_linked_entity_sidebar_add', [
                'entity' => $entity::class,
                'entityId' => $entityId,
                'documentType' => (new \ReflectionClass($entity))->getShortName(),
                'url' => $currentUrl,
            ]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $headerHtml[] = sprintf(
                '<span data-controller="modal-form"><a href="%1$s" data-action="click->modal-form#openModal" data-url="%1$s" class="btn btn-sm" style="background-color:#f672a7;color:#fff;border:none;"><i class="fas fa-book-open me-1"></i>Add to QM paragraph</a></span>',
                $sidebarUrl
            );
        }

        $data['html'] = $headerHtml;
        $event->setData($data);
    }

    private function hasBelongToQmAttribute(object $entity): bool
    {
        $reflection = new \ReflectionClass($entity);

        do {
            if ($reflection->getAttributes(BelongToQm::class) !== []) {
                return true;
            }

            $reflection = $reflection->getParentClass();
        } while ($reflection !== false);

        return false;
    }
}
