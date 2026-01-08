<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add performance indexes for common queries.
 */
final class Version20260107120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add performance indexes for price_check, notification, and user tables';
    }

    public function up(Schema $schema): void
    {
        // Index for finding successful price checks per watch
        $this->addSql('CREATE INDEX idx_price_check_watch_success ON price_check (product_watch_id, was_successful)');

        // Index for finding notifications by type and date
        $this->addSql('CREATE INDEX idx_notification_type_sent ON notification (type, sent_at)');

        // Index for finding verified users
        $this->addSql('CREATE INDEX idx_user_verified ON user (is_verified)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_price_check_watch_success ON price_check');
        $this->addSql('DROP INDEX idx_notification_type_sent ON notification');
        $this->addSql('DROP INDEX idx_user_verified ON user');
    }
}
