<?php

namespace App\Entity;

use App\Repository\CardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CardRepository::class)]
#[ORM\Table(name: 'cards')]
#[ORM\Index(name: 'idx_cards_name', columns: ['name'])]
#[ORM\Index(name: 'idx_cards_type_line', columns: ['type_line'])]
class Card
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $scryfallId = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typeLine = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $manaCost = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $oracleText = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $imageUri = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $imageUriSmall = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $colors = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $cmc = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $rarity = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $setCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $setName = null;

    #[ORM\Column(length: 10, options: ['default' => 'en'])]
    private ?string $lang = 'en';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $colorIdentity = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $keywords = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getTypeLine(): ?string
    {
        return $this->typeLine;
    }

    public function setTypeLine(?string $typeLine): static
    {
        $this->typeLine = $typeLine;
        return $this;
    }

    public function getManaCost(): ?string
    {
        return $this->manaCost;
    }

    public function setManaCost(?string $manaCost): static
    {
        $this->manaCost = $manaCost;
        return $this;
    }

    public function getOracleText(): ?string
    {
        return $this->oracleText;
    }

    public function setOracleText(?string $oracleText): static
    {
        $this->oracleText = $oracleText;
        return $this;
    }

    public function getImageUri(): ?string
    {
        return $this->imageUri;
    }

    public function setImageUri(?string $imageUri): static
    {
        $this->imageUri = $imageUri;
        return $this;
    }

    public function getImageUriSmall(): ?string
    {
        return $this->imageUriSmall;
    }

    public function setImageUriSmall(?string $imageUriSmall): static
    {
        $this->imageUriSmall = $imageUriSmall;
        return $this;
    }

    public function getColors(): ?array
    {
        return $this->colors;
    }

    public function setColors(?array $colors): static
    {
        $this->colors = $colors;
        return $this;
    }

    public function getCmc(): ?float
    {
        return $this->cmc;
    }

    public function setCmc(?float $cmc): static
    {
        $this->cmc = $cmc;
        return $this;
    }

    public function getRarity(): ?string
    {
        return $this->rarity;
    }

    public function setRarity(?string $rarity): static
    {
        $this->rarity = $rarity;
        return $this;
    }

    public function getSetCode(): ?string
    {
        return $this->setCode;
    }

    public function setSetCode(?string $setCode): static
    {
        $this->setCode = $setCode;
        return $this;
    }

    public function getSetName(): ?string
    {
        return $this->setName;
    }

    public function setSetName(?string $setName): static
    {
        $this->setName = $setName;
        return $this;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function setLang(string $lang): static
    {
        $this->lang = $lang;
        return $this;
    }

    public function getColorIdentity(): ?array
    {
        return $this->colorIdentity;
    }

    public function setColorIdentity(?array $colorIdentity): static
    {
        $this->colorIdentity = $colorIdentity;
        return $this;
    }

    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    public function setKeywords(?array $keywords): static
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    /**
     * Crée une instance Card à partir de données Scryfall
     */
    public static function fromScryfallData(array $data): self
    {
        $card = new self();
        $card->setScryfallId($data['id'] ?? '');
        $card->setName($data['name'] ?? '');
        $card->setTypeLine($data['type_line'] ?? $data['typeLine'] ?? null);
        $card->setManaCost($data['mana_cost'] ?? $data['manaCost'] ?? null);
        $card->setOracleText($data['oracle_text'] ?? $data['oracleText'] ?? null);
        
        // Images - gérer tous les formats possibles de Scryfall
        // Format normal: image_uris.normal / image_uris.small
        // Format double-face: card_faces[0].image_uris
        $imageUri = null;
        $imageUriSmall = null;
        
        if (isset($data['image_uris'])) {
            $imageUri = $data['image_uris']['normal'] ?? $data['image_uris']['large'] ?? null;
            $imageUriSmall = $data['image_uris']['small'] ?? $data['image_uris']['normal'] ?? null;
        } elseif (isset($data['card_faces'][0]['image_uris'])) {
            // Cartes double-face
            $imageUri = $data['card_faces'][0]['image_uris']['normal'] ?? $data['card_faces'][0]['image_uris']['large'] ?? null;
            $imageUriSmall = $data['card_faces'][0]['image_uris']['small'] ?? $data['card_faces'][0]['image_uris']['normal'] ?? null;
        } elseif (isset($data['imageUri'])) {
            // Format camelCase du JS
            $imageUri = $data['imageUri'];
            $imageUriSmall = $data['imageUriSmall'] ?? null;
        }
        
        $card->setImageUri($imageUri);
        $card->setImageUriSmall($imageUriSmall);
        
        $card->setColors($data['colors'] ?? []);
        $card->setCmc($data['cmc'] ?? 0);
        $card->setRarity($data['rarity'] ?? null);
        $card->setSetCode($data['set'] ?? $data['setCode'] ?? null);
        $card->setSetName($data['set_name'] ?? $data['setName'] ?? null);
        $card->setLang($data['lang'] ?? 'en');
        $card->setColorIdentity($data['color_identity'] ?? $data['colorIdentity'] ?? []);
        $card->setKeywords($data['keywords'] ?? []);
        
        return $card;
    }

    /**
     * Convertit la carte en tableau pour l'API
     */
    public function toArray(): array
    {
        return [
            'id' => $this->scryfallId,
            'name' => $this->name,
            'typeLine' => $this->typeLine,
            'manaCost' => $this->manaCost,
            'oracleText' => $this->oracleText,
            'imageUri' => $this->imageUri,
            'imageUriSmall' => $this->imageUriSmall,
            'colors' => $this->colors,
            'cmc' => $this->cmc,
            'rarity' => $this->rarity,
            'setCode' => $this->setCode,
            'setName' => $this->setName,
            'lang' => $this->lang,
            'colorIdentity' => $this->colorIdentity,
            'keywords' => $this->keywords,
        ];
    }
}
