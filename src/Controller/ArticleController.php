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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;




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

        $article = new Article();
        $form = $this->createFormBuilder($article)
        ->add('Titre', TextType::class)
        ->add('content', TextareaType::class)
        ->add('Categories', EntityType::class, [
            'class' => Categorie::class,
            'choice_label' => 'nom', // Affiche le nom de la catégorie comme libellé
            'multiple' => true, // Permet de sélectionner plusieurs catégories
            'expanded' => false, // Champ de type liste déroulante ou multi-sélection
        ])
        ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Associe l'article à l'utilisateur connecté
            $article->setUser($this->getUser());
    
            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('article_index');
        }

        return $this->render('/article/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('/article/show.html.twig', [
            'article' => $article,
        ]);
    }
    
    #[Route('/category/{id}', name: 'by_category', methods: ['GET'])]
    public function articlesByCategory(Categorie $category, ArticleRepository $articleRepository, EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Categorie::class)->findAll();
        $articles = $articleRepository->findByCategory($category);
    
        return $this->render('article/index.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
            'currentCategory' => $category,
        ]);
    }
    

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        // Vérifie si l'utilisateur connecté est l'auteur ou un admin
        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser() !== $article->getUser()) {
        throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cet article.');
    }

        $form = $this->createFormBuilder($article)
            ->add('Titre', TextType::class)
            ->add('content', TextareaType::class)
            ->add('save', SubmitType::class, ['label' => 'Update Article'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('article_index');
        }

        return $this->render('/article/edit.html.twig', [
            'form' => $form->createView(),
            'article' => $article, // Passe l'article au template
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
            $entityManager->remove($article);
            $entityManager->flush();
        }

        return $this->redirectToRoute('article_index');
    }
}