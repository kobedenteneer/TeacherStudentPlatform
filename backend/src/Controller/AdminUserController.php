<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'admin_users_')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    #[Route('/{id}/password', name: 'reset_password', methods: ['PATCH'])]
    public function resetPassword(
        int $id,
        Request $request,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse([
                'message' => 'Gebruiker niet gevonden.'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['newPassword']) || strlen($data['newPassword']) < 8) {
            return new JsonResponse([
                'message' => 'Nieuw wachtwoord is ongeldig. Minimaal 8 tekens vereist.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $data['newPassword']);
        $user->setPassword($hashedPassword);

        return new JsonResponse([
            'message' => 'Wachtwoord succesvol gereset.'
        ], Response::HTTP_OK);
    }
}
