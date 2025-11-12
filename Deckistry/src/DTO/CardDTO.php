<?php

namespace App\DTO;

/**
 * Data Transfer Object for Scryfall Card data
 * Contains only the most useful fields from Scryfall API
 */
class CardDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $manaCost = null,
        public readonly ?string $typeLine = null,
        public readonly ?string $oracleText = null,
        public readonly ?string $power = null,
        public readonly ?string $toughness = null,
        public readonly ?string $loyalty = null,
        public readonly array $colors = [],
        public readonly array $colorIdentity = [],
        public readonly ?string $setCode = null,
        public readonly ?string $setName = null,
        public readonly ?string $rarity = null,
        public readonly ?string $imageUriSmall = null,
        public readonly ?string $imageUriNormal = null,
        public readonly ?string $imageUriLarge = null,
        public readonly ?string $imageUriArtCrop = null,
        public readonly ?float $eurPrice = null,
        public readonly ?float $usdPrice = null,
        public readonly ?string $scryfallUri = null,
    ) {}

    /**
     * Create a CardDTO from Scryfall API response
     */
    public static function fromScryfallData(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            name: $data['name'] ?? 'Unknown Card',
            manaCost: $data['mana_cost'] ?? null,
            typeLine: $data['type_line'] ?? null,
            oracleText: $data['oracle_text'] ?? null,
            power: $data['power'] ?? null,
            toughness: $data['toughness'] ?? null,
            loyalty: $data['loyalty'] ?? null,
            colors: $data['colors'] ?? [],
            colorIdentity: $data['color_identity'] ?? [],
            setCode: $data['set'] ?? null,
            setName: $data['set_name'] ?? null,
            rarity: $data['rarity'] ?? null,
            imageUriSmall: $data['image_uris']['small'] ?? null,
            imageUriNormal: $data['image_uris']['normal'] ?? null,
            imageUriLarge: $data['image_uris']['large'] ?? null,
            imageUriArtCrop: $data['image_uris']['art_crop'] ?? null,
            eurPrice: isset($data['prices']['eur']) ? (float)$data['prices']['eur'] : null,
            usdPrice: isset($data['prices']['usd']) ? (float)$data['prices']['usd'] : null,
            scryfallUri: $data['scryfall_uri'] ?? null,
        );
    }

    /**
     * Get display name with mana cost
     */
    public function getDisplayName(): string
    {
        if ($this->manaCost) {
            return "{$this->name} {$this->manaCost}";
        }
        return $this->name;
    }

    /**
     * Get creature stats (P/T or Loyalty)
     */
    public function getStats(): ?string
    {
        if ($this->power && $this->toughness) {
            return "{$this->power}/{$this->toughness}";
        }
        if ($this->loyalty) {
            return "Loyalty: {$this->loyalty}";
        }
        return null;
    }

    /**
     * Get best available image URI
     */
    public function getBestImageUri(): ?string
    {
        return $this->imageUriNormal 
            ?? $this->imageUriLarge 
            ?? $this->imageUriSmall 
            ?? null;
    }
}
