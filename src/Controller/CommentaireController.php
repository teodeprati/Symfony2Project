<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

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
        $form = $this->createFormBuilder($commentaire)
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu du commentaire',
                'attr' => ['class' => 'form-control', 'rows' => 5],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Mettre à jour', 'attr' => ['class' => 'btn btn-primary']])
            ->getForm();

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
}
