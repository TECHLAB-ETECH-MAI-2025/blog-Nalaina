<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findForDatatable($start, $length, $search, $orderColumn, $orderDir): array
    {
        $qb = $this->createQueryBuilder('c');

        $searchValue = $search['value'] ?? '';
        if (!empty($searchValue)) {
            $qb->andWhere('c.title LIKE :search OR c.description LIKE :search')
            ->setParameter('search', '%' . $searchValue. '%');
        }

        $qb->orderBy($orderColumn, $orderDir)
        ->setFirstResult($start)
        ->setMaxResults($length);

        $data = $qb->getQuery()->getResult();

        // Compter le total
        $total = $this->createQueryBuilder('c')
                    ->select('COUNT(c.id)')
                    ->getQuery()
                    ->getSingleScalarResult();

        // Compter les résultats filtrés
        $filtered = clone $qb->select('COUNT(c.id)')
                    ->getQuery()
                    ->getSingleScalarResult();

        return [
            'data' => $data,
            'total' => $total,
            'filtered' => $filtered,
        ];
    }

        //    /**
    //     * @return Category[] Returns an array of Category objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Category
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
