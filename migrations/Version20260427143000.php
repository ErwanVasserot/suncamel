<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add product stock quantity used for availability.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD stock_quantity INT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP stock_quantity');
    }
}
