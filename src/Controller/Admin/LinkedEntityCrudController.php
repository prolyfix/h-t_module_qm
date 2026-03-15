<?php

namespace Prolyfix\QmBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Prolyfix\HolidayAndTime\Controller\Admin\BaseCrudController;
use Prolyfix\HolidayAndTime\Entity\Media;
use Prolyfix\QmBundle\Entity\LinkedEntity;
use Prolyfix\QmBundle\Entity\Paragraph;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class LinkedEntityCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return LinkedEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('paragraph')->renderAsNativeWidget(),
            TextField::new('entity')->setHelp('FQCN of the linked entity (e.g. Prolyfix\\KnowledgebaseBundle\\Entity\\Knowledgebase)'),
            IntegerField::new('entityId'),
            TextField::new('documentType'),
            BooleanField::new('isTemplate')->renderAsSwitch(false),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'QM Linked Entities')
            ->setDefaultSort(['id' => 'DESC']);
    }

    #[Route('/admin/qm/linked-entity/sidebar-add', name: 'qm_linked_entity_sidebar_add', methods: ['GET', 'POST'])]
    public function addToParagraph(Request $request): Response
    {
        $entityClass = $request->query->get('entity');
        $entityId = $request->query->getInt('entityId');
        $documentType = $request->query->get('documentType');
        $entityUrl = $request->query->get('url');

        if (!is_string($entityClass) || '' === $entityClass || $entityId <= 0) {
            return new Response('Missing entity context.', Response::HTTP_BAD_REQUEST);
        }

        if ($this->em->getRepository(LinkedEntity::class)->findOneForEntity($entityClass, $entityId) !== null) {
            return new Response('Entity is already linked to a paragraph.', Response::HTTP_CONFLICT);
        }

        $linkedEntity = new LinkedEntity();
        $linkedEntity->setEntity($entityClass);
        $linkedEntity->setEntityId($entityId);
        $linkedEntity->setDocumentType(is_string($documentType) && '' !== $documentType ? $documentType : (new \ReflectionClass($entityClass))->getShortName());
        if (is_string($entityUrl) && '' !== $entityUrl) {
            $linkedEntity->setUrl($entityUrl);
        }

        $form = $this->createFormBuilder($linkedEntity)
            ->add('paragraph', EntityType::class, [
                'class' => Paragraph::class,
                'choice_label' => 'title',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('documentType', TextType::class, [
                'attr' => ['class' => 'form-control'],
            ])
            ->add('isTemplate', CheckboxType::class, [
                'required' => false,
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($linkedEntity);
            $this->em->flush();

            return new JsonResponse([
                'status' => 'success',
                'linkedEntityId' => $linkedEntity->getId(),
            ]);
        }

        return $this->render('@ProlyfixQm/sidebar_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/qm/paragraph/{paragraphId}/attach-media', name: 'qm_paragraph_attach_media', methods: ['GET', 'POST'])]
    public function attachMedia(int $paragraphId, Request $request, KernelInterface $kernel): Response
    {
        $paragraph = $this->em->getRepository(Paragraph::class)->find($paragraphId);
        if ($paragraph === null) {
            return new Response('Paragraph not found.', Response::HTTP_NOT_FOUND);
        }

        $form = $this->createFormBuilder()
            ->add('file', FileType::class, [
                'label' => 'File',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('documentType', TextType::class, [
                'label' => 'Document type',
                'data' => 'Media',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('isTemplate', CheckboxType::class, [
                'label' => 'Is template',
                'required' => false,
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();

            $media = new Media();
            $media->setFile($uploadedFile);
            $media->setReadableName($uploadedFile->getClientOriginalName());
            $this->em->persist($media);
            $this->em->flush();

            $linkedEntity = new LinkedEntity();
            $linkedEntity->setParagraph($paragraph);
            $linkedEntity->setEntity(Media::class);
            $linkedEntity->setEntityId($media->getId());
            $linkedEntity->setDocumentType($form->get('documentType')->getData() ?: 'Media');
            $linkedEntity->setIsTemplate($form->get('isTemplate')->getData() ?? false);
            $this->em->persist($linkedEntity);
            $this->em->flush();

            return new JsonResponse([
                'status' => 'success',
                'linkedEntityId' => $linkedEntity->getId(),
            ]);
        }

        return $this->render('@ProlyfixQm/sidebar_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/qm/media/{mediaId}/download', name: 'qm_media_download', methods: ['GET'])]
    public function downloadMedia(int $mediaId, KernelInterface $kernel): Response
    {
        $media = $this->em->getRepository(Media::class)->find($mediaId);
        if ($media === null || $media->getFilename() === null) {
            throw $this->createNotFoundException('Media not found.');
        }

        $path = $kernel->getProjectDir() . '/private/medias/' . $media->getFilename();
        if (!file_exists($path)) {
            throw $this->createNotFoundException('File not found on disk.');
        }

        $response = new BinaryFileResponse($path);
        $disposition = $media->getReadableName()
            ? ResponseHeaderBag::DISPOSITION_ATTACHMENT
            : ResponseHeaderBag::DISPOSITION_INLINE;
        $response->setContentDisposition($disposition, $media->getReadableName() ?? $media->getFilename());

        return $response;
    }
}
