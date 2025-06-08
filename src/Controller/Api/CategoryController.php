<?php
namespace App\Controller\Api;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/api')]
class CategoryController extends AbstractController
{
    #[Route('/categorie/datatable', name:'api_categorie_datatable', methods:['GET', 'POST'])]
    public function datatable(
        Request $request,
        CategoryRepository $categoryRepository,
    ): JsonResponse{
        $draw = $request->request->getInt('draw', 0);
        $start = $request->request->getInt('start', 0);
        $length = $request->request->getInt('length', 10);
        $search = $request->request->all('search', []);
        $orders = $request->request->all('order', []);

        // colonnes pour le tri
        $colums = [
            0=> 'c.id', 
            1=> 'c.title',
            2=> 'c.description',
            3=> 'c.createdAt',
        ]; 

        // ordre de tri
        $orderColumn = $colums[$orders[0]['column'] ?? 0] ?? 'c.id';
        $orderDir = $orders[0]['dir'] ?? 'des';

        // Recuperations des données
        $results = $categoryRepository->findForDatatable(
            $start,
            $length,
            $search,
            $orderColumn,
            $orderDir
        );

        // formatage des données pour les datatable
        $data = [];
        foreach ($results['data'] as $category) {
            $data[] = [
                'id' => $category->getId(),
                'title' => $category->getTitle(),
                'description' => $category->getDescription(),
                'createdAt' => $category->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $results['total'],
            'recordsFiltered' => $results['filtered'],
            'data' => $data,
        ]);

    }
    #[Route('/categorie/search', name: 'api_categorie_search', methods: ['GET'])]
    public function search(
        Request $request,
        CategoryRepository $categoryRepository
    ){
        $query = $request->query->get('q', '');

        if (strlen($query) < 3) {
            return new JsonResponse([]);
        }

        $categories = $categoryRepository->searchByTitle($query, 10);

        $results = [];
        foreach ($categories as $category) {
            $results[] = [
                'id' => $category->getId(),
                'title' => $category->getTitle(),
                'description' => $category->getDescription(),
            ];
        }
        return new JsonResponse(['result' => $results]);
    }

}