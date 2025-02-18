<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        // Rediriger si l'utilisateur est déjà connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Encoder le mot de passe
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                // Définir le rôle par défaut
                $user->setRoles(['ROLE_USER']);

                // Persister l'utilisateur
                $entityManager->persist($user);
                $entityManager->flush();

                // Ajouter un message flash
                $this->addFlash('success', 'Votre compte a été créé avec succès !');

                // Rediriger vers la page de connexion
                return $this->redirectToRoute('app_login');
            } else {
                // Ajouter un message flash d'erreur
                $this->addFlash('error', 'Il y a des erreurs dans le formulaire. Veuillez les corriger.');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
