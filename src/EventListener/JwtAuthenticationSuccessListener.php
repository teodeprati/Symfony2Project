<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class JwtAuthenticationSuccessListener
{
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData(); // Récupère les données de la réponse par défaut
        $user = $event->getUser(); // Récupère l'utilisateur connecté

        // Vérifie que l'utilisateur est bien une instance de UserInterface
        if (!$user instanceof UserInterface) {
            return;
        }

        // Ajoute des informations supplémentaires à la réponse
        $data['user'] = [
            'id' => $user->getId(),
            'username' => $user->getName(),
            'roles' => $user->getRoles(), // Inclut les rôles de l'utilisateur
        ];

        $event->setData($data); // Met à jour les données de la réponse
    }
}