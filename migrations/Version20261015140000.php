<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align promotion and Stripe refund indexes with the Doctrine mapping.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_C11D7DD377153098 ON promotion');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C11D7DD177153098 ON promotion (code)');
        $this->addSql('ALTER TABLE booking CHANGE refunded_at refunded_at DATETIME DEFAULT NULL, CHANGE subtotal_amount subtotal_amount INT NOT NULL, CHANGE discount_amount discount_amount INT NOT NULL');
        $this->addSql('DROP INDEX UNIQ_E00CEDDE28D9B9CD ON booking');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E00CEDDEFC72F97E ON booking (stripe_payment_intent_id)');
        $this->addSql('DROP INDEX UNIQ_E00CEDDE1A112B64 ON booking');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E00CEDDEA715271F ON booking (stripe_refund_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_C11D7DD177153098 ON promotion');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C11D7DD377153098 ON promotion (code)');
        $this->addSql("ALTER TABLE booking CHANGE refunded_at refunded_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE subtotal_amount subtotal_amount INT DEFAULT 0 NOT NULL, CHANGE discount_amount discount_amount INT DEFAULT 0 NOT NULL");
        $this->addSql('DROP INDEX UNIQ_E00CEDDEFC72F97E ON booking');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E00CEDDE28D9B9CD ON booking (stripe_payment_intent_id)');
        $this->addSql('DROP INDEX UNIQ_E00CEDDEA715271F ON booking');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E00CEDDE1A112B64 ON booking (stripe_refund_id)');
    }
}
