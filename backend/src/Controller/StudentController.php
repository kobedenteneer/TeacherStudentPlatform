<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\EvaluationRepository;
use App\Repository\UserRepository;
use App\Service\ScoreCalculationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[IsGranted('ROLE_STUDENT')]
class StudentController extends AbstractController
{
    public function __construct(
        private EvaluationRepository $evaluationRepository,
        private UserRepository $userRepository,
        private ScoreCalculationService $scoreCalculationService
    ) {}

    #[Route('/student/results', name: 'student_results', methods: ['GET'])]
    #[Route('/student/{id}/results', name: 'student_results_with_id', methods: ['GET'])]
    public function getResults(?int $id = null): JsonResponse
    {
        $student = $this->getStudentFromIdOrToken($id);

        if (!$student) {
            return new JsonResponse(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }

        $evaluations = $this->evaluationRepository->findBy(['student' => $student]);

        $results = [];
        $courseScores = [];

        foreach ($evaluations as $evaluation) {
            $course = $evaluation->getCourse();
            $courseId = $course->getId();

            if (!isset($courseScores[$courseId])) {
                $courseScores[$courseId] = $this->scoreCalculationService->calculateStudentScore($student, $course);
            }

            $results[] = [
                'id' => $evaluation->getId(),
                'result' => $evaluation->getResult(),
                'weight' => $evaluation->getWeight(),
                'message' => $evaluation->getMessage(),
                'participated' => $evaluation->hasParticipated(),
                'created_at' => $evaluation->getCreatedAt()->format('Y-m-d H:i:s'),
                'course' => [
                    'id' => $course->getId(),
                    'name' => $course->getName(),
                    'description' => $course->getDescription(),
                    'teacher_name' => $course->getTeacher()->getFullName(),
                    'overall_score' => $courseScores[$courseId]
                ]
            ];
        }

        // Group by course for better organization
        $groupedResults = [];
        foreach ($results as $result) {
            $courseId = $result['course']['id'];
            if (!isset($groupedResults[$courseId])) {
                $groupedResults[$courseId] = [
                    'course' => $result['course'],
                    'evaluations' => []
                ];
            }
            unset($result['course']);
            $groupedResults[$courseId]['evaluations'][] = $result;
        }

        return new JsonResponse([
            'student_id' => $student->getId(),
            'student_name' => $student->getFullName(),
            'results_by_course' => array_values($groupedResults),
            'total_evaluations' => count($results)
        ]);
    }

    private function getStudentFromIdOrToken(?int $id): ?User
    {
        $currentUser = $this->getUser();

        if ($id === null) {
            return $currentUser instanceof User && $currentUser->isStudent() ? $currentUser : null;
        }

        $student = $this->userRepository->find($id);

        if (!$student || !$student->isStudent()) {
            return null;
        }

        // If ID is provided, check if it matches the current user
        if ($currentUser instanceof User && $currentUser->getId() !== $id) {
            return null;
        }

        return $student;
    }
}
