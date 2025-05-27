<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }
    /**
     * filtre pour les articles
     */
    
    public function findForDatatable(
        int $start,
        int $length,
        array $search = [],
        string $orderColumn = 'a.id',
        string $orderDir = 'desc'
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.categories', 'c')
            ->addSelect('c') 
            // Subquery pour compter les commentaires
            ->addSelect('(SELECT COUNT(com.id) FROM App\Entity\Comment com WHERE com.article = a.id) AS HIDDEN commentsCount')
            // Subquery pour compter les likes
            ->addSelect('(SELECT COUNT(l.id) FROM App\Entity\ArticleLike l WHERE l.article = a.id) AS HIDDEN likesCount');

        // Appliquer la recherche si elle existe
        if (!empty($search['value'])) {
            $qb->andWhere('a.title LIKE :search OR c.title LIKE :search')
                ->setParameter('search', '%' . $search['value'] . '%');
        }

        // Compter le nombre total
        $totalCount = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Compter le nombre filtré
        $filteredCountQB = clone $qb;
        $filteredCount = $filteredCountQB
            ->select('COUNT(DISTINCT a.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Appliquer le tri
        if ($orderColumn === 'commentsCount') {
            $qb->orderBy('commentsCount', $orderDir);
        } elseif ($orderColumn === 'likesCount') {
            $qb->orderBy('likesCount', $orderDir);
        } elseif ($orderColumn === 'categories') {
            $qb->orderBy('c.title', $orderDir);
        } else {
            $qb->orderBy($orderColumn, $orderDir);
        }

        // Appliquer la pagination
        $qb->setFirstResult($start)
        ->setMaxResults($length);

        return [
            'data' => $qb->getQuery()->getResult(),
            'totalCount' => (int) $totalCount,
            'filteredCount' => (int) $filteredCount,
        ];
    }


    /**
     * Recherche des articles par titre
     */
    public function searchByTitle(string $query, int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.categories', 'c')
            ->where('a.title LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

}
