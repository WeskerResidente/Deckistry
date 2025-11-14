<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251113095601 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Créer la table cards
        $this->addSql('CREATE TABLE cards (scryfall_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, type_line VARCHAR(255) DEFAULT NULL, mana_cost VARCHAR(100) DEFAULT NULL, oracle_text LONGTEXT DEFAULT NULL, image_uri VARCHAR(500) DEFAULT NULL, image_uri_small VARCHAR(500) DEFAULT NULL, colors JSON DEFAULT NULL, cmc DOUBLE PRECISION DEFAULT NULL, rarity VARCHAR(50) DEFAULT NULL, set_code VARCHAR(100) DEFAULT NULL, set_name VARCHAR(255) DEFAULT NULL, color_identity JSON DEFAULT NULL, keywords JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX idx_cards_name (name), INDEX idx_cards_type_line (type_line), PRIMARY KEY(scryfall_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Vider deck_cards pour éviter les problèmes de FK
        $this->addSql('DELETE FROM deck_cards');
        
        // Modifier deck_cards
        $this->addSql('DROP INDEX idx_deck_cards_scryfall_id ON deck_cards');
        $this->addSql('DROP INDEX `primary` ON deck_cards');
        $this->addSql('ALTER TABLE deck_cards CHANGE scryfall_id card_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE deck_cards ADD CONSTRAINT FK_C59FA2124ACC9A20 FOREIGN KEY (card_id) REFERENCES cards (scryfall_id)');
        $this->addSql('CREATE INDEX idx_deck_cards_card_id ON deck_cards (card_id)');
        $this->addSql('ALTER TABLE deck_cards ADD PRIMARY KEY (deck_id, card_id)');
    }

    public function down(Schema $schema): void
    {
        // Supprimer la contrainte FK et restaurer l'ancienne structure
        $this->addSql('ALTER TABLE deck_cards DROP FOREIGN KEY FK_C59FA2124ACC9A20');
        $this->addSql('DROP INDEX idx_deck_cards_card_id ON deck_cards');
        $this->addSql('DROP INDEX `PRIMARY` ON deck_cards');
        $this->addSql('ALTER TABLE deck_cards CHANGE card_id scryfall_id VARCHAR(255) NOT NULL');
        $this->addSql('CREATE INDEX idx_deck_cards_scryfall_id ON deck_cards (scryfall_id)');
        $this->addSql('ALTER TABLE deck_cards ADD PRIMARY KEY (deck_id, scryfall_id)');
        
        // Supprimer la table cards
        $this->addSql('DROP TABLE cards');
    }
}
