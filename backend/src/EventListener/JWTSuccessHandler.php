<?php
namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class JWTSuccessHandler
{
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        error_log('JWTSuccessHandler wordt aangeroepen'); // Controleer of dit in de logs verschijnt

        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof UserInterface) {
            error_log('Gebruiker is niet geldig');
            return;
        }

        $data['user'] = [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'roles' => $user->getRoles(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
        ];

        $event->setData($data);
        error_log('JWTSuccessHandler heeft de data aangepast');
    }
}
