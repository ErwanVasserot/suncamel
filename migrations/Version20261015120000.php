<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store Stripe payment intent and automatic refund details on bookings.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, ADD stripe_refund_id VARCHAR(255) DEFAULT NULL, ADD stripe_refund_status VARCHAR(30) DEFAULT NULL, ADD refunded_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E00CEDDE28D9B9CD ON booking (stripe_payment_intent_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E00CEDDE1A112B64 ON booking (stripe_refund_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_E00CEDDE28D9B9CD ON booking');
        $this->addSql('DROP INDEX UNIQ_E00CEDDE1A112B64 ON booking');
        $this->addSql('ALTER TABLE booking DROP stripe_payment_intent_id, DROP stripe_refund_id, DROP stripe_refund_status, DROP refunded_at');
    }
}
