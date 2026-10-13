<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261013120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow the display zoom to be configured for each product image.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_image ADD zoom_percent SMALLINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_image DROP zoom_percent');
    }
}
