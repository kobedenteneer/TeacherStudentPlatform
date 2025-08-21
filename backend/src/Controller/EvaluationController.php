<?php
// src/Controller/EvaluationController.php

namespace App\Controller;

use App\Dto\EvaluationDto;
use App\Form\EvaluationType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EvaluationController extends AbstractController
{
    #[Route('/teacher/courses/{courseId}/students/{studentId}/evaluations', name: 'create_evaluation', methods: ['POST'])]
    public function createEvaluation(Request $request, int $courseId, int $studentId): JsonResponse
    {
        $dto = new EvaluationDto();
        $form = $this->createForm(EvaluationType::class, $dto);
        $data = json_decode($request->getContent(), true);

        $form->submit($data);

        if (!$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }

            return $this->json(['errors' => $errors], 400);
        }

        // Normaal voeg je hier je service toe om de evaluatie op te slaan
        // $this->evaluationService->createEvaluation($courseId, $studentId, $dto);

        return $this->json([
            'message' => 'Evaluation added successfully',
            'data' => $dto,
        ], 201);
    }
}
