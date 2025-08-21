<?php

namespace App\Tests\Service;

use App\Entity\Course;
use App\Entity\User;
use App\Entity\Evaluation;
use App\Repository\EvaluationRepository;
use App\Service\ScoreCalculationService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ScoreCalculationServiceTest extends TestCase
{
    private ScoreCalculationService $service;
    private EvaluationRepository|MockObject $evaluationRepository;
    private User|MockObject $student;
    private Course|MockObject $course;

    protected function setUp(): void
    {
        $this->evaluationRepository = $this->createMock(EvaluationRepository::class);
        $this->service = new ScoreCalculationService($this->evaluationRepository);
        $this->student = $this->createMock(User::class);
        $this->course = $this->createMock(Course::class);
    }

    public function testCalculateStudentScoreReturnsNullWhenNoEvaluations(): void
    {
        $this->evaluationRepository
            ->expects($this->once())
            ->method('findBy')
            ->with([
                'student' => $this->student,
                'course' => $this->course
            ])
            ->willReturn([]);

        $result = $this->service->calculateStudentScore($this->student, $this->course);

        $this->assertNull($result);
    }

    public function testCalculateStudentScoreReturnsNullWhenTotalMaxScoreIsZero(): void
    {
        $evaluation = $this->createMockEvaluation(true, 8.0, 0);

        $this->evaluationRepository
            ->expects($this->once())
            ->method('findBy')
            ->willReturn([$evaluation]);

        $result = $this->service->calculateStudentScore($this->student, $this->course);

        $this->assertNull($result);
    }

    public function testCalculateStudentScoreWithSingleParticipatedEvaluation(): void
    {
        // Evaluation: result=8, weight=2, maxScore=10
        // Expected: (8 * 2) / (10 * 2) * 100 = 80%
        $evaluation = $this->createMockEvaluation(true, 8.0, 2);

        $this->evaluationRepository
            ->expects($this->once())
            ->method('findBy')
            ->willReturn([$evaluation]);

        $result = $this->service->calculateStudentScore($this->student, $this->course);

        $this->assertEquals(80.0, $result);
    }

    public function testCalculateStudentScoreWithMultipleParticipatedEvaluations(): void
    {
        // Evaluation 1: result=8, weight=2 -> weighted score = 16
        // Evaluation 2: result=6, weight=3 -> weighted score = 18
        // Total weighted score = 34, total max score = 50
        // Expected: (34 / 50) * 100 = 68%
        $evaluation1 = $this->createMockEvaluation(true, 8.0, 2);
        $evaluation2 = $this->createMockEvaluation(true, 6.0, 3);

        $this->evaluationRepository
            ->expects($this->once())
            ->method('findBy')
            ->willReturn([$evaluation1, $evaluation2]);

        $result = $this->service->calculateStudentScore($this->student, $this->course);

        $this->assertEquals(68.0, $result);
    }

    public function testCalculateStudentScoreWithMixedParticipation(): void
    {
        // Evaluation 1: participated, result=8, weight=2 -> weighted score = 16
        // Evaluation 2: not participated, result=0, weight=3 -> weighted score = 0
        // Total weighted score = 16, total max score = 50
        // Expected: (16 / 50) * 100 = 32%
        $evaluation1 = $this->createMockEvaluation(true, 8.0, 2);
        $evaluation2 = $this->createMockEvaluation(false, 0.0, 3);

        $this->evaluationRepository
            ->expects($this->once())
            ->method('findBy')
            ->willReturn([$evaluation1, $evaluation2]);

        $result = $this->service->calculateStudentScore($this->student, $this->course);

        $this->assertEquals(32.0, $result);
    }

    public function testCalculateStudentScoreRoundsResult(): void
    {
        // Create a scenario that results in 66.666...%
        // Result should be rounded to 67
        $evaluation1 = $this->createMockEvaluation(true, 6.0, 1);
        $evaluation2 = $this->createMockEvaluation(true, 7.0, 2);
        // Weighted score: 6*1 + 7*2 = 20, max score: 30
        // (20/30) * 100 = 66.666... -> rounds to 67

        $this->evaluationRepository
            ->expects($this->once())
            ->method('findBy')
            ->willReturn([$evaluation1, $evaluation2]);

        $result = $this->service->calculateStudentScore($this->student, $this->course);

        $this->assertEquals(67.0, $result);
    }

    public function testGetStudentsWithScoresReturnsEmptyArrayWhenNoStudents(): void
    {
        $this->course
            ->expects($this->once())
            ->method('getStudents')
            ->willReturn([]);

        $result = $this->service->getStudentsWithScores($this->course);

        $this->assertEquals([], $result);
    }

    public function testGetStudentsWithScoresExcludesStudentsWithNullScores(): void
    {
        $student1 = $this->createMock(User::class);
        $student2 = $this->createMock(User::class);

        $this->course
            ->expects($this->once())
            ->method('getStudents')
            ->willReturn([$student1, $student2]);

        // Mock evaluationRepository to return empty for both students
        $this->evaluationRepository
            ->expects($this->exactly(2))
            ->method('findBy')
            ->willReturn([]);

        $result = $this->service->getStudentsWithScores($this->course);

        $this->assertEquals([], $result);
    }

    public function testGetStudentsWithScoresReturnsSortedResults(): void
    {
        $student1 = $this->createMock(User::class);
        $student2 = $this->createMock(User::class);
        $student3 = $this->createMock(User::class);

        $this->course
            ->expects($this->once())
            ->method('getStudents')
            ->willReturn([$student1, $student2, $student3]);

        // Configure different scores for each student
        $this->evaluationRepository
            ->expects($this->exactly(3))
            ->method('findBy')
            ->willReturnOnConsecutiveCalls(
                [$this->createMockEvaluation(true, 7.0, 1)], // 70%
                [$this->createMockEvaluation(true, 9.0, 1)], // 90%
                [$this->createMockEvaluation(true, 5.0, 1)]  // 50%
            );

        $result = $this->service->getStudentsWithScores($this->course);

        $this->assertCount(3, $result);
        // Should be sorted by score descending: 90%, 70%, 50%
        $this->assertEquals(90.0, $result[0]['score']);
        $this->assertEquals($student2, $result[0]['student']);
        $this->assertEquals(70.0, $result[1]['score']);
        $this->assertEquals($student1, $result[1]['student']);
        $this->assertEquals(50.0, $result[2]['score']);
        $this->assertEquals($student3, $result[2]['student']);
    }

    public function testGetStudentsWithScoresAppliesLowerBound(): void
    {
        $student1 = $this->createMock(User::class);
        $student2 = $this->createMock(User::class);

        $this->course
            ->expects($this->once())
            ->method('getStudents')
            ->willReturn([$student1, $student2]);

        $this->evaluationRepository
            ->expects($this->exactly(2))
            ->method('findBy')
            ->willReturnOnConsecutiveCalls(
                [$this->createMockEvaluation(true, 6.0, 1)], // 60%
                [$this->createMockEvaluation(true, 8.0, 1)]  // 80%
            );

        $result = $this->service->getStudentsWithScores($this->course, 70.0);

        $this->assertCount(1, $result);
        $this->assertEquals(80.0, $result[0]['score']);
        $this->assertEquals($student2, $result[0]['student']);
    }

    public function testGetStudentsWithScoresAppliesUpperBound(): void
    {
        $student1 = $this->createMock(User::class);
        $student2 = $this->createMock(User::class);

        $this->course
            ->expects($this->once())
            ->method('getStudents')
            ->willReturn([$student1, $student2]);

        $this->evaluationRepository
            ->expects($this->exactly(2))
            ->method('findBy')
            ->willReturnOnConsecutiveCalls(
                [$this->createMockEvaluation(true, 6.0, 1)], // 60%
                [$this->createMockEvaluation(true, 8.0, 1)]  // 80%
            );

        $result = $this->service->getStudentsWithScores($this->course, null, 70.0);

        $this->assertCount(1, $result);
        $this->assertEquals(60.0, $result[0]['score']);
        $this->assertEquals($student1, $result[0]['student']);
    }

    public function testGetStudentsWithScoresAppliesBothBounds(): void
    {
        $student1 = $this->createMock(User::class);
        $student2 = $this->createMock(User::class);
        $student3 = $this->createMock(User::class);

        $this->course
            ->expects($this->once())
            ->method('getStudents')
            ->willReturn([$student1, $student2, $student3]);

        $this->evaluationRepository
            ->expects($this->exactly(3))
            ->method('findBy')
            ->willReturnOnConsecutiveCalls(
                [$this->createMockEvaluation(true, 5.0, 1)], // 50%
                [$this->createMockEvaluation(true, 7.0, 1)], // 70%
                [$this->createMockEvaluation(true, 9.0, 1)]  // 90%
            );

        $result = $this->service->getStudentsWithScores($this->course, 60.0, 80.0);

        $this->assertCount(1, $result);
        $this->assertEquals(70.0, $result[0]['score']);
        $this->assertEquals($student2, $result[0]['student']);
    }

    private function createMockEvaluation(bool $hasParticipated, float $result, int $weight): Evaluation|MockObject
    {
        $evaluation = $this->createMock(Evaluation::class);
        $evaluation->expects($this->any())
            ->method('hasParticipated')
            ->willReturn($hasParticipated);
        $evaluation->expects($this->any())
            ->method('getResult')
            ->willReturn($result);
        $evaluation->expects($this->any())
            ->method('getWeight')
            ->willReturn($weight);

        return $evaluation;
    }
}
