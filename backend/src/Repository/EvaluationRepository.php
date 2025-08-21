<?php
// src/Repository/EvaluationRepository.php

namespace App\Repository;

use App\Entity\Evaluation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evaluation::class);
    }

    public function findByStudentAndCourse(int $studentId, int $courseId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.student = :studentId')
            ->andWhere('e.course = :courseId')
            ->setParameter('studentId', $studentId)
            ->setParameter('courseId', $courseId)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
