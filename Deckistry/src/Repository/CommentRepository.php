<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Deck;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Find all comments for a deck
     *
     * @return Comment[]
     */
    public function findByDeck(Deck $deck): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.deck = :deck')
            ->setParameter('deck', $deck)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all comments by a user
     *
     * @return Comment[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count comments for a deck
     */
    public function countByDeck(Deck $deck): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.deck = :deck')
            ->setParameter('deck', $deck)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find recent comments for a deck
     *
     * @return Comment[]
     */
    public function findRecentByDeck(Deck $deck, int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.deck = :deck')
            ->setParameter('deck', $deck)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
