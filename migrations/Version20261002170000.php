<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Content\TermsContent;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the editable Terms & Conditions page when it does not exist.';
    }

    public function up(Schema $schema): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $blocks = json_encode([
            ['type' => 'title', 'data' => [
                'title' => 'Terms & Conditions',
                'eyebrow' => 'SunCamel rentals',
                'image' => '/images/suncamel/products/collection-hero.png',
            ]],
            ['type' => 'legal', 'data' => [
                'title' => 'Rental terms',
                'html' => TermsContent::HTML,
                'color_set' => '1',
            ]],
        ], JSON_THROW_ON_ERROR);
        $meta = json_encode([
            'title' => 'Terms & Conditions | SunCamel',
            'description' => 'Read the terms and conditions that apply to SunCamel e-bike rentals, bookings, cancellations and rider responsibilities.',
            'og_image' => '/images/suncamel/shared/og-image.png',
        ], JSON_THROW_ON_ERROR);

        $this->addSql(
            'INSERT INTO page (slug, title, template, meta, blocks, is_published, created_at, updated_at, published_at) SELECT :slug, :title, :template, :meta, :blocks, 1, :created, :updated, :published WHERE NOT EXISTS (SELECT 1 FROM page WHERE slug = :slug)',
            [
                'slug' => 'pages/suncamel-terms-and-conditions',
                'title' => 'Terms & Conditions',
                'template' => 'default',
                'meta' => $meta,
                'blocks' => $blocks,
                'created' => $now,
                'updated' => $now,
                'published' => $now,
            ],
        );
    }

    public function down(Schema $schema): void
    {
        // Keep legal content created or edited by an administrator.
    }
}
