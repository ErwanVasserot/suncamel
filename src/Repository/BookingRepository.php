<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Product;
use App\Entity\User;
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

    /** @return list<Booking> */
    public function findActiveOverlapping(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('b')
            ->select('DISTINCT b, i, p')
            ->join('b.items', 'i')
            ->join('i.product', 'p')
            ->andWhere('i.pickupAt < :end')
            ->andWhere('i.returnAt > :start')
            ->andWhere('(b.status = :paid OR (b.status = :pending AND b.expiresAt > :now))')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('paid', Booking::STATUS_PAID)
            ->setParameter('pending', Booking::STATUS_PENDING)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('i.pickupAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function paidAmountBetween(\DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COALESCE(SUM(b.totalAmount), 0)')
            ->andWhere('b.status = :paid')
            ->andWhere('b.paidAt >= :start')
            ->andWhere('b.paidAt < :end')
            ->setParameter('paid', Booking::STATUS_PAID)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<Booking> */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->addSelect('i', 'p')
            ->leftJoin('b.items', 'i')
            ->leftJoin('i.product', 'p')
            ->andWhere('b.user = :user')
            ->setParameter('user', $user)
            ->orderBy('b.createdAt', 'DESC')
            ->addOrderBy('i.pickupAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
