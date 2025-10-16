<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015112953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sortie_invites (sortie_id INT NOT NULL, participant_id INT NOT NULL, INDEX IDX_C1AE6101CC72D953 (sortie_id), INDEX IDX_C1AE61019D1C3019 (participant_id), PRIMARY KEY(sortie_id, participant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sortie_invites ADD CONSTRAINT FK_C1AE6101CC72D953 FOREIGN KEY (sortie_id) REFERENCES sortie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sortie_invites ADD CONSTRAINT FK_C1AE61019D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sortie ADD privee TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sortie_invites DROP FOREIGN KEY FK_C1AE6101CC72D953');
        $this->addSql('ALTER TABLE sortie_invites DROP FOREIGN KEY FK_C1AE61019D1C3019');
        $this->addSql('DROP TABLE sortie_invites');
        $this->addSql('ALTER TABLE sortie DROP privee');
    }
}
