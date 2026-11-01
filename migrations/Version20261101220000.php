<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261101220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make the Explore Raglan illustrated map editable in site images.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO site_image (source, label) SELECT ?, ? WHERE NOT EXISTS (SELECT 1 FROM site_image WHERE source = ?)', [
            '/images/suncamel/explore/raglan-map.png',
            'Explore Raglan — carte illustrée',
            '/images/suncamel/explore/raglan-map.png',
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM site_image WHERE source = ?', ['/images/suncamel/explore/raglan-map.png']);
    }
}
