<?php

namespace Prolyfix\QmBundle;

use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Prolyfix\HolidayAndTime\Entity\Module\ModuleRight;
use Prolyfix\HolidayAndTime\Module\ModuleBundle;
use Prolyfix\QmBundle\Entity\LinkedEntity;
use Prolyfix\QmBundle\Entity\Paragraph;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ProlyfixQmBundle extends ModuleBundle
{
    private AuthorizationCheckerInterface $authorizationChecker;

    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker): void
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public const IS_MODULE = true;

    public static function getShortName(): string
    {
        return 'QmBundle';
    }

    public static function getModuleName(): string
    {
        return 'QM';
    }

    public static function getModuleDescription(): string
    {
        return 'Quality management module';
    }

    public static function getModuleType(): string
    {
        return 'module';
    }

    public static function getModuleConfiguration(): array
    {
        return [];
    }

    public static function getModuleRights(): array
    {
        return [
            (new ModuleRight())
                ->setModuleAction(['list', 'show', 'edit', 'new', 'delete'])
                ->setCoverage('company')
                ->setRole('ROLE_MANAGER')
                ->setEntityClass(Paragraph::class),
            (new ModuleRight())
                ->setModuleAction(['list', 'show', 'edit', 'new', 'delete'])
                ->setCoverage('company')
                ->setRole('ROLE_MANAGER')
                ->setEntityClass(LinkedEntity::class),
        ];
    }

    public function getMenuConfiguration(): array
    {
        return [
            'qm' => [
                MenuItem::section('QM', 'fas fa-book-open'),
                MenuItem::linkToCrud('Paragraphs', 'fas fa-paragraph', Paragraph::class),
                MenuItem::linkToCrud('Linked Entities', 'fas fa-link', LinkedEntity::class),
            ],
        ];
    }

    public static function getUserConfiguration(): array
    {
        return [];
    }

    public static function getModuleAccess(): array
    {
        return [];
    }

    public static function getTables(): array
    {
        return [
            Paragraph::class,
            LinkedEntity::class,
        ];
    }
}
