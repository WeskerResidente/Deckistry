<?php

namespace App\Entity;

use App\Repository\DeckCardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeckCardRepository::class)]
#[ORM\Table(name: 'deck_cards')]
#[ORM\Index(name: 'idx_deck_cards_deck_id', columns: ['deck_id'])]
#[ORM\Index(name: 'idx_deck_cards_scryfall_id', columns: ['scryfall_id'])]
class DeckCard
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Deck::class, inversedBy: 'deckCards')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Deck $deck = null;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $scryfallId = null;

    #[ORM\Column(type: 'integer')]
    private ?int $quantity = null;

    public function getDeck(): ?Deck
    {
        return $this->deck;
    }

    public function setDeck(?Deck $deck): static
    {
        $this->deck = $deck;
        return $this;
    }

    public function getScryfallId(): ?string
    {
        return $this->scryfallId;
    }

    public function setScryfallId(string $scryfallId): static
    {
        $this->scryfallId = $scryfallId;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function __toString(): string
    {
        return $this->scryfallId . ' x' . $this->quantity;
    }
}
