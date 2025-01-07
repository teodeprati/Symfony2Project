<?php

// Déclare le namespace de la classe pour l'organisation du projet.
namespace App\Controller;

// Importation de l'entité Categorie, qui représente une table dans la base de données.
use App\Entity\Categorie;

// Importation du repository de Categorie pour effectuer des requêtes spécifiques sur la table des catégories.
use App\Repository\CategorieRepository;

// Importation de l'interface EntityManager pour gérer les interactions avec la base de données (ajout, suppression, mise à jour).
use Doctrine\ORM\EntityManagerInterface;

// Importation de la classe de base des contrôleurs dans Symfony. Fournit des méthodes pour la gestion des requêtes et des vues.
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// Importation de la classe Request pour manipuler les requêtes HTTP (GET, POST, etc.).
use Symfony\Component\HttpFoundation\Request;

// Importation de la classe Response pour créer des réponses HTTP à retourner au client.
use Symfony\Component\HttpFoundation\Response;

// Importation de types spécifiques pour construire des formulaires, ici un champ texte.
use Symfony\Component\Form\Extension\Core\Type\TextType;

// Importation de types spécifiques pour construire des formulaires, ici un bouton de soumission.
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

// Importation de l'annotation Route pour définir les routes associées aux actions du contrôleur.
use Symfony\Component\Routing\Annotation\Route;

// Définition de la route principale du contrôleur avec un préfixe pour toutes les routes associées.
#[Route('/categorie', name: 'categorie_')]
class CategorieController extends AbstractController
{
    // Définition de la route pour afficher toutes les catégories, accessible par méthode GET.
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(CategorieRepository $categorieRepository): Response
    {
        // Vérifie que l'utilisateur connecté possède le rôle ADMIN avant d'accéder à cette action.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Utilise le repository pour récupérer toutes les catégories de la base de données.
        $categories = $categorieRepository->findAll();

        // Retourne une réponse en rendant la vue Twig "categorie/index.html.twig" avec les catégories passées en variable.
        return $this->render('categorie/index.html.twig', [
            'categories' => $categories, // Passe les données des catégories à la vue.
        ]);
    }

    // Définition de la route pour créer une nouvelle catégorie, accessible par GET et POST.
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifie que l'utilisateur connecté possède le rôle ADMIN avant d'accéder à cette action.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Crée une nouvelle instance de l'entité Categorie.
        $categorie = new Categorie();

        // Crée un formulaire basé sur l'objet $categorie. Ajoute un champ "nom" (texte) et un bouton de soumission.
        $form = $this->createFormBuilder($categorie)
            ->add('nom', TextType::class, [ // Définit un champ de type texte pour le nom de la catégorie.
                'label' => 'Nom de la catégorie', // Texte affiché pour le champ dans le formulaire.
            ])
            ->add('save', SubmitType::class, [ // Ajoute un bouton de soumission au formulaire.
                'label' => 'Créer la catégorie', // Texte affiché sur le bouton.
            ])
            ->getForm(); // Génère le formulaire final.

        // Traite la requête HTTP pour vérifier si le formulaire a été soumis et récupérer ses données.
        $form->handleRequest($request);

        // Vérifie si le formulaire a été soumis ET si les données sont valides.
        if ($form->isSubmitted() && $form->isValid()) {
            // Prépare l'objet $categorie pour être sauvegardé en base de données.
            $entityManager->persist($categorie);

            // Exécute les changements en base de données (ici, l'insertion de la nouvelle catégorie).
            $entityManager->flush();

            // Redirige l'utilisateur vers la liste des catégories après la création.
            return $this->redirectToRoute('categorie_index');
        }

        // Si le formulaire n'est pas soumis ou pas valide, retourne une vue Twig avec le formulaire.
        return $this->render('categorie/new.html.twig', [
            'form' => $form->createView(), // Passe la vue du formulaire à la template.
        ]);
    }

    // Définition de la route pour afficher une catégorie spécifique en fonction de son ID, accessible par GET.
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Categorie $categorie): Response
    {
        // Vérifie que l'utilisateur connecté possède le rôle ADMIN avant d'accéder à cette action.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Retourne une réponse en rendant la vue Twig "categorie/show.html.twig" avec les détails de la catégorie.
        return $this->render('categorie/show.html.twig', [
            'categorie' => $categorie, // Passe les données de la catégorie à la vue.
        ]);
    }

    // Définition de la route pour modifier une catégorie existante, accessible par GET et POST.
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Categorie $categorie, EntityManagerInterface $entityManager): Response
    {
        // Vérifie que l'utilisateur connecté possède le rôle ADMIN avant d'accéder à cette action.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Crée un formulaire pour modifier les données de l'entité $categorie.
        $form = $this->createFormBuilder($categorie)
            ->add('nom', TextType::class, [ // Champ texte pour modifier le nom de la catégorie.
                'label' => 'Nom de la catégorie', // Texte affiché pour le champ dans le formulaire.
            ])
            ->add('save', SubmitType::class, [ // Bouton pour soumettre les modifications.
                'label' => 'Mettre à jour la catégorie', // Texte affiché sur le bouton.
            ])
            ->getForm(); // Génère le formulaire final.

        // Traite la requête HTTP pour vérifier si le formulaire a été soumis et récupérer ses données.
        $form->handleRequest($request);

        // Vérifie si le formulaire a été soumis ET si les données sont valides.
        if ($form->isSubmitted() && $form->isValid()) {
            // Exécute les modifications directement en base de données.
            $entityManager->flush();

            // Redirige l'utilisateur vers la liste des catégories après la mise à jour.
            return $this->redirectToRoute('categorie_index');
        }

        // Si le formulaire n'est pas soumis ou pas valide, retourne une vue Twig avec le formulaire.
        return $this->render('categorie/edit.html.twig', [
            'form' => $form->createView(), // Passe la vue du formulaire à la template.
            'categorie' => $categorie, // Passe également les données actuelles de la catégorie.
        ]);
    }

    // Définition de la route pour supprimer une catégorie, accessible par POST.
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Categorie $categorie, EntityManagerInterface $entityManager): Response
    {
        // Vérifie que l'utilisateur connecté possède le rôle ADMIN avant d'accéder à cette action.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Vérifie la validité du token CSRF pour sécuriser l'action de suppression.
        if ($this->isCsrfTokenValid('delete' . $categorie->getId(), $request->request->get('_token'))) {
            // Supprime l'entité $categorie de la base de données.
            $entityManager->remove($categorie);

            // Exécute la suppression en base de données.
            $entityManager->flush();
        }

        // Redirige l'utilisateur vers la liste des catégories après la suppression.
        return $this->redirectToRoute('categorie_index');
    }
}
