<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261101191449 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add editable replacements for all site images, including logo, banners and galleries.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE site_image (id INT AUTO_INCREMENT NOT NULL, source VARCHAR(255) NOT NULL, label VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_167D45A5F8A7F73 (source), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $images = [
            'https://images.booqablecdn.com/assets/0656d2e7-232b-48fc-80fd-5f4c9e552385/cmii43wlg096j357qipq2ag79(2).SUNCAMEL-a29378b15f79062aa8a0dd0a16d5b176829b8e9afb2ce9f9718235395d76a382.png' => 'Logo — en-tête et pied de page',
            '/images/suncamel/shared/favicon.ico' => 'Icône du navigateur',
            '/images/suncamel/shared/og-image.png' => 'Image de partage sur les réseaux sociaux',
            '/images/suncamel/shared/cta-bike.jpg' => 'Bannière — invitation à réserver',
            '/images/suncamel/home/hero.jpg' => 'Accueil — bannière principale',
            '/images/suncamel/home/collection-all.jpg' => 'Accueil — tous les vélos',
            '/images/suncamel/home/collection-adventure.jpg' => 'Accueil — vélos Adventure',
            '/images/suncamel/home/collection-cruiser.png' => 'Accueil — vélos Cruiser',
            '/images/suncamel/faq/hero.png' => 'FAQ — bannière',
            '/images/suncamel/contact/hero.jpg' => 'Contact — bannière',
            '/images/suncamel/products/collection-hero.png' => 'Catalogue — bannière',
        ];
        foreach ([1 => 'png', 2 => 'jpg', 3 => 'jpg', 4 => 'jpg'] as $number => $extension) {
            $images["/images/suncamel/home/gallery-$number.$extension"] = "Accueil — galerie $number";
        }
        foreach (['cruiser' => ['png', 'jpg'], 'sportracer' => ['jpeg', 'png'], 'adventurer' => ['png', 'jpg']] as $bike => [$cover, $hero]) {
            $images["/images/suncamel/products/$bike-cover.$cover"] = "$bike — image catalogue";
            $images["/images/suncamel/products/$bike-hero.$hero"] = "$bike — bannière";
            $images["/images/suncamel/products/$bike-gallery-1.png"] = "$bike — galerie 1";
            $images["/images/suncamel/products/$bike-gallery-2.jpg"] = "$bike — galerie 2";
        }
        // Include custom visuals from existing published or draft page blocks.
        foreach ($this->connection->fetchAllAssociative('SELECT title, blocks, meta FROM page') as $page) {
            foreach (['blocks', 'meta'] as $column) {
                $content = json_decode($page[$column] ?? '[]', true);
                if (is_array($content)) {
                    array_walk_recursive($content, static function ($value) use (&$images, $page): void {
                        if (is_string($value) && preg_match('~^(?:/images/|https?://).+\.(?:png|jpe?g|webp|gif|svg|avif|ico)(?:\?.*)?$~i', $value) && strlen($value) <= 255) {
                            $images[$value] ??= mb_substr($page['title'] . ' — ' . basename(parse_url($value, PHP_URL_PATH) ?: $value), 0, 255);
                        }
                    });
                }
            }
        }
        foreach ($images as $source => $label) {
            $this->addSql('INSERT INTO site_image (source, label) VALUES (?, ?)', [$source, $label]);
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE site_image');
    }
}
