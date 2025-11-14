<?php

namespace App\Entity;

use App\Repository\DeckCardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeckCardRepository::class)]
#[ORM\Table(name: 'deck_cards')]
#[ORM\Index(name: 'idx_deck_cards_deck_id', columns: ['deck_id'])]
#[ORM\Index(name: 'idx_deck_cards_card_id', columns: ['card_id'])]
class DeckCard
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Deck::class, inversedBy: 'deckCards')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Deck $deck = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Card::class)]
    #[ORM\JoinColumn(name: 'card_id', referencedColumnName: 'scryfall_id', nullable: false)]
    private ?Card $card = null;

    #[ORM\Column(type: 'integer')]
    private ?int $quantity = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isFoil = false;

    public function getDeck(): ?Deck
    {
        return $this->deck;
    }

    public function setDeck(?Deck $deck): static
    {
        $this->deck = $deck;
        return $this;
    }

    public function getCard(): ?Card
    {
        return $this->card;
    }

    public function setCard(?Card $card): static
    {
        $this->card = $card;
        return $this;
    }

    // Méthode de compatibilité pour l'ancien code
    public function getScryfallId(): ?string
    {
        return $this->card?->getScryfallId();
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

    public function isFoil(): bool
    {
        return $this->isFoil;
    }

    public function setIsFoil(bool $isFoil): static
    {
        $this->isFoil = $isFoil;
        return $this;
    }

    public function __toString(): string
    {
        return ($this->card?->getName() ?? 'Unknown') . ' x' . $this->quantity;
    }
}
