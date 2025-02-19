<?php
// Déclaration du namespace. Cela permet de définir à quel "espace" ou "dossier" cette classe appartient
// Ici, la classe UserController appartient à l'espace "App\Controller"
namespace App\Controller;

use App\Entity\User; // On importe la classe User. Cela permet de manipuler les entités User
use App\Repository\UserRepository; // On importe le repository User pour accéder aux données de la table 'user' dans la base de données
use Doctrine\ORM\EntityManagerInterface; // On importe EntityManagerInterface qui est utilisé pour interagir avec la base de données
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importation de la classe de base de Symfony qui fournit des méthodes utiles pour les contrôleurs
use Symfony\Component\HttpFoundation\Request; // Importation de la classe Request qui gère les données envoyées dans la requête HTTP
use Symfony\Component\HttpFoundation\Response; // Importation de la classe Response qui est utilisée pour envoyer une réponse HTTP
use Symfony\Component\Routing\Annotation\Route; // On importe l'annotation Route, qui permet de définir des routes pour ce contrôleur
use Symfony\Component\Security\Http\Attribute\IsGranted; // Importation de l'annotation IsGranted
use Symfony\Component\Security\Core\Exception\AccessDeniedException; // Importation de l'exception AccessDeniedException
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; // Importation de l'interface UserPasswordHasherInterface

class UserController extends AbstractController // Déclaration de la classe UserController qui étend la classe AbstractController (la classe de base des contrôleurs Symfony)
{
    #[Route('/admin/list', name: 'user_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(UserRepository $userRepository): Response // La méthode index() récupère tous les utilisateurs de la base de données via UserRepository et les affiche
    {
        $users = $userRepository->findAll(); // Appelle la méthode findAll() du UserRepository pour récupérer tous les utilisateurs dans la base de données

        return $this->render('user/index.html.twig', ['users' => $users]); // Rendu de la vue 'user/index.html.twig', avec la liste des utilisateurs passée à la vue
    }

    #[Route('/admin/register', name: 'user_new', methods: ['GET', 'POST'])] // La route '/new' pour afficher le formulaire de création et traiter l'envoi du formulaire
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response // La méthode new() gère l'affichage et la création de nouveaux utilisateurs
    {
        if ($request->isMethod('POST')) { // Si la méthode de la requête est POST (c'est-à-dire que le formulaire a été soumis)
            $user = new User(); // On crée une nouvelle instance de l'entité User

            // On récupère les données soumises dans le formulaire et on les attribue à l'entité $user
            $user->setUsername($request->request->get('username')); // Attribue le nom de l'utilisateur depuis la requête
            $user->setEmail($request->request->get('email')); // Attribue l'email depuis la requête

            // Hachage du mot de passe avant de le sauvegarder dans la base de données
            $hashedPassword = $passwordHasher->hashPassword($user, $request->request->get('password')); // Utilise bcrypt pour sécuriser le mot de passe
            $user->setPassword($hashedPassword); // On attribue le mot de passe haché à l'utilisateur

            $role = $request->request->get('role', 'ROLE_USER'); // On récupère le rôle du formulaire. Par défaut, il sera 'ROLE_USER'
            $user->setRoles([$role]); // Attribue le rôle à l'utilisateur

            $em->persist($user); // Prépare l'entité $user à être sauvegardée dans la base de données
            $em->flush(); // Sauvegarde réellement les données dans la base de données

            return $this->redirectToRoute('user_index'); // Redirige l'utilisateur vers la page de la liste des utilisateurs après l'ajout
        }

        return $this->render('user/new.html.twig'); // Si la méthode est GET (formulaire de création), on affiche le formulaire
    }
    
    #[Route('/admin/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])] // La route '/{id}/edit' permet de modifier un utilisateur existant
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, User $user, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response // La méthode edit() permet de modifier les informations d'un utilisateur existant
    {
        if ($request->isMethod('POST')) { // Si la requête est de type POST (formulaire soumis)
            // Récupère et met à jour les informations de l'utilisateur
            $user->setUsername($request->request->get('username')); // Modifie le nom de l'utilisateur
            $user->setEmail($request->request->get('email')); // Modifie l'email de l'utilisateur

            $role = $request->request->get('role', 'ROLE_USER'); // Récupère et met à jour le rôle de l'utilisateur
            $user->setRoles([$role]); // Modifie le rôle de l'utilisateur

            $em->flush(); // Sauvegarde les modifications apportées à l'utilisateur dans la base de données

            return $this->redirectToRoute('user_index'); // Redirige vers la page de la liste des utilisateurs après modification
        }

        return $this->render('user/edit.html.twig', ['user' => $user]); // Affiche le formulaire avec les données de l'utilisateur à modifier
    }

    #[Route('/admin/{id}/delete', name: 'user_delete', methods: ['POST'])] // La route '/{id}/delete' permet de supprimer un utilisateur
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, User $user, EntityManagerInterface $em): Response // La méthode delete() permet de supprimer un utilisateur existant
    {
        $em->remove($user); // Supprime l'utilisateur de la base de données
        $em->flush(); // Sauvegarde la suppression dans la base de données

        return $this->redirectToRoute('user_index'); // Redirige vers la liste des utilisateurs après suppression
    }

    #[Route('/profile', name: 'user_profile')]
    public function profile(): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/profile/edit', name: 'user_profile_edit', methods: ['GET', 'POST'])]
    public function editProfile(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $username = $request->request->get('username');
            $email = $request->request->get('email');

            // Vérifier si le nom d'utilisateur est déjà pris
            $existingUser = $em->getRepository(User::class)->findOneBy(['username' => $username]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                $this->addFlash('error', 'Ce nom d\'utilisateur est déjà utilisé.');
                return $this->redirectToRoute('user_profile_edit');
            }

            // Vérifier si l'email est déjà pris
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->redirectToRoute('user_profile_edit');
            }

            $user->setUsername($username);
            $user->setEmail($email);

            $em->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
            return $this->redirectToRoute('user_profile');
        }

        return $this->render('user/profile_edit.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/profile/delete', name: 'user_profile_delete', methods: ['POST'])]
    public function deleteProfile(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-profile', $submittedToken)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        // Déconnexion de l'utilisateur
        $this->container->get('security.token_storage')->setToken(null);
        $request->getSession()->invalidate();

        // Suppression du compte
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
        return $this->redirectToRoute('app_home');
    }
}
