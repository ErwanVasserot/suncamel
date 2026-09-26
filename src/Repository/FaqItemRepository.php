<?php

namespace App\Repository;

use App\Entity\FaqItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FaqItem>
 */
class FaqItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FaqItem::class);
    }

    /**
     * @return list<FaqItem>
     */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('f.category', 'ASC')
            ->addOrderBy('f.position', 'ASC')
            ->addOrderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
