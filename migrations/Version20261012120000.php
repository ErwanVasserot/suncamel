<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261012120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add editable booking confirmation and cancellation email messages.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE booking_email_content (id INT AUTO_INCREMENT NOT NULL, confirmation_message LONGTEXT NOT NULL, cancellation_message LONGTEXT NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql("INSERT INTO booking_email_content (id, confirmation_message, cancellation_message, updated_at) VALUES (1, '<p>Thank you for booking with SunCamel. Your payment has been received.</p>', '<p>Your SunCamel booking has been cancelled.</p>', NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE booking_email_content');
    }
}
