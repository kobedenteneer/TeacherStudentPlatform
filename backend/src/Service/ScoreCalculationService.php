<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\User;
use App\Repository\EvaluationRepository;

class ScoreCalculationService
{
    public function __construct(
        private EvaluationRepository $evaluationRepository
    ) {}

    public function calculateStudentScore(User $student, Course $course): ?float
    {
        $evaluations = $this->evaluationRepository->findBy([
            'student' => $student,
            'course' => $course
        ]);

        if (empty($evaluations)) {
            return null;
        }

        $totalWeightedScore = 0;
        $totalMaxScore = 0;

        foreach ($evaluations as $evaluation) {
            if ($evaluation->hasParticipated()) {
                $totalWeightedScore += $evaluation->getResult() * $evaluation->getWeight();
            }
            $totalMaxScore += 10 * $evaluation->getWeight();
        }

        if ($totalMaxScore === 0) {
            return null;
        }

        $score = ($totalWeightedScore / $totalMaxScore) * 100;
        return round($score);
    }

    public function getStudentsWithScores(Course $course, ?float $lowerBound = null, ?float $upperBound = null): array
    {
        $students = $course->getStudents();
        $studentsWithScores = [];

        foreach ($students as $student) {
            $score = $this->calculateStudentScore($student, $course);

            if ($score !== null) {
                $include = true;

                if ($lowerBound !== null && $score < $lowerBound) {
                    $include = false;
                }

                if ($upperBound !== null && $score > $upperBound) {
                    $include = false;
                }

                if ($include) {
                    $studentsWithScores[] = [
                        'student' => $student,
                        'score' => $score
                    ];
                }
            }
        }

        // Sort by score descending
        usort($studentsWithScores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $studentsWithScores;
    }
}
