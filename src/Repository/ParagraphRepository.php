<?php

namespace Prolyfix\QmBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Prolyfix\HolidayAndTime\Repository\SearchableTrait;
use Prolyfix\QmBundle\Entity\Paragraph;

/**
 * @extends ServiceEntityRepository<Paragraph>
 */
class ParagraphRepository extends ServiceEntityRepository
{
    use SearchableTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paragraph::class);
    }
}
