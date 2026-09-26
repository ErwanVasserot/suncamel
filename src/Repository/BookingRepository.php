<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Booking> */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function reservedQuantity(Product $product, \DateTimeImmutable $pickup, \DateTimeImmutable $return): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COALESCE(SUM(i.quantity), 0)')
            ->join('b.items', 'i')
            ->andWhere('i.product = :product')
            ->andWhere('i.pickupAt < :return')
            ->andWhere('i.returnAt > :pickup')
            ->andWhere('(b.status = :paid OR (b.status = :pending AND b.expiresAt > :now))')
            ->setParameter('product', $product)
            ->setParameter('pickup', $pickup)
            ->setParameter('return', $return)
            ->setParameter('paid', Booking::STATUS_PAID)
            ->setParameter('pending', Booking::STATUS_PENDING)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
