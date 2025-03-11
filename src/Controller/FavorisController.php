<?php

namespace App\Controller;

use App\Entity\Favoris;
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FavorisController extends AbstractController
{
    #[Route('/favoris', name: 'app_favoris_list')]
    #[IsGranted('ROLE_USER')]
    public function list(): Response
    {
        $user = $this->getUser();
        $favoris = $user->getFavoris();

        return $this->render('favoris/list.html.twig', [
            'favoris' => $favoris
        ]);
    }

    #[Route('/article/{id}/favoris', name: 'app_favoris_toggle')]
    #[IsGranted('ROLE_USER')]
    public function toggle(Article $article, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        // Chercher si l'article est déjà en favoris
        $existingFavoris = $entityManager->getRepository(Favoris::class)->findOneBy([
            'user' => $user,
            'article' => $article
        ]);

        if ($existingFavoris) {
            // Si oui, on le supprime
            $entityManager->remove($existingFavoris);
            $message = 'Article retiré des favoris';
        } else {
            // Si non, on l'ajoute
            $favoris = new Favoris();
            $favoris->setUser($user);
            $favoris->setArticle($article);
            $entityManager->persist($favoris);
            $message = 'Article ajouté aux favoris';
        }

        $entityManager->flush();

        $this->addFlash('success', $message);

        // Rediriger vers la page précédente
        return $this->redirect($this->generateUrl('article_index', ['id' => $article->getId()]));
    }
}
