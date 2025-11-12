<?php

namespace App\Repository;

use App\Entity\CollectionCard;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CollectionCard>
 */
class CollectionCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CollectionCard::class);
    }

    /**
     * Find a card in user's collection by Scryfall ID
     */
    public function findByUserAndScryfallId(User $user, string $scryfallId): ?CollectionCard
    {
        return $this->createQueryBuilder('cc')
            ->andWhere('cc.user = :user')
            ->andWhere('cc.scryfallId = :scryfallId')
            ->setParameter('user', $user)
            ->setParameter('scryfallId', $scryfallId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get all cards in user's collection
     *
     * @return CollectionCard[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('cc')
            ->andWhere('cc.user = :user')
            ->setParameter('user', $user)
            ->orderBy('cc.addedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total number of cards in user's collection
     */
    public function getTotalQuantity(User $user): int
    {
        return (int) $this->createQueryBuilder('cc')
            ->select('SUM(cc.quantity)')
            ->andWhere('cc.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    /**
     * Get unique card count in user's collection
     */
    public function getUniqueCardCount(User $user): int
    {
        return (int) $this->createQueryBuilder('cc')
            ->select('COUNT(cc.id)')
            ->andWhere('cc.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Search cards in user's collection by Scryfall ID
     *
     * @return CollectionCard[]
     */
    public function searchByName(User $user, string $query): array
    {
        return $this->createQueryBuilder('cc')
            ->andWhere('cc.user = :user')
            ->andWhere('cc.scryfallId LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('cc.scryfallId', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recently added cards
     *
     * @return CollectionCard[]
     */
    public function findRecentlyAdded(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('cc')
            ->andWhere('cc.user = :user')
            ->setParameter('user', $user)
            ->orderBy('cc.addedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
