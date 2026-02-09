<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260120205113 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_follow (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, follower_id INT NOT NULL, following_id INT NOT NULL, INDEX idx_follow_follower (follower_id), INDEX idx_follow_following (following_id), UNIQUE INDEX unique_follow (follower_id, following_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user_follow ADD CONSTRAINT FK_D665F4DAC24F853 FOREIGN KEY (follower_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_follow ADD CONSTRAINT FK_D665F4D1816E3A3 FOREIGN KEY (following_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD follower_count INT DEFAULT 0 NOT NULL, ADD following_count INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_follow DROP FOREIGN KEY FK_D665F4DAC24F853');
        $this->addSql('ALTER TABLE user_follow DROP FOREIGN KEY FK_D665F4D1816E3A3');
        $this->addSql('DROP TABLE user_follow');
        $this->addSql('ALTER TABLE `user` DROP follower_count, DROP following_count');
    }
}
