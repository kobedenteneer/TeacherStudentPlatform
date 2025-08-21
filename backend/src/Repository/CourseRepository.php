<?php
// src/Repository/CourseRepository.php

namespace App\Repository;

use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    public function findByTeacher(int $teacherId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.teacher = :teacherId')
            ->setParameter('teacherId', $teacherId)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
