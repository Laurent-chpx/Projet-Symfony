<?php

namespace App\Repository;

use App\Entity\Permission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Permission>
 *
 * @method Permission|null find($id, $lockMode = null, $lockVersion = null)
 * @method Permission|null findOneBy(array $criteria, array $orderBy = null)
 * @method Permission[]    findAll()
 * @method Permission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Permission::class);
    }

    /**
     * Trouve les permissions pour une entité donnée
     */
    public function findByEntity(string $entityType, int $entityId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.entity_type = :type')
            ->andWhere('p.entity_id = :id')
            ->setParameter('type', $entityType)
            ->setParameter('id', $entityId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un rôle a accès à une entité
     */
    public function hasAccess(string $entityType, int $entityId, int $roleId): bool
    {
        $result = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.entity_type = :type')
            ->andWhere('p.entity_id = :entityId')
            ->andWhere('p.role_id = :roleId')
            ->setParameter('type', $entityType)
            ->setParameter('entityId', $entityId)
            ->setParameter('roleId', $roleId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    /**
     * Supprime toutes les permissions pour une entité
     */
    public function deleteByEntity(string $entityType, int $entityId): void
    {
        $this->createQueryBuilder('p')
            ->delete()
            ->where('p.entity_type = :type')
            ->andWhere('p.entity_id = :id')
            ->setParameter('type', $entityType)
            ->setParameter('id', $entityId)
            ->getQuery()
            ->execute();
    }

    /**
     * Récupère les entités accessibles pour un rôle donné
     */
    public function getAccessibleEntities(string $entityType, int $roleId): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.entity_id')
            ->where('p.entity_type = :type')
            ->andWhere('p.role_id = :roleId')
            ->setParameter('type', $entityType)
            ->setParameter('roleId', $roleId)
            ->getQuery()
            ->getArrayResult();
    }
}
