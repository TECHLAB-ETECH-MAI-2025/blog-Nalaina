<?php
namespace App\Controller\Api;
use App\Entity\Article;
use App\Form\ArticleForm;
use App\Repository\ArticleRepository;
use App\Entity\Comment;
use App\Form\CommentForm;
use App\Entity\ArticleLike;
use App\Repository\ArticleLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Util\Json;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/api')]  
class ArticleController extends AbstractController
{   
    #[Route('/article/datatable', name: 'api_articles_datatable', methods: ['GET'])]
    public function datatable(
        Request $request,
        ArticleRepository $articleRepository
    ): JsonResponse {
        $draw = $request->request->getInt('draw');
        $start = $request->request->getInt('start');
        $length = $request->request->getInt('length');
        $search = $request->request->all('search', ['value'] ?? null);
        $orders = $request->request->all('order', []);

        // colonnes pour le tri
        $colums = [
            0 => 'a.id',
            1 => 'a.title',
            2 => 'a.categories',
            3 => 'a.commentsCount',
            4 => 'a.likesCount',
            5 => 'a.createdAt',
        ];

        // Ordre de tri
        $orderColumn = $colums[$orders[0]['column'] ?? 0] ?? 'a.id';
        $orderDir = $orders[0]['dir'] ?? 'desc';

        // Récupération des données
        $results = $articleRepository->findForDatatable(
            $start,
            $length,
            $search,
            $orderColumn,
            $orderDir
        );

        // Formatage des données pour les datatable
        $data = [];
        foreach ($results['data'] as $article) {
            $categoryNames = array_map(function($category) {
                return sprintf('%s', $category->getTitle());
            }, $article->getCategories()->toArray());

            $data[] = [
                'id' => $article->getId(),
                'title' =>  sprintf('%s',
                    $this->generateUrl('app_article_show', ['id' => $article->getId()]),
                    htmlspecialchars($article->getTitle())
                ),
                'categories' => implode(' ', $categoryNames),
                'commentsCount' => $article->getComments()->count(),
                'likesCount' => $article->getLikes()->count(),
                'createdAt' => $article->getCreatedAt()->format('d/m/Y H:i'),
                'actions' => $this->renderView('article/_actions.html.twig', [
                    'article' => $article,
                ]),
            ];
        }

        // réponse au format attendu par les datatables
        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $results['totalCount'],
            'recordsFiltered' => $results['filteredCount'],
            'data' => $data,
        ]);
    }

    #[Route('/articles/search', name: 'api_articles_search', methods: ['GET'])]
    public function search(
        Request $request,
        ArticleRepository $articleRepository
    ){
        $query = $request->query->get('q', '');

        if(strlen($query) < 2) {
            return new JsonResponse(['result' => []]);
        }

        $articles = $articleRepository->searchByTitle($query, 10);

		$results = [];
        foreach ($articles as $article) {
            $categoryNames = array_map(function($category) {
                return $category->getTitle();
            }, $article->getCategories()->toArray());

            $results[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'categories' => $categoryNames,
            ];
        }

        return new JsonResponse(['result' => $results]);
    }
    
    #[Route('/article/{id}/comment', name: 'api_article_comment', methods: ['POST'])]
    public function addComment(
        Article $article,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $comment = new Comment();
        $comment->setArticle($article);
        $comment->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(CommentForm::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'commentHtml' => $this->renderView('article/_comment.html.twig', [
                    'comment' => $comment,
                ]),

            ]);
        }
        // If the form is not valid, return an error response
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return new JsonResponse([
            'success' => false,
            'errors' => count($errors) > 0 ? $errors : ['Formulaire invalide.'],
        ], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/api/article/{id}/like', name: 'api_article_like', methods: ['GET','POST'])]
    public function likeArticle(
        Article $article,
        Request $request,
        EntityManagerInterface $entityManager,
        ArticleLikeRepository $articleLikeRepository
    ): JsonResponse {
        // Utiliser l'adresse IP comme identifiant (dans un cas réel, utilisez l'ID utilisateur)
        $ipAddress = $request->getClientIp();

        // Vérifier si l'utilisateur a déjà aimé cet article
        $existingLike = $articleLikeRepository->findOneBy([
            'article' => $article,
            'ipAddress' => $ipAddress,
        ]);

        if ($existingLike) {
            // Si l'utilisateur a déjà aimé, supprimer le like(toggle)
            $entityManager->remove($existingLike);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'liked' => 'false',
                'likesCount' => $article->getLikes()->count(),
            ]);
        }else {
            // Sinon, ajouter un nouveau like
            $like = new ArticleLike();
            $like->setArticle($article);
            $like->setIpAddress($ipAddress);
            $like->setCreatedAt(new \DateTimeImmutable());


            $entityManager->persist($like);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'liked' => 'true',
                'likeCount' => $article->getLikes()->count(),
            ]);
        }
    }
    
}