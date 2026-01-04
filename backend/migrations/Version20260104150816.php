<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260104150816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email verification token fields to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD verification_token VARCHAR(64) DEFAULT NULL, ADD verification_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX idx_user_verification_token ON `user` (verification_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_user_verification_token ON `user`');
        $this->addSql('ALTER TABLE `user` DROP verification_token, DROP verification_expires_at');
    }
}
