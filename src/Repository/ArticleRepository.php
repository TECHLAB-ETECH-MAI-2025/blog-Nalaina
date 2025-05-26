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
    public function filterByDatatable(
        int $start,
        int $length,
        array $search = [],
        string $orderColumn = 'a.id',
        string $orderDir = 'desc'
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.categories', 'c')
            ->leftJoin('a.comments', 'com')
            ->leftJoin('a.likes', 'l')
            ->groupBy('a.id');

        // Appliquer la recherche si elle existe
        if ($search) {
            $qb->andWhere('a.title LIKE :search OR c.title LIKE :search')
                ->setParameter('search', '%' . $search['value'] . '%');
        }

        // compter le nombre total d'articles
        $totalCount = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        // compter le nombre d'articles filtrés
        $filteredCountQB = clone $qb;
        $filteredCount = $filteredCountQB
            ->select('COUNT(DISTINCT a.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Appliquer le tri
        if ($orderColumn === 'commentsCount') {
            $qb->addSelect('COUNT(com.id) AS commentsCount')
                ->orderBy('commentsCount', $orderDir);
        } elseif ($orderColumn === 'likesCount') {
            $qb->addSelect('COUNT(l.id) AS likesCount')
                ->orderBy('likesCount', $orderDir);
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
        // // Recherche
        // if (!empty($search['value'])) {
        //     $qb->andWhere('a.title LIKE :search OR a.content LIKE :search')
        //         ->setParameter('search', '%' . $search['value'] . '%');
        // }

        // // Tri
        // $qb->orderBy($orderColumn, $orderDir);

        // // Pagination
        // $qb->setFirstResult($start)
        //     ->setMaxResults($length);

        // return [
        //     'data' => $qb->getQuery()->getResult(),
        //     'total' => count($this->findAll()),
        //     'filtered' => count($qb->getQuery()->getResult())
        // ];
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


    //    /**
    //     * @return Article[] Returns an array of Article objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Article
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
