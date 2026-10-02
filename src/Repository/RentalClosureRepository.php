<?php
namespace App\Repository;

use App\Entity\RentalClosure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<RentalClosure> */
final class RentalClosureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, RentalClosure::class); }

    /** @return list<RentalClosure> */
    public function findFrom(\DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('c')->andWhere('c.endDate >= :date')->setParameter('date', $date->setTime(0, 0))->orderBy('c.startDate', 'ASC')->getQuery()->getResult();
    }

    public function findOverlapping(\DateTimeImmutable $start, \DateTimeImmutable $end): ?RentalClosure
    {
        return $this->createQueryBuilder('c')->andWhere('c.startDate <= :end')->andWhere('c.endDate >= :start')
            ->setParameter('start', $start->setTime(0, 0))->setParameter('end', $end->setTime(0, 0))
            ->orderBy('c.startDate', 'ASC')->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }
}
