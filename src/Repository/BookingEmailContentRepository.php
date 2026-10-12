<?php

namespace App\Repository;

use App\Entity\BookingEmailContent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<BookingEmailContent> */
class BookingEmailContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookingEmailContent::class);
    }

    public function getContent(): BookingEmailContent
    {
        return $this->find(BookingEmailContent::SINGLETON_ID) ?? new BookingEmailContent();
    }
}
