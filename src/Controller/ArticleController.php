<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Categorie;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface; // On importe EntityManagerInterface qui est utilisé pour interagir avec la base de données
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\ArticleType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

#[Route('/article', name: 'article_')]
class ArticleController extends AbstractController
{
    // #[Route('/admin', name: 'index', methods: ['GET'])]
    // public function index(ArticleRepository $articleRepository, EntityManagerInterface $entityManager): Response
    // {
    //     $this->denyAccessUnlessGranted('ROLE_ADMIN');

    //     // Récupérer toutes les catégories
    //     $categories = $entityManager->getRepository(Categorie::class)->findAll(); // ici je pourrais faire comme pour les articles rajouter le repo de categorie
    
    //     return $this->render('article/index.html.twig', [
    //         'articles' => $articleRepository->findAll(),
    //         'categories' => $categories, // Passer les catégories au template
    //     ]);
    // }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function userIndex(ArticleRepository $articleRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupérer toutes les catégories
        $categories = $entityManager->getRepository(Categorie::class)->findAll();

        return $this->render('article/index.html.twig', [
            'articles' => $articleRepository->findAll(),
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifie que l'utilisateur est connecté (admin ou utilisateur)
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
    
        // 1) On crée l'entité
        $article = new Article();
    
        // 2) On génère le formulaire depuis ton ArticleType
        $form = $this->createForm(ArticleType::class, $article);
    
        // 3) On gère la soumission
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Associe l'article à l'utilisateur connecté
            $article->setUser($this->getUser());
    
            // Récupère le fichier image
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                // Génère un nom de fichier unique
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
    
                // Déplace le fichier dans le répertoire configuré (services.yaml)
                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
    
                // Stocke le nom dans l'entité
                $article->setImage($newFilename);
            }
    
            // Sauvegarde en base
            $entityManager->persist($article);
            $entityManager->flush();
    
            return $this->redirectToRoute('article_index');
        }
    
        // 4) Affiche le formulaire dans la vue
        return $this->render('/article/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/category/{id}', name: 'by_category', methods: ['GET'])]
    public function articlesByCategory(Categorie $category, ArticleRepository $articleRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupérer toutes les catégories pour le menu
        $categories = $entityManager->getRepository(Categorie::class)->findAll();
        $articles = $articleRepository->findByCategory($category);
    
        return $this->render('article/index.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
            'currentCategory' => $category,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        // Affiche un article spécifique
        return $this->render('/article/show.html.twig', [
            'article' => $article,
        ]);
    }
    
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        // Vérifie si l'utilisateur connecté est l'auteur ou un admin
        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser() !== $article->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cet article.');
        }
    
        // Stocke l'ancienne image pour suppression si une nouvelle est uploadée
        $ancienneImage = $article->getImage();
    
        // Création du formulaire avec le FormType
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer le fichier uploadé
            $imageFile = $form->get('imageFile')->getData();
    
            if ($imageFile) {
                // Supprime l'ancienne image si elle existe
                if ($ancienneImage) {
                    $filesystem = new Filesystem();
                    $imagePath = $this->getParameter('images_directory').'/'.$ancienneImage;
                    if ($filesystem->exists($imagePath)) {
                        $filesystem->remove($imagePath);
                    }
                }
    
                // Génère un nom de fichier unique
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
    
                // Déplace l'image dans le répertoire d'upload
                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
    
                // Met à jour l'entité avec le nouveau nom de fichier
                $article->setImage($newFilename);
            }
    
            // Met à jour la base de données
            $entityManager->flush();
    
            return $this->redirectToRoute('article_index');
        }
    
        return $this->render('/article/edit.html.twig', [
            'form' => $form->createView(),
            'article' => $article,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        // Vérifie si l'utilisateur connecté est l'auteur ou un admin
        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser() !== $article->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cet article.');
        }

        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) {
            // Suppression du fichier image s'il existe
            if ($article->getImage()) {
                $filesystem = new Filesystem();
                $imagePath = $this->getParameter('images_directory').'/'.$article->getImage();

                if ($filesystem->exists($imagePath)) {
                    $filesystem->remove($imagePath);
                }
            }

            // Supprime l'article de la base de données
            $entityManager->remove($article);
            $entityManager->flush();
        }

        return $this->redirectToRoute('article_index');
    }
}