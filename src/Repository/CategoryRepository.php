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

    public function findAuthorizedCategory(?int $roleId): array
    {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('App\Entity\Permission', 'p', 'WITH', 'p.entity_id = c.id AND p.entity_type = :type')
            ->setParameter('type', 'category');

        if ($roleId !== null) {
            $qb->andWhere('p.role_id = :roleId')
                ->setParameter('roleId', $roleId);
        } else {
            $qb->andWhere('p.role_id IS NULL');
        }

        return $qb->getQuery()->getResult();
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
