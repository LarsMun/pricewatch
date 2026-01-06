<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260106233311 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE collection (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(1024) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX idx_collection_user (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE collection_product_watch (collection_id INT NOT NULL, product_watch_id INT NOT NULL, INDEX IDX_2A76DBB5514956FD (collection_id), INDEX IDX_2A76DBB5C75EBA2 (product_watch_id), PRIMARY KEY (collection_id, product_watch_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE collection ADD CONSTRAINT FK_FC4D6532A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE collection_product_watch ADD CONSTRAINT FK_2A76DBB5514956FD FOREIGN KEY (collection_id) REFERENCES collection (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE collection_product_watch ADD CONSTRAINT FK_2A76DBB5C75EBA2 FOREIGN KEY (product_watch_id) REFERENCES product_watch (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE collection DROP FOREIGN KEY FK_FC4D6532A76ED395');
        $this->addSql('ALTER TABLE collection_product_watch DROP FOREIGN KEY FK_2A76DBB5514956FD');
        $this->addSql('ALTER TABLE collection_product_watch DROP FOREIGN KEY FK_2A76DBB5C75EBA2');
        $this->addSql('DROP TABLE collection');
        $this->addSql('DROP TABLE collection_product_watch');
    }
}
