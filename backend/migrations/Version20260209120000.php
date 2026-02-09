<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260209120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite indexes for public feed queries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_public_active_popular ON product_watch (is_public, is_active, subscriber_count)');
        $this->addSql('CREATE INDEX idx_public_username ON `user` (is_public, username)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_public_active_popular ON product_watch');
        $this->addSql('DROP INDEX idx_public_username ON `user`');
    }
}
