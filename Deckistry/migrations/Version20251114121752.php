<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251114121752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE decks ADD slug VARCHAR(255) DEFAULT NULL');
        
        // Générer les slugs pour les decks existants
        $this->addSql("
            UPDATE decks 
            SET slug = LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                name, ' ', '-'), 'é', 'e'), 'è', 'e'), 'à', 'a'), 'ô', 'o'), 'ù', 'u'))
            WHERE slug IS NULL
        ");
        
        // Rendre le champ NOT NULL après avoir généré les slugs
        $this->addSql('ALTER TABLE decks MODIFY slug VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE decks DROP slug');
    }
}
