<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250729153350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE intervention ADD commentaire_technicien TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE intervention ALTER finalisee DROP DEFAULT');
        $this->addSql('ALTER TABLE intervention ALTER finalisee DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE intervention DROP commentaire_technicien');
        $this->addSql('ALTER TABLE intervention ALTER finalisee SET DEFAULT false');
        $this->addSql('ALTER TABLE intervention ALTER finalisee SET NOT NULL');
    }
}
