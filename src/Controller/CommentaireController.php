<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CommentaireRepository;
use App\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;
use App\Form\CommentaireType;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comment', name: 'comment_')]
class CommentaireController extends AbstractController
{
    #[Route('/new/{id}', name: 'new', methods: ['POST'])]
    public function new(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $commentaire = new Commentaire(); // Crée une nouvelle instance de l'entité `Commentaire`
        $commentaire->setArticle($article); // relation entre `Commentaire` et `Article`
        $commentaire->setAuteur($this->getUser()); // relation entre `Commentaire` et `User`
        $commentaire->setCreatedAt(new \DateTimeImmutable());// Définit la date de création du commentaire avec l'heure actuelle

        // Récupère directement la valeur "contenu"
        $contenu = $request->request->get('contenu');

        if ($contenu) {
            $commentaire->setContenu($contenu);

            $entityManager->persist($commentaire); // Marque l'objet `Commentaire` pour être ajouté à la base de données.
            $entityManager->flush(); // Exécute les requêtes SQL pour sauvegarder le commentaire en base.

            $this->addFlash('success', 'Votre commentaire a bien été ajouté.');
        } else {
            $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
        }

        return $this->redirectToRoute('article_show', ['id' => $article->getId()]); // l'ID de l'article concerné
    }


    #[Route('/edit/{id}', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commentaire $commentaire, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($commentaire->getAuteur() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce commentaire.');
        }

        // Crée un formulaire pour modifier le commentaire
        $form = $this->createForm(CommentaireType::class, $commentaire);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire modifié avec succès.');

            return $this->redirectToRoute('article_show', ['id' => $commentaire->getArticle()->getId()]);
        }

        return $this->render('/commentaire/edit.html.twig', [
            'form' => $form->createView(),
            'commentaire' => $commentaire,
        ]);
    }


    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Commentaire $commentaire, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($commentaire->getAuteur() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce commentaire.');
        }

        if ($this->isCsrfTokenValid('delete' . $commentaire->getId(), $request->request->get('_token'))) {
            $entityManager->remove($commentaire);
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire supprimé.');
        }

        return $this->redirectToRoute('article_show', ['id' => $commentaire->getArticle()->getId()]);
    }

    // Récupère tous les commentaires et le retrun en format JSON pour Angular
    #[Route('/api/index', name: 'api_commentaire_index', methods: ['GET'])]
    public function index(CommentaireRepository $commentaireRepository): JsonResponse
    {
        // Récupère tous les commentaires depuis la base de données
        $commentaires = $commentaireRepository->findAll();

        // Transforme les commentaires en tableau pour les renvoyer en JSON
        $data = [];
        foreach ($commentaires as $commentaire) {
            $data[] = [
                'id' => $commentaire->getId(),
                'contenu' => $commentaire->getContenu(),
                'auteur' => $commentaire->getAuteur()->getUsername(), // Renvoie uniquement le nom d'utilisateur sinon je vais avoir un objet auteur avec toutes les info de l'auteur
                'dateCreation' => $commentaire->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        // Renvoie les commentaires au format JSON
        return new JsonResponse($data);
    }

    #[Route('/api/delete/{id}', name: 'api_commentaire_delete', methods: ['DELETE'])]
    public function apiDelete(int $id, CommentaireRepository $commentaireRepository, EntityManagerInterface $entityManager, LoggerInterface $logger): JsonResponse
    {
        try {
            $logger->info('Début de la requête de suppression', ['id' => $id]);
            
            // Récupère l'utilisateur courant
            $user = $this->getUser();
            if (!$user) {
                $logger->error('Utilisateur non authentifié');
                return new JsonResponse(['error' => 'Utilisateur non authentifié'], 401);
            }
            
            $logger->info('Utilisateur authentifié', ['username' => $user->getUsername()]);
            
            // Récupère le commentaire
            $commentaire = $commentaireRepository->find($id);
            if (!$commentaire) {
                $logger->error('Commentaire non trouvé', ['id' => $id]);
                return new JsonResponse(['error' => 'Commentaire non trouvé'], 404);
            }
            
            // Vérifie les permissions
            if (!$this->isGranted('ROLE_ADMIN') && $commentaire->getAuteur() !== $user) {
                $logger->error('Permission refusée', [
                    'user_id' => $user->getId(),
                    'comment_author_id' => $commentaire->getAuteur()->getId()
                ]);
                return new JsonResponse(['error' => 'Vous n\'avez pas la permission de supprimer ce commentaire'], 403);
            }
            
            // Supprime le commentaire
            $entityManager->remove($commentaire);
            $entityManager->flush();
            
            $logger->info('Commentaire supprimé avec succès', ['id' => $id]);
            
            return new JsonResponse(['message' => 'Commentaire supprimé avec succès']);
            
        } catch (\Exception $e) {
            $logger->error('Erreur lors de la suppression du commentaire', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse(['error' => 'Une erreur est survenue lors de la suppression du commentaire'], 500);
        }
    }
}
