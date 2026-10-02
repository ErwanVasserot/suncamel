<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20261002120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Synchronize the managed product catalogue, stock, images and pricing tiers.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
UPDATE product SET
    summary = 'Experience Raglan in style on a Cruiser E-Bike. Designed with a vintage cruiser look and packed with modern electric power, this bike is perfect for beach rides, hill climbs, and exploring at your own pace. With chunky tyres, smooth acceleration, and an ultra-comfortable ride, it’s ideal for anyone wanting a fun, effortless adventure.\nBook your Cruiser E-bike today and cruise like a local',
    specs = JSON_ARRAY('250 Kw power', 'Comfortable seat', 'Battery lasts up to 60 km', 'Rugged tyres built for gravel, dirt, and urban roads', 'Locked max speed: 30 km/h', 'Number of gears: Shimano 6 speed', 'Max weight: 100kg'),
    position = 1, stock_quantity = 1, half_day_amount = 7900, full_day_amount = 9900
WHERE slug = 'cruiser-e-bike'
SQL);

        $this->addSql(<<<'SQL'
UPDATE product SET
    summary = 'Take your rides to the next level with the Sportracer E-Bike. Built for thrill-seekers and outdoor enthusiasts, this bike is perfect for off-road trails, lifestyle blocks, and adventurous family outings. With a powerful motor, rugged tyres, and a comfortable, durable frame, the Sportracer is designed to handle challenging terrain while keeping every ride smooth and exhilarating.',
    specs = JSON_ARRAY('Powerful motor for climbs and off-road performance', 'Comfortable, ergonomic seat for longer rides', 'Battery lasts up to 125 km (if using mode 1 and depending on terrain and rider weight)', 'Rugged tyres built for gravel, dirt, and urban roads', 'Locked max speed: 32 km/h', 'Max weight: 150kg'),
    position = 2, stock_quantity = 2, half_day_amount = 7900, full_day_amount = 9900
WHERE slug = 'sportracer'
SQL);

        $this->addSql(<<<'SQL'
UPDATE product SET
    summary = 'Take your explorations further with the Adventurer E-Bike. Built for adventure and packed with power, this bike is perfect for longer rides, hilly terrain, and off-the-beaten-path trails. With a 300W motor, fat tyres, and a rugged, comfortable frame, it’s designed to handle more challenging terrain while keeping your ride smooth and enjoyable.',
    specs = JSON_ARRAY('300W motor for uphill power and rough terrain', 'Comfortable, ergonomic seat for longer rides', 'Battery lasts up to 90-100 km (depending on terrain and rider weight)', 'Rugged tyres built for gravel, dirt, and urban roads', 'Number of gears: Shimano 7', 'Factory Locked Max speed: 30 km/h', 'Max weight: 130kg'),
    position = 3, stock_quantity = 1, half_day_amount = 7900, full_day_amount = 9900
WHERE slug = 'adventurer-e-bike'
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO pricing_tier (product_id, minimum_days, daily_amount)
SELECT id, 2, 9450 FROM product WHERE slug = 'cruiser-e-bike'
UNION ALL SELECT id, 7, 7100 FROM product WHERE slug = 'cruiser-e-bike'
UNION ALL SELECT id, 2, 9050 FROM product WHERE slug = 'sportracer'
UNION ALL SELECT id, 7, 7100 FROM product WHERE slug = 'sportracer'
UNION ALL SELECT id, 2, 9050 FROM product WHERE slug = 'adventurer-e-bike'
UNION ALL SELECT id, 7, 7100 FROM product WHERE slug = 'adventurer-e-bike'
ON DUPLICATE KEY UPDATE daily_amount = VALUES(daily_amount)
SQL);

        $this->addSql(<<<'SQL'
UPDATE product_image i
INNER JOIN product p ON p.id = i.product_id
SET i.image = 'cruiser-gallery-2-gallery-1777457263.webp', i.alt = 'Cruiser E-Bike'
WHERE p.slug = 'cruiser-e-bike' AND i.position = 20
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO product_image (product_id, image, alt, position)
SELECT p.id, 'cruiser-gallery-3-gallery-1777461942.webp', 'Cruiser E-Bike', 30
FROM product p
WHERE p.slug = 'cruiser-e-bike'
  AND NOT EXISTS (
      SELECT 1 FROM product_image i WHERE i.product_id = p.id AND i.position = 30
  )
SQL);
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('Catalogue data may have been edited after deployment and cannot be safely restored.');
    }
}
