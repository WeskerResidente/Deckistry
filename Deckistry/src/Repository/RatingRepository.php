<?php

namespace App\Repository;

use App\Entity\Deck;
use App\Entity\Rating;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rating>
 */
class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    /**
     * Find a rating by user and deck
     */
    public function findByUserAndDeck(User $user, Deck $deck): ?Rating
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.deck = :deck')
            ->setParameter('user', $user)
            ->setParameter('deck', $deck)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get average rating for a deck
     */
    public function getAverageRating(Deck $deck): float
    {
        return (float) $this->createQueryBuilder('r')
            ->select('AVG(r.rating)')
            ->andWhere('r.deck = :deck')
            ->setParameter('deck', $deck)
            ->getQuery()
            ->getSingleScalarResult() ?? 0.0;
    }

    /**
     * Count ratings for a deck
     */
    public function countByDeck(Deck $deck): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.deck = :deck')
            ->setParameter('deck', $deck)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find all ratings for a deck
     *
     * @return Rating[]
     */
    public function findByDeck(Deck $deck): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.deck = :deck')
            ->setParameter('deck', $deck)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all ratings by a user
     *
     * @return Rating[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get rating distribution for a deck
     * Returns an array with keys 1-5 and count of each rating
     */
    public function getRatingDistribution(Deck $deck): array
    {
        $results = $this->createQueryBuilder('r')
            ->select('r.rating, COUNT(r.id) as count')
            ->andWhere('r.deck = :deck')
            ->setParameter('deck', $deck)
            ->groupBy('r.rating')
            ->getQuery()
            ->getResult();

        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($results as $result) {
            $distribution[$result['rating']] = (int) $result['count'];
        }

        return $distribution;
    }
}
