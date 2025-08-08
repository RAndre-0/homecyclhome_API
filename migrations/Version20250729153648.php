<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250729153648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire_intervention DROP CONSTRAINT fk_224c655213457256');
        $this->addSql('ALTER TABLE commentaire_intervention DROP CONSTRAINT fk_224c65528eae3863');
        $this->addSql('DROP TABLE commentaire_intervention');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE commentaire_intervention (technicien_id INT NOT NULL, intervention_id INT NOT NULL, contenu TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(technicien_id, intervention_id))');
        $this->addSql('CREATE INDEX idx_224c655213457256 ON commentaire_intervention (technicien_id)');
        $this->addSql('CREATE INDEX idx_224c65528eae3863 ON commentaire_intervention (intervention_id)');
        $this->addSql('COMMENT ON COLUMN commentaire_intervention.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE commentaire_intervention ADD CONSTRAINT fk_224c655213457256 FOREIGN KEY (technicien_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE commentaire_intervention ADD CONSTRAINT fk_224c65528eae3863 FOREIGN KEY (intervention_id) REFERENCES intervention (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
