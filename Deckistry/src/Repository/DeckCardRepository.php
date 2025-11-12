<?php

namespace App\Repository;

use App\Entity\Deck;
use App\Entity\DeckCard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeckCard>
 */
class DeckCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeckCard::class);
    }

    /**
     * Find a card in a deck by Scryfall ID
     */
    public function findByDeckAndScryfallId(Deck $deck, string $scryfallId): ?DeckCard
    {
        return $this->createQueryBuilder('dc')
            ->andWhere('dc.deck = :deck')
            ->andWhere('dc.scryfallId = :scryfallId')
            ->setParameter('deck', $deck)
            ->setParameter('scryfallId', $scryfallId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get all cards in a deck grouped by type
     *
     * @return DeckCard[]
     */
    public function findByDeck(Deck $deck): array
    {
        return $this->createQueryBuilder('dc')
            ->andWhere('dc.deck = :deck')
            ->setParameter('deck', $deck)
            ->orderBy('dc.scryfallId', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total quantity of cards in a deck
     */
    public function getTotalQuantity(Deck $deck): int
    {
        return (int) $this->createQueryBuilder('dc')
            ->select('SUM(dc.quantity)')
            ->andWhere('dc.deck = :deck')
            ->setParameter('deck', $deck)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    /**
     * Get unique card count in a deck
     */
    public function getUniqueCardCount(Deck $deck): int
    {
        return (int) $this->createQueryBuilder('dc')
            ->select('COUNT(dc.id)')
            ->andWhere('dc.deck = :deck')
            ->setParameter('deck', $deck)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
