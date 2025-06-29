<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleForm;
use App\Form\CommentForm;
use App\Entity\Comment;
use App\Entity\User;
use App\Repository\ArticleLikeRepository;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface; // pour la pagination

#[Route('/article')]
final class ArticleController extends AbstractController
{
    #[Route(name: 'app_article_index', methods: ['GET', 'POST'])]
    public function index(
        ArticleRepository $articleRepository, 
        PaginatorInterface $paginator, 
        Request $request,
        EntityManagerInterface $entityManager
        ): Response
    {
        // formulaire d'ajout d'article si tout dans l'index
        $article = new Article();
        $form = $this->createForm(ArticleForm::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }
        // Liste des articles
        $query = $articleRepository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery();
        // pagination
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // page number
            3 // limit per page
        );

        return $this->render('article/index.html.twig', [
            'articles' => $articleRepository->findAll(),
            'form' =>$form->createView(),
            'pagination' => $pagination,
        ]);
    }
    // formulaire d'ajout d'article si pas dans l'index
    #[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleForm::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', 'L\'article a été créé avec succès !');
            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }
    // affiche un article et gère l'ajout de commentaires
    // @Route("/article/{id}", name="app_article_show", methods={"GET", "POST"}) 
    #[Route('/{id}', name: 'app_article_show', methods: ['GET', 'POST'])]
    public function show(Article $article, Request $request, EntityManagerInterface $entityManager, ArticleLikeRepository $articleLike): Response
    {
        // creer un nouveau commentaire
        $comment = new Comment();
        $comment->setArticle($article);

        // créer le formulaire de commentaire
        $form = $this->createForm(CommentForm::class, $comment);
        $form->handleRequest($request);

        // traiter le formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setCreatedAt(new \DateTimeImmutable());

            // enregistrement du commentaire
            $entityManager->persist($comment);
            $entityManager->flush();

            // message de succés
            $this->addFlash('success', 'Votre commentaire a été ajouté avec succès !');
            
            // rediriger vers la page de l'article
            return $this->redirectToRoute(
                'app_article_show', 
                ['id' => $article->getId()], 
                Response::HTTP_SEE_OTHER
            );
        }
        // vérifier si l'article est aimé par l'utilisateur
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $isLiked = $user && $articleLike->findOneBy([
            'article' => $article,
            'user' => $user,
        ]) !== null;
        // affichage de la vue de l'article
        return $this->render('article/show.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
            'is_liked' => $isLiked,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ArticleForm::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'L\'article a été modifié avec succès !');
            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($article);
            $entityManager->flush();
            $this->addFlash('success', 'L\'article a été supprimé avec succès !');
        }

        return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
    }
}
