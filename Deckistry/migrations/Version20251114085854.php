<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251114085854 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cards ADD lang VARCHAR(10) DEFAULT \'en\' NOT NULL');
        $this->addSql('ALTER TABLE collection_cards ADD is_foil TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE deck_cards ADD is_foil TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE deck_cards DROP is_foil');
        $this->addSql('ALTER TABLE cards DROP lang');
        $this->addSql('ALTER TABLE collection_cards DROP is_foil');
    }
}
