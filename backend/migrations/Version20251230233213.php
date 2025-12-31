<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251230233213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, old_price NUMERIC(10, 2) DEFAULT NULL, new_price NUMERIC(10, 2) DEFAULT NULL, type VARCHAR(50) NOT NULL, sent_at DATETIME NOT NULL, product_watch_id INT NOT NULL, INDEX IDX_BF5476CAC75EBA2 (product_watch_id), INDEX idx_watch_sent (product_watch_id, sent_at), INDEX idx_type (type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE price_check (id INT AUTO_INCREMENT NOT NULL, price NUMERIC(10, 2) DEFAULT NULL, raw_text VARCHAR(500) DEFAULT NULL, was_successful TINYINT NOT NULL, http_status INT DEFAULT NULL, duration_ms INT DEFAULT NULL, error_message VARCHAR(1000) DEFAULT NULL, checked_at DATETIME NOT NULL, product_watch_id INT NOT NULL, INDEX IDX_1747A473C75EBA2 (product_watch_id), INDEX idx_watch_checked (product_watch_id, checked_at), INDEX idx_checked_at (checked_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE product_watch (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(2048) NOT NULL, domain VARCHAR(255) NOT NULL, product_name VARCHAR(500) DEFAULT NULL, price_selector VARCHAR(500) NOT NULL, product_selector VARCHAR(500) DEFAULT NULL, currency VARCHAR(3) DEFAULT \'EUR\' NOT NULL, current_price NUMERIC(10, 2) DEFAULT NULL, previous_price NUMERIC(10, 2) DEFAULT NULL, original_price NUMERIC(10, 2) DEFAULT NULL, last_seen_raw_text VARCHAR(500) DEFAULT NULL, parse_rule_json JSON DEFAULT NULL, selector_context_html LONGTEXT DEFAULT NULL, check_method VARCHAR(20) DEFAULT \'http\' NOT NULL, consecutive_failures INT DEFAULT 0 NOT NULL, next_check_at DATETIME NOT NULL, last_checked_at DATETIME DEFAULT NULL, last_successful_check_at DATETIME DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_F157317AA76ED395 (user_id), INDEX idx_next_check (next_check_at), INDEX idx_domain (domain), INDEX idx_active (is_active), INDEX idx_user_active (user_id, is_active), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAC75EBA2 FOREIGN KEY (product_watch_id) REFERENCES product_watch (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE price_check ADD CONSTRAINT FK_1747A473C75EBA2 FOREIGN KEY (product_watch_id) REFERENCES product_watch (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_watch ADD CONSTRAINT FK_F157317AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAC75EBA2');
        $this->addSql('ALTER TABLE price_check DROP FOREIGN KEY FK_1747A473C75EBA2');
        $this->addSql('ALTER TABLE product_watch DROP FOREIGN KEY FK_F157317AA76ED395');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE price_check');
        $this->addSql('DROP TABLE product_watch');
        $this->addSql('DROP TABLE `user`');
    }
}
