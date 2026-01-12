<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260111222659 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, icon VARCHAR(50) DEFAULT NULL, sort_order INT DEFAULT 0 NOT NULL, parent_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_64C19C1989D9B62 (slug), INDEX idx_category_parent (parent_id), INDEX idx_category_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE email_subscriber CHANGE verification_expires_at verification_expires_at DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE email_subscriber RENAME INDEX idx_email_subscriber_watch TO IDX_1B3A9B6C75EBA2');
        $this->addSql('DROP INDEX idx_notification_type_sent ON notification');
        $this->addSql('DROP INDEX idx_price_check_watch_success ON price_check');
        $this->addSql('DROP INDEX idx_public_watches ON product_watch');
        $this->addSql('ALTER TABLE product_watch ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_watch ADD CONSTRAINT FK_F157317A12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_category ON product_watch (category_id)');
        $this->addSql('DROP INDEX idx_user_verified ON user');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');
        $this->addSql('DROP TABLE category');
        $this->addSql('ALTER TABLE email_subscriber CHANGE verification_expires_at verification_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE email_subscriber RENAME INDEX idx_1b3a9b6c75eba2 TO IDX_EMAIL_SUBSCRIBER_WATCH');
        $this->addSql('CREATE INDEX idx_notification_type_sent ON notification (type, sent_at)');
        $this->addSql('CREATE INDEX idx_price_check_watch_success ON price_check (product_watch_id, was_successful)');
        $this->addSql('ALTER TABLE product_watch DROP FOREIGN KEY FK_F157317A12469DE2');
        $this->addSql('DROP INDEX idx_category ON product_watch');
        $this->addSql('ALTER TABLE product_watch DROP category_id');
        $this->addSql('CREATE INDEX idx_public_watches ON product_watch (is_public, is_active)');
        $this->addSql('CREATE INDEX idx_user_verified ON `user` (is_verified)');
    }
}
