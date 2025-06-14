<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/*
* @method User|null find($id, $lockMode = null, $lockVersion = null)
* @method User|null findOneBy(array $criteria, array $orderBy = null)
* @method User[]	findAll()
* @method User[]	findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
*/
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->save($user, true);
    }

    // compter les nombre des utilisateurs ayant le rôle d'administrateur
    public function countAdmins(): int
    {
        $qb = $this->createQueryBuilder('u');
        return $qb
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role_admin OR u.roles LIKE :role_super_admin')
            ->setParameter('role_admin', '%ROLE_ADMIN%')
            ->setParameter('role_super_admin', '%ROLE_SUPER_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    public function findAllExceptCurrentSortedByName(int $currentUserId): array 
    {
        return $this->createQueryBuilder('u')
            ->where('u.id != :currentUserId')
            ->setParameter('currentUserId', $currentUserId)
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
