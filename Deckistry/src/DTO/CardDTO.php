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
        public readonly bool $isDoubleFaced = false,
        public readonly ?string $backImageUriSmall = null,
        public readonly ?string $backImageUriNormal = null,
        public readonly ?string $backImageUriLarge = null,
    ) {}

    /**
     * Create a CardDTO from Scryfall API response
     */
    public static function fromScryfallData(array $data): self
    {
        // Handle double-faced cards (transform, modal_dfc, etc.)
        // For these layouts, some data is only in card_faces
        $hasFaces = isset($data['card_faces']) && is_array($data['card_faces']) && count($data['card_faces']) > 0;
        $firstFace = $hasFaces ? $data['card_faces'][0] : [];
        $secondFace = ($hasFaces && isset($data['card_faces'][1])) ? $data['card_faces'][1] : null;
        
        // Image URIs: use root level first, fallback to first face
        $imageUris = $data['image_uris'] ?? ($firstFace['image_uris'] ?? null);
        
        // Back face image URIs (for double-faced cards)
        $backImageUris = $secondFace['image_uris'] ?? null;
        
        // Mana cost: use root level first, fallback to first face
        $manaCost = $data['mana_cost'] ?? ($firstFace['mana_cost'] ?? null);
        
        // Oracle text: use root level first, fallback to first face
        $oracleText = $data['oracle_text'] ?? ($firstFace['oracle_text'] ?? null);
        
        // Power/Toughness/Loyalty: use root level first, fallback to first face
        $power = $data['power'] ?? ($firstFace['power'] ?? null);
        $toughness = $data['toughness'] ?? ($firstFace['toughness'] ?? null);
        $loyalty = $data['loyalty'] ?? ($firstFace['loyalty'] ?? null);
        
        return new self(
            id: $data['id'] ?? '',
            name: $data['name'] ?? 'Unknown Card',
            manaCost: $manaCost,
            typeLine: $data['type_line'] ?? null,
            oracleText: $oracleText,
            power: $power,
            toughness: $toughness,
            loyalty: $loyalty,
            colors: $data['colors'] ?? [],
            colorIdentity: $data['color_identity'] ?? [],
            setCode: $data['set'] ?? null,
            setName: $data['set_name'] ?? null,
            rarity: $data['rarity'] ?? null,
            imageUriSmall: $imageUris['small'] ?? null,
            imageUriNormal: $imageUris['normal'] ?? null,
            imageUriLarge: $imageUris['large'] ?? null,
            imageUriArtCrop: $imageUris['art_crop'] ?? null,
            eurPrice: isset($data['prices']['eur']) ? (float)$data['prices']['eur'] : null,
            usdPrice: isset($data['prices']['usd']) ? (float)$data['prices']['usd'] : null,
            scryfallUri: $data['scryfall_uri'] ?? null,
            isDoubleFaced: $hasFaces && $secondFace !== null && isset($secondFace['image_uris']),
            backImageUriSmall: $backImageUris['small'] ?? null,
            backImageUriNormal: $backImageUris['normal'] ?? null,
            backImageUriLarge: $backImageUris['large'] ?? null,
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

    /**
     * Get best available back image URI (for double-faced cards)
     */
    public function getBestBackImageUri(): ?string
    {
        return $this->backImageUriNormal 
            ?? $this->backImageUriLarge 
            ?? $this->backImageUriSmall 
            ?? null;
    }
}
