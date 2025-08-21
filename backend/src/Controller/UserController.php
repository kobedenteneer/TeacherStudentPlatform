<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class UserController extends AbstractController
{
    #[Route('/api/login_check', name: 'api_login_check', methods: ['POST'])]
    public function login(
        Request $request,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof UserInterface) {
            error_log('Gebruiker niet gevonden of niet geauthenticeerd');
            return $this->json(['error' => 'Invalid credentials'], 401);
        }

        error_log('Gebruiker gevonden: ' . $user->getUsername());

        $token = $jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
                'first_name' => $user->getFirstName(), // Voeg voornaam toe
                'last_name' => $user->getLastName(),  // Voeg achternaam toe
            ],
        ]);
    }
}
