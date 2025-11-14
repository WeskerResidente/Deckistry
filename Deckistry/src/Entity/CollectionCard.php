<?php

namespace App\Entity;

use App\Repository\CollectionCardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CollectionCardRepository::class)]
#[ORM\Table(name: 'collection_cards')]
#[ORM\Index(name: 'idx_collection_cards_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_collection_cards_scryfall_id', columns: ['scryfall_id'])]
class CollectionCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'collectionCards')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $scryfallId = null;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private ?int $quantity = 1;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isFoil = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
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
        return 'CollectionCard#' . $this->id . ' (' . $this->scryfallId . ' x' . $this->quantity . ')';
    }
}
