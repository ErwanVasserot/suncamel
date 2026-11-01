<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261101210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move bike image replacements into products and remove duplicate site image entries.';
    }

    public function up(Schema $schema): void
    {
        $images = $this->connection->fetchAllAssociative("SELECT id, source, image FROM site_image WHERE source LIKE '/images/suncamel/products/%' AND source <> '/images/suncamel/products/collection-hero.png'");
        foreach ($images as $image) {
            if ($image['image'] !== null && $image['image'] !== '') {
                $filename = substr($image['source'], strlen('/images/suncamel/products/'));
                $matched = false;
                foreach (['cover_image', 'hero_image'] as $column) {
                    $ids = $this->connection->fetchFirstColumn("SELECT id FROM product WHERE $column IN (?, ?)", [$filename, $image['source']]);
                    foreach ($ids as $id) {
                        $this->addSql("UPDATE product SET $column = ? WHERE id = ?", [$image['image'], $id]);
                        $matched = true;
                    }
                }
                $ids = $this->connection->fetchFirstColumn('SELECT id FROM product_image WHERE image IN (?, ?)', [$filename, $image['source']]);
                foreach ($ids as $id) {
                    $this->addSql('UPDATE product_image SET image = ? WHERE id = ?', [$image['image'], $id]);
                    $matched = true;
                }
                $this->abortIf(!$matched, 'Un remplacement de vélo ne correspond à aucun produit. Le rattacher à sa fiche produit avant de relancer la migration : ' . $image['source']);
            }
            $this->addSql('DELETE FROM site_image WHERE id = ?', [$image['id']]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Bike image replacements were transferred to their product records.');
    }
}
