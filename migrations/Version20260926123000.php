<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260926123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align booking date metadata and generated index names with Doctrine mapping.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking_item DROP FOREIGN KEY FK_1692C35C3301C60');
        $this->addSql('ALTER TABLE booking_item DROP FOREIGN KEY FK_1692C35C4584665A');
        $this->addSql('ALTER TABLE booking_item CHANGE pickup_at pickup_at DATETIME NOT NULL, CHANGE return_at return_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX IDX_1692C35C3301C60 ON booking_item');
        $this->addSql('CREATE INDEX IDX_78A07503301C60 ON booking_item (booking_id)');
        $this->addSql('DROP INDEX IDX_1692C35C4584665A ON booking_item');
        $this->addSql('CREATE INDEX IDX_78A07504584665A ON booking_item (product_id)');
        $this->addSql('ALTER TABLE booking_item ADD CONSTRAINT FK_1692C35C3301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking_item ADD CONSTRAINT FK_1692C35C4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE booking CHANGE created_at created_at DATETIME NOT NULL, CHANGE expires_at expires_at DATETIME NOT NULL, CHANGE paid_at paid_at DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_E00CEDDEB1367A3E ON booking');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E00CEDDE1A314A57 ON booking (stripe_session_id)');
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_EMAIL ON app_user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E9E7927C74 ON app_user (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking_item DROP FOREIGN KEY FK_1692C35C3301C60');
        $this->addSql('ALTER TABLE booking_item DROP FOREIGN KEY FK_1692C35C4584665A');
        $this->addSql('DROP INDEX IDX_78A07503301C60 ON booking_item');
        $this->addSql('CREATE INDEX IDX_1692C35C3301C60 ON booking_item (booking_id)');
        $this->addSql('DROP INDEX IDX_78A07504584665A ON booking_item');
        $this->addSql('CREATE INDEX IDX_1692C35C4584665A ON booking_item (product_id)');
        $this->addSql('ALTER TABLE booking_item ADD CONSTRAINT FK_1692C35C3301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking_item ADD CONSTRAINT FK_1692C35C4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('DROP INDEX UNIQ_88BDF3E9E7927C74 ON app_user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON app_user (email)');
        $this->addSql('DROP INDEX UNIQ_E00CEDDE1A314A57 ON booking');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E00CEDDEB1367A3E ON booking (stripe_session_id)');
    }
}
