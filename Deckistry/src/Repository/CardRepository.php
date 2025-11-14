<?php

namespace App\Repository;

use App\Entity\Card;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    /**
     * Trouve une carte par son Scryfall ID, ou la crée si elle n'existe pas
     */
    public function findOrCreateFromScryfallData(array $scryfallData): Card
    {
        $scryfallId = $scryfallData['id'] ?? null;
        
        if (!$scryfallId) {
            throw new \InvalidArgumentException('Scryfall ID is required');
        }

        // Chercher la carte existante
        $card = $this->find($scryfallId);
        
        if ($card) {
            // Mettre à jour la date de dernière utilisation
            $card->setUpdatedAt(new \DateTime());
            $this->getEntityManager()->flush();
            return $card;
        }

        // Créer une nouvelle carte
        $card = Card::fromScryfallData($scryfallData);
        
        $em = $this->getEntityManager();
        $em->persist($card);
        $em->flush();
        
        return $card;
    }

    /**
     * Trouve plusieurs cartes par leurs Scryfall IDs
     */
    public function findByScryfallIds(array $scryfallIds): array
    {
        if (empty($scryfallIds)) {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->where('c.scryfallId IN (:ids)')
            ->setParameter('ids', $scryfallIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de cartes par nom
     */
    public function searchByName(string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
