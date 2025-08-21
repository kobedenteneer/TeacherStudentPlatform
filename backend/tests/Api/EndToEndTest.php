<?php

namespace App\Tests\Api;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class EndToEndTest extends TestCase
{
    private Client $client;
    private string $baseUri = 'http://localhost:8000';
    private string $jwtToken = '';

    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'http_errors' => false,
        ]);
    }

    public function testLoginAndCreateEvaluation(): void
    {
        // Login en JWT token ophalen
        $response = $this->client->post('/api/login_check', [
            'json' => [
                'username' => 'teacher1',
                'password' => '1rehcaet', // Pas aan naar jouw wachtwoord
            ],
        ]);

        $this->assertEquals(200, $response->getStatusCode(), 'Login moet HTTP 200 teruggeven');

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('token', $body);
        $this->assertIsString($body['token']);

        $this->jwtToken = $body['token'];

        // Evaluatie aanmaken
        $courseId = 1;   // Pas aan naar jouw testdata
        $studentId = 4;  // Pas aan naar jouw testdata

        $response = $this->client->post("/teacher/courses/$courseId/students/$studentId/evaluations", [
            'headers' => [
                'Authorization' => "Bearer {$this->jwtToken}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'result' => 8.5,
                'weight' => 3,
                'message' => 'Goed gedaan!',
            ],
        ]);

        $this->assertEquals(201, $response->getStatusCode(), 'Evaluatie aanmaken moet HTTP 201 teruggeven');

        $body = json_decode($response->getBody()->getContents(), true);

        // Controleer de response velden
        $this->assertArrayHasKey('id', $body);
        $this->assertEquals(8.5, $body['result']);
        $this->assertEquals(3, $body['weight']);
        $this->assertEquals('Goed gedaan!', $body['message']);
    }
}
