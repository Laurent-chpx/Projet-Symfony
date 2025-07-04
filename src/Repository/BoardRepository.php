<?php

namespace App\Repository;

use App\Entity\Board;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Board>
 */
class BoardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Board::class);
    }

    public function findAuthorizedBoard(?int $roleId): array
    {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('App\Entity\Permission', 'p', 'WITH', 'p.entity_id = c.id AND p.entity_type = :type')
            ->setParameter('type', 'board');

        if ($roleId !== null) {
            $qb->andWhere('p.role_id = :roleId')
                ->setParameter('roleId', $roleId);
        } else {
            $qb->andWhere('p.role_id IS NULL');
        }

        return $qb->getQuery()->getResult();
    }


    //    /**
    //     * @return Board[] Returns an array of Board objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Board
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
