<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(UserInterface $user): JsonResponse
    {
        // Retourne la réponse avec le token et les informations de l'utilisateur
        return new JsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles() // Inclut les rôles de l'utilisateur
            ]
        ]);
    }

    private function getTokenFromRequest(): string
    {
        // Récupère le token généré par LexikJWTAuthenticationBundle
        // (à adapter selon votre configuration)
        return $this->container->get('lexik_jwt_authentication.jwt_manager')->create($this->getUser());
    }
}