<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiControllerTest extends WebTestCase
{
    private $client;
    private $teacherToken;
    private $studentToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Load fixtures
        $this->loadFixtures();

        // Get authentication tokens
        $this->teacherToken = $this->getTokenForUser('teacher1', '1rehcaet');
        $this->studentToken = $this->getTokenForUser('student1', '1tneduts');
    }

    private function loadFixtures(): void
    {
        $kernel = self::bootKernel();
        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
        $application->setAutoExit(false);

        $input = new \Symfony\Component\Console\Input\ArrayInput([
            'command' => 'doctrine:fixtures:load',
            '--no-interaction' => true,
            '--env' => 'test'
        ]);

        $application->run($input);
    }

    private function getTokenForUser(string $username, string $password): string
    {
        $this->client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => $username,
                'password' => $password
            ])
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        return $data['token'];
    }

    public function testLoginSuccess(): void
    {
        $this->client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'teacher1',
                'password' => '1rehcaet'
            ])
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    public function testLoginFailure(): void
    {
        $this->client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'teacher1',
                'password' => 'wrongpassword'
            ])
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testTeacherGetCourses(): void
    {
        $this->client->request(
            'GET',
            '/api/teacher/courses',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->teacherToken]
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));

        // Check structure of first course
        $firstCourse = $data[0];
        $this->assertArrayHasKey('id', $firstCourse);
        $this->assertArrayHasKey('name', $firstCourse);
        $this->assertArrayHasKey('description', $firstCourse);
        $this->assertArrayHasKey('teacher_id', $firstCourse);
        $this->assertArrayHasKey('teacher_name', $firstCourse);
    }

    public function testTeacherGetCourseStudents(): void
    {
        // First get courses to get a course ID
        $this->client->request(
            'GET',
            '/api/teacher/courses',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->teacherToken]
        );

        $coursesData = json_decode($this->client->getResponse()->getContent(), true);
        $courseId = $coursesData[0]['id'];

        // Now get students for that course
        $this->client->request(
            'GET',
            '/api/teacher/courses/' . $courseId . '/students',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->teacherToken]
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);

        if (count($data) > 0) {
            $firstStudent = $data[0];
            $this->assertArrayHasKey('id', $firstStudent);
            $this->assertArrayHasKey('username', $firstStudent);
            $this->assertArrayHasKey('first_name', $firstStudent);
            $this->assertArrayHasKey('last_name', $firstStudent);
            $this->assertArrayHasKey('score', $firstStudent);
            $this->assertArrayHasKey('course_id', $firstStudent);
        }
    }

    public function testTeacherGetCourseStudentsWithScoreBounds(): void
    {
        // Get course ID
        $this->client->request(
            'GET',
            '/api/teacher/courses',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->teacherToken]
        );

        $coursesData = json_decode($this->client->getResponse()->getContent(), true);
        $courseId = $coursesData[0]['id'];

        // Test with score bounds
        $this->client->request(
            'GET',
            '/api/teacher/courses/' . $courseId . '/students?score_lowerbound=70&score_upperbound=80',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->teacherToken]
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);

        // Verify all scores are within bounds
        foreach ($data as $student) {
            $this->assertGreaterThanOrEqual(70, $student['score']);
            $this->assertLessThanOrEqual(80, $student['score']);
        }
    }

    public function testTeacherCreateEvaluation(): void
    {
        // Get course and student IDs
        $this->client->request(
            'GET',
            '/api/teacher/courses',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->teacherToken]
        );

        $coursesData = json_decode($this->client->getResponse()->getContent(), true);
        $courseId = $coursesData[0]['id'];

        // Create evaluation
        $this->client->request(
            'POST',
            '/api/teacher/courses/' . $courseId . '/students/1/evaluations',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->teacherToken,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'result' => 8.5,
                'weight' => 5,
                'message' => 'Good work on the test'
            ])
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals(8.5, $data['result']);
        $this->assertEquals(5, $data['weight']);
        $this->assertEquals('Good work on the test', $data['message']);
        $this->assertArrayHasKey('student_id', $data);
        $this->assertArrayHasKey('course_id', $data);
        $this->assertArrayHasKey('created_at', $data);
    }

    public function testTeacherCreateEvaluationWithInvalidData(): void
    {
        // Get course ID
        $this->client->request(
            'GET',
            '/api/teacher/courses',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->teacherToken]
        );

        $coursesData = json_decode($this->client->getResponse()->getContent(), true);
        $courseId = $coursesData[0]['id'];

        // Try to create evaluation with invalid weight
        $this->client->request(
            'POST',
            '/api/teacher/courses/' . $courseId . '/students/1/evaluations',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->teacherToken,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'result' => 8.5,
                'weight' => 25, // Invalid: should be max 19
                'message' => 'Test evaluation'
            ])
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testStudentGetResults(): void
    {
        $this->client->request(
            'GET',
            '/api/student/results',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->studentToken]
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('student_id', $data);
        $this->assertArrayHasKey('student_name', $data);
        $this->assertArrayHasKey('results_by_course', $data);
        $this->assertArrayHasKey('total_evaluations', $data);
        $this->assertIsArray($data['results_by_course']);
    }

    public function testUnauthorizedAccessToTeacherEndpoint(): void
    {
        $this->client->request(
            'GET',
            '/api/teacher/courses',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->studentToken] // Student token for teacher endpoint
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testUnauthorizedAccessToStudentEndpoint(): void
    {
        $this->client->request(
            'GET',
            '/api/student/results',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->teacherToken] // Teacher token for student endpoint
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testNoTokenAccess(): void
    {
        $this->client->request('GET', '/api/teacher/courses');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
}
