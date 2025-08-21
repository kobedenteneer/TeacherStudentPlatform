<?php

namespace App\Controller;

use App\Dto\EvaluationDto;
use App\Entity\Course;
use App\Entity\Evaluation;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\UserRepository;
use App\Service\ScoreCalculationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_TEACHER')]
class TeacherController extends AbstractController
{
    public function __construct(
        private CourseRepository $courseRepository,
        private UserRepository $userRepository,
        private ScoreCalculationService $scoreCalculationService,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('/teacher/courses', name: 'teacher_courses', methods: ['GET'])]
    #[Route('/teacher/{id}/courses', name: 'teacher_courses_with_id', methods: ['GET'])]
    public function getCourses(?int $id = null): JsonResponse
    {
        $teacher = $this->getTeacherFromIdOrToken($id);

        if (!$teacher) {
            return new JsonResponse(['error' => 'Teacher not found'], Response::HTTP_NOT_FOUND);
        }

        $courses = $this->courseRepository->findBy(['teacher' => $teacher]);

        $coursesData = array_map(function(Course $course) {
            return [
                'id' => $course->getId(),
                'name' => $course->getName(),
                'description' => $course->getDescription(),
                'teacher_id' => $course->getTeacher()->getId(),
                'teacher_name' => $course->getTeacher()->getFullName()
            ];
        }, $courses);

        return new JsonResponse($coursesData);
    }

    #[Route('/teacher/courses/{courseId}/students', name: 'teacher_course_students', methods: ['GET'])]
    #[Route('/teacher/{id}/courses/{courseId}/students', name: 'teacher_course_students_with_id', methods: ['GET'])]
    public function getCourseStudents(int $courseId, Request $request, ?int $id = null): JsonResponse
    {
        $teacher = $this->getTeacherFromIdOrToken($id);

        if (!$teacher) {
            return new JsonResponse(['error' => 'Teacher not found'], Response::HTTP_NOT_FOUND);
        }

        $course = $this->courseRepository->find($courseId);

        if (!$course || $course->getTeacher() !== $teacher) {
            return new JsonResponse(['error' => 'Course not found or access denied'], Response::HTTP_NOT_FOUND);
        }

        $lowerBound = $request->query->get('score_lowerbound');
        $upperBound = $request->query->get('score_upperbound');

        $lowerBound = $lowerBound !== null ? (float) $lowerBound : null;
        $upperBound = $upperBound !== null ? (float) $upperBound : null;

        $studentsWithScores = $this->scoreCalculationService->getStudentsWithScores($course, $lowerBound, $upperBound);

        $studentsData = array_map(function($item) use ($course) {
            $student = $item['student'];
            return [
                'id' => $student->getId(),
                'username' => $student->getUsername(),
                'first_name' => $student->getFirstName(),
                'last_name' => $student->getLastName(),
                'full_name' => $student->getFullName(),
                'score' => $item['score'],
                'course_id' => $course->getId(),
                'course_name' => $course->getName()
            ];
        }, $studentsWithScores);

        return new JsonResponse($studentsData);
    }

    #[Route('/teacher/courses/{courseId}/students/{studentId}/evaluations', name: 'create_evaluation', methods: ['POST'])]
    #[Route('/teacher/{id}/courses/{courseId}/students/{studentId}/evaluations', name: 'create_evaluation_with_id', methods: ['POST'])]
    public function createEvaluation(int $courseId, int $studentId, Request $request, ?int $id = null): JsonResponse
    {
        $teacher = $this->getTeacherFromIdOrToken($id);

        if (!$teacher) {
            return new JsonResponse(['error' => 'Teacher not found'], Response::HTTP_NOT_FOUND);
        }

        $course = $this->courseRepository->find($courseId);

        if (!$course || $course->getTeacher() !== $teacher) {
            return new JsonResponse(['error' => 'Course not found or access denied'], Response::HTTP_NOT_FOUND);
        }

        $student = $this->userRepository->find($studentId);

        if (!$student || !$student->isStudent()) {
            return new JsonResponse(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
            }

            $evaluationDto = new EvaluationDto(
                $data['result'] ?? null,
                $data['weight'] ?? null,
                $data['message'] ?? null
            );

            $violations = $this->validator->validate($evaluationDto);

            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }
                return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $evaluation = new Evaluation();
            $evaluation->setResult($evaluationDto->result);
            $evaluation->setWeight($evaluationDto->weight);
            $evaluation->setMessage($evaluationDto->message);
            $evaluation->setStudent($student);
            $evaluation->setCourse($course);

            $this->entityManager->persist($evaluation);
            $this->entityManager->flush();

            return new JsonResponse([
                'id' => $evaluation->getId(),
                'result' => $evaluation->getResult(),
                'weight' => $evaluation->getWeight(),
                'message' => $evaluation->getMessage(),
                'student_id' => $evaluation->getStudent()->getId(),
                'student_name' => $evaluation->getStudent()->getFullName(),
                'course_id' => $evaluation->getCourse()->getId(),
                'course_name' => $evaluation->getCourse()->getName(),
                'created_at' => $evaluation->getCreatedAt()->format('Y-m-d H:i:s')
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred while creating the evaluation'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getTeacherFromIdOrToken(?int $id): ?User
    {
        $currentUser = $this->getUser();

        if ($id === null) {
            return $currentUser instanceof User && $currentUser->isTeacher() ? $currentUser : null;
        }

        $teacher = $this->userRepository->find($id);

        if (!$teacher || !$teacher->isTeacher()) {
            return null;
        }

        // If ID is provided, check if it matches the current user
        if ($currentUser instanceof User && $currentUser->getId() !== $id) {
            return null;
        }

        return $teacher;
    }
}
