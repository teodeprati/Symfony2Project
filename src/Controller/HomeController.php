<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Categorie;
use App\Repository\ArticleRepository;
use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ArticleRepository $articleRepository, CategorieRepository $categorieRepository): Response
    {
        // Récupérer les 4 derniers articles
        $latestArticles = $articleRepository->findBy(
            [], // critères
            ['createdAt' => 'DESC'], // tri
            4 // limite
        );

        // Récupérer toutes les catégories
        $categories = $categorieRepository->findAll();

        return $this->render('home/index.html.twig', [
            'latest_articles' => $latestArticles,
            'categories' => $categories,
        ]);
    }
}
