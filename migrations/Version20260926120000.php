<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260926120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add temporary and paid bookings linked to Stripe Checkout.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, reference VARCHAR(32) NOT NULL, status VARCHAR(20) NOT NULL, total_amount INT NOT NULL, currency VARCHAR(3) NOT NULL, stripe_session_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', paid_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_E00CEDDEAEA34913 (reference), UNIQUE INDEX UNIQ_E00CEDDEB1367A3E (stripe_session_id), INDEX IDX_E00CEDDEA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4");
        $this->addSql("CREATE TABLE booking_item (id INT AUTO_INCREMENT NOT NULL, booking_id INT NOT NULL, product_id INT NOT NULL, pickup_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', return_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', quantity INT NOT NULL, unit_amount INT NOT NULL, day_count INT NOT NULL, INDEX IDX_1692C35C3301C60 (booking_id), INDEX IDX_1692C35C4584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4");
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id)');
        $this->addSql('ALTER TABLE booking_item ADD CONSTRAINT FK_1692C35C3301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking_item ADD CONSTRAINT FK_1692C35C4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE booking_item');
        $this->addSql('DROP TABLE booking');
    }
}
