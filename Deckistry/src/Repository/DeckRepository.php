<?php

namespace App\Repository;

use App\Entity\Deck;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deck>
 */
class DeckRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deck::class);
    }

    /**
     * Find all decks by user
     *
     * @return Deck[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->setParameter('user', $user)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent decks (last 10)
     *
     * @return Deck[]
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search decks by name
     *
     * @return Deck[]
     */
    public function searchByName(string $query): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find popular decks (most commented/rated)
     *
     * @return Deck[]
     */
    public function findPopular(int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.comments', 'c')
            ->leftJoin('d.ratings', 'r')
            ->groupBy('d.id')
            ->orderBy('COUNT(c.id) + COUNT(r.id)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total cards in a deck
     */
    public function countCards(Deck $deck): int
    {
        return $this->createQueryBuilder('d')
            ->select('SUM(dc.quantity)')
            ->leftJoin('d.deckCards', 'dc')
            ->where('d.id = :deckId')
            ->setParameter('deckId', $deck->getId())
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }
}
