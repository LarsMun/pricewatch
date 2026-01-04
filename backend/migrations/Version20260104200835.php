<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260104200835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_user_verification_token ON user');
        $this->addSql('ALTER TABLE user ADD password_reset_token VARCHAR(64) DEFAULT NULL, ADD password_reset_expires_at DATETIME DEFAULT NULL, CHANGE verification_expires_at verification_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP password_reset_token, DROP password_reset_expires_at, CHANGE verification_expires_at verification_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX idx_user_verification_token ON `user` (verification_token)');
    }
}
