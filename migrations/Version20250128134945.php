<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250128134945 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE intervention_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE marque_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE modele_interventions_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE modele_planning_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE produit_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE type_intervention_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE zone_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE commentaire_intervention (technicien_id INT NOT NULL, intervention_id INT NOT NULL, contenu TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(technicien_id, intervention_id))');
        $this->addSql('CREATE INDEX IDX_224C655213457256 ON commentaire_intervention (technicien_id)');
        $this->addSql('CREATE INDEX IDX_224C65528EAE3863 ON commentaire_intervention (intervention_id)');
        $this->addSql('COMMENT ON COLUMN commentaire_intervention.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE intervention (id INT NOT NULL, type_intervention_id INT DEFAULT NULL, client_id INT DEFAULT NULL, technicien_id INT DEFAULT NULL, velo_categorie VARCHAR(255) DEFAULT NULL, velo_electrique BOOLEAN DEFAULT NULL, velo_marque VARCHAR(255) DEFAULT NULL, velo_modele VARCHAR(255) DEFAULT NULL, adresse VARCHAR(255) DEFAULT NULL, commentaire_client TEXT DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, debut TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D11814AB799AAC17 ON intervention (type_intervention_id)');
        $this->addSql('CREATE INDEX IDX_D11814AB19EB6921 ON intervention (client_id)');
        $this->addSql('CREATE INDEX IDX_D11814AB13457256 ON intervention (technicien_id)');
        $this->addSql('CREATE TABLE intervention_produit (produit_id INT NOT NULL, intervention_id INT NOT NULL, quantite INT NOT NULL, prix NUMERIC(10, 2) DEFAULT NULL, designation VARCHAR(255) DEFAULT NULL, PRIMARY KEY(produit_id, intervention_id))');
        $this->addSql('CREATE INDEX IDX_624B9842F347EFB ON intervention_produit (produit_id)');
        $this->addSql('CREATE INDEX IDX_624B98428EAE3863 ON intervention_produit (intervention_id)');
        $this->addSql('CREATE TABLE marque (id INT NOT NULL, nom VARCHAR(255) NOT NULL, logo VARCHAR(255) DEFAULT NULL, couleur VARCHAR(7) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE modele_interventions (id INT NOT NULL, type_intervention_id INT DEFAULT NULL, modele_intervention_id INT DEFAULT NULL, interventiontime TIME(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1E51F4AA799AAC17 ON modele_interventions (type_intervention_id)');
        $this->addSql('CREATE INDEX IDX_1E51F4AA6056A4E3 ON modele_interventions (modele_intervention_id)');
        $this->addSql('CREATE TABLE modele_planning (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE produit (id INT NOT NULL, designation VARCHAR(255) NOT NULL, prix NUMERIC(10, 2) DEFAULT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, modified_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN produit.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE type_intervention (id INT NOT NULL, nom VARCHAR(255) NOT NULL, duree TIME(0) WITHOUT TIME ZONE NOT NULL, prix_depart NUMERIC(10, 2) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('CREATE TABLE zone (id INT NOT NULL, technicien_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, color VARCHAR(7) DEFAULT NULL, coordinates JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A0EBC00713457256 ON zone (technicien_id)');
        $this->addSql('ALTER TABLE commentaire_intervention ADD CONSTRAINT FK_224C655213457256 FOREIGN KEY (technicien_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE commentaire_intervention ADD CONSTRAINT FK_224C65528EAE3863 FOREIGN KEY (intervention_id) REFERENCES intervention (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB799AAC17 FOREIGN KEY (type_intervention_id) REFERENCES type_intervention (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB19EB6921 FOREIGN KEY (client_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB13457256 FOREIGN KEY (technicien_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE intervention_produit ADD CONSTRAINT FK_624B9842F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE intervention_produit ADD CONSTRAINT FK_624B98428EAE3863 FOREIGN KEY (intervention_id) REFERENCES intervention (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE modele_interventions ADD CONSTRAINT FK_1E51F4AA799AAC17 FOREIGN KEY (type_intervention_id) REFERENCES type_intervention (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE modele_interventions ADD CONSTRAINT FK_1E51F4AA6056A4E3 FOREIGN KEY (modele_intervention_id) REFERENCES modele_planning (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE zone ADD CONSTRAINT FK_A0EBC00713457256 FOREIGN KEY (technicien_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE intervention_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE marque_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE modele_interventions_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE modele_planning_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE produit_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE type_intervention_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE zone_id_seq CASCADE');
        $this->addSql('ALTER TABLE commentaire_intervention DROP CONSTRAINT FK_224C655213457256');
        $this->addSql('ALTER TABLE commentaire_intervention DROP CONSTRAINT FK_224C65528EAE3863');
        $this->addSql('ALTER TABLE intervention DROP CONSTRAINT FK_D11814AB799AAC17');
        $this->addSql('ALTER TABLE intervention DROP CONSTRAINT FK_D11814AB19EB6921');
        $this->addSql('ALTER TABLE intervention DROP CONSTRAINT FK_D11814AB13457256');
        $this->addSql('ALTER TABLE intervention_produit DROP CONSTRAINT FK_624B9842F347EFB');
        $this->addSql('ALTER TABLE intervention_produit DROP CONSTRAINT FK_624B98428EAE3863');
        $this->addSql('ALTER TABLE modele_interventions DROP CONSTRAINT FK_1E51F4AA799AAC17');
        $this->addSql('ALTER TABLE modele_interventions DROP CONSTRAINT FK_1E51F4AA6056A4E3');
        $this->addSql('ALTER TABLE zone DROP CONSTRAINT FK_A0EBC00713457256');
        $this->addSql('DROP TABLE commentaire_intervention');
        $this->addSql('DROP TABLE intervention');
        $this->addSql('DROP TABLE intervention_produit');
        $this->addSql('DROP TABLE marque');
        $this->addSql('DROP TABLE modele_interventions');
        $this->addSql('DROP TABLE modele_planning');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE type_intervention');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE zone');
    }
}
