<?php

namespace Prolyfix\QmBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Prolyfix\QmBundle\Entity\LinkedEntity;

/**
 * @extends ServiceEntityRepository<LinkedEntity>
 */
class LinkedEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LinkedEntity::class);
    }

    public function findOneForEntity(string $entityClass, int $entityId): ?LinkedEntity
    {
        return $this->createQueryBuilder('linked')
            ->andWhere('linked.entity = :entityClass')
            ->andWhere('linked.entityId = :entityId')
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityId', $entityId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function hasForEntity(string $entityClass, int $entityId): bool
    {
        return $this->createQueryBuilder('linked')
            ->select('COUNT(linked.id)')
            ->andWhere('linked.entity = :entityClass')
            ->andWhere('linked.entityId = :entityId')
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityId', $entityId)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
