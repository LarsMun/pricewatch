<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add public feed support: username, isPublic fields, and email_subscriber table.
 */
final class Version20260111100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add public feed support with username, isPublic fields, and email_subscriber table';
    }

    public function up(Schema $schema): void
    {
        // Add username and isPublic to user table
        $this->addSql('ALTER TABLE `user` ADD username VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD is_public TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON `user` (username)');

        // Add isPublic and subscriberCount to product_watch table
        $this->addSql('ALTER TABLE product_watch ADD is_public TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE product_watch ADD subscriber_count INT DEFAULT 0 NOT NULL');
        $this->addSql('CREATE INDEX idx_public_watches ON product_watch (is_public, is_active)');

        // Create email_subscriber table
        $this->addSql('CREATE TABLE email_subscriber (
            id INT AUTO_INCREMENT NOT NULL,
            product_watch_id INT NOT NULL,
            email VARCHAR(180) NOT NULL,
            unsubscribe_token VARCHAR(64) NOT NULL,
            is_verified TINYINT(1) DEFAULT 0 NOT NULL,
            verification_token VARCHAR(64) DEFAULT NULL,
            verification_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_EMAIL_SUBSCRIBER_WATCH (product_watch_id),
            INDEX idx_subscriber_email (email),
            INDEX idx_verification_token (verification_token),
            INDEX idx_unsubscribe_token (unsubscribe_token),
            UNIQUE INDEX unique_email_watch (email, product_watch_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_EMAIL_SUBSCRIBER_WATCH FOREIGN KEY (product_watch_id) REFERENCES product_watch (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // Drop email_subscriber table
        $this->addSql('DROP TABLE email_subscriber');

        // Remove product_watch columns
        $this->addSql('DROP INDEX idx_public_watches ON product_watch');
        $this->addSql('ALTER TABLE product_watch DROP is_public');
        $this->addSql('ALTER TABLE product_watch DROP subscriber_count');

        // Remove user columns
        $this->addSql('DROP INDEX UNIQ_8D93D649F85E0677 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP username');
        $this->addSql('ALTER TABLE `user` DROP is_public');
    }
}
