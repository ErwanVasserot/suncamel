<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize booking currency codes to the uppercase ISO/ICU format.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE booking SET currency = UPPER(currency)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE booking SET currency = LOWER(currency)');
    }
}
