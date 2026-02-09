<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209150457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE droid (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, primary_function VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE starship (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, captain VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, arrived_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, slug VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C414E64A989D9B62 ON starship (slug)');
        $this->addSql('COMMENT ON COLUMN starship.arrived_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE starship_droid (id SERIAL NOT NULL, droid_id INT NOT NULL, starship_id INT NOT NULL, assigned_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1C7FBE88AB064EF ON starship_droid (droid_id)');
        $this->addSql('CREATE INDEX IDX_1C7FBE889B24DF5 ON starship_droid (starship_id)');
        $this->addSql('COMMENT ON COLUMN starship_droid.assigned_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE starship_part (id SERIAL NOT NULL, starship_id INT NOT NULL, name VARCHAR(255) NOT NULL, price INT NOT NULL, notes TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_41C447379B24DF5 ON starship_part (starship_id)');
        $this->addSql('ALTER TABLE starship_droid ADD CONSTRAINT FK_1C7FBE88AB064EF FOREIGN KEY (droid_id) REFERENCES droid (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE starship_droid ADD CONSTRAINT FK_1C7FBE889B24DF5 FOREIGN KEY (starship_id) REFERENCES starship (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE starship_part ADD CONSTRAINT FK_41C447379B24DF5 FOREIGN KEY (starship_id) REFERENCES starship (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE starship_droid DROP CONSTRAINT FK_1C7FBE88AB064EF');
        $this->addSql('ALTER TABLE starship_droid DROP CONSTRAINT FK_1C7FBE889B24DF5');
        $this->addSql('ALTER TABLE starship_part DROP CONSTRAINT FK_41C447379B24DF5');
        $this->addSql('DROP TABLE droid');
        $this->addSql('DROP TABLE starship');
        $this->addSql('DROP TABLE starship_droid');
        $this->addSql('DROP TABLE starship_part');
    }
}
