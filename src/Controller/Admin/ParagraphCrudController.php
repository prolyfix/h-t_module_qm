<?php

namespace Prolyfix\QmBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Prolyfix\QmBundle\Entity\LinkedEntity;
use Prolyfix\HolidayAndTime\Controller\Admin\BaseCrudController;
use Prolyfix\QmBundle\Entity\Paragraph;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Vich\UploaderBundle\Form\Type\VichFileType;

class ParagraphCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return Paragraph::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            AssociationField::new('parentParagraph')->renderAsNativeWidget(),
            TextField::new('title'),
            TextEditorField::new('description')->hideOnIndex(),
        ];

        if (in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT], true)) {
            $fields[] = Field::new('file')
                ->setFormType(VichFileType::class)
                ->setLabel('Document file')
                ->setRequired(false);
        }

        return $fields;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'QM Paragraphs')
            ->setDefaultSort(['title' => 'ASC'])
            ->overrideTemplate('crud/detail', '@ProlyfixQm/paragraph_detail.html.twig')
            ->overrideTemplate('crud/index', '@ProlyfixQm/paragraph_index.html.twig');
    }

    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        if ($responseParameters->get('pageName') === Crud::PAGE_INDEX) {
            $rootParagraphs = $this->em->getRepository(Paragraph::class)
                ->findBy(['parentParagraph' => null], ['title' => 'ASC']);
            $responseParameters->set('rootParagraphs', $rootParagraphs);
        }

        if ($responseParameters->get('pageName') === Crud::PAGE_DETAIL) {
            $entity = $responseParameters->get('entity');
            if ($entity !== null) {
                $paragraph = $entity->getInstance();
                $linkedEntities = $paragraph->getLinkedEntities();
                $linkedDocumentUrls = [];

                foreach ($linkedEntities as $linkedEntity) {
                    if (!$linkedEntity instanceof LinkedEntity) {
                        continue;
                    }

                    $controllerClass = $this->resolveCrudControllerForEntity((string) $linkedEntity->getEntity());
                    if ($controllerClass === null || $linkedEntity->getEntityId() === null) {
                        continue;
                    }

                    $linkedDocumentUrls[$linkedEntity->getId()] = (clone $this->adminUrlGenerator)
                        ->setController($controllerClass)
                        ->setAction(Action::DETAIL)
                        ->setEntityId($linkedEntity->getEntityId())
                        ->generateUrl();
                }

                $responseParameters->set('linkedEntities', $linkedEntities);
                $responseParameters->set('linkedDocumentUrls', $linkedDocumentUrls);
                $responseParameters->set('paragraphInstance', $paragraph);
            }
        }

        return $responseParameters;
    }

    private function resolveCrudControllerForEntity(string $entityClass): ?string
    {
        static $entityToController = null;

        if ($entityToController === null) {
            $entityToController = [];
            $controllerFiles = glob($this->getParameter('kernel.project_dir') . '/../synpraxis-bundle/*/src/Controller/**/*CrudController.php', GLOB_BRACE);

            foreach ($controllerFiles ?: [] as $file) {
                $content = @file_get_contents($file);
                if ($content === false) {
                    continue;
                }

                if (!preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
                    continue;
                }
                if (!preg_match('/class\s+([A-Za-z0-9_]+)/', $content, $classMatch)) {
                    continue;
                }

                $controllerClass = $namespaceMatch[1] . '\\' . $classMatch[1];
                if (!class_exists($controllerClass) || !method_exists($controllerClass, 'getEntityFqcn')) {
                    continue;
                }

                try {
                    $mappedEntityClass = $controllerClass::getEntityFqcn();
                    if (is_string($mappedEntityClass) && $mappedEntityClass !== '') {
                        $entityToController[$mappedEntityClass] = $controllerClass;
                    }
                } catch (\Throwable) {
                    // Skip controllers that cannot resolve statically at runtime.
                }
            }
        }

        return $entityToController[$entityClass] ?? null;
    }

    #[Route('/admin/qm/paragraph/{id}/download', name: 'qm_paragraph_download', methods: ['GET'])]
    public function downloadFile(int $id, KernelInterface $kernel): Response
    {
        $paragraph = $this->em->getRepository(Paragraph::class)->find($id);
        if ($paragraph === null || $paragraph->getFilename() === null) {
            throw $this->createNotFoundException('File not found.');
        }

        $path = $kernel->getProjectDir() . '/private/medias/' . $paragraph->getFilename();
        if (!file_exists($path)) {
            throw $this->createNotFoundException('File not found on disk.');
        }

        return new BinaryFileResponse($path);
    }
}
