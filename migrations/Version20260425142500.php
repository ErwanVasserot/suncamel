<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425142500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add administrable products, product images, and FAQ items.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(190) NOT NULL, title VARCHAR(255) NOT NULL, tagline VARCHAR(255) NOT NULL, summary LONGTEXT NOT NULL, price VARCHAR(80) NOT NULL, duration VARCHAR(80) NOT NULL, collection_slug VARCHAR(190) DEFAULT NULL, cover_image VARCHAR(255) DEFAULT NULL, hero_image VARCHAR(255) DEFAULT NULL, highlights JSON DEFAULT NULL, specs JSON DEFAULT NULL, position INT DEFAULT 0 NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_D34A04AD989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_image (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, image VARCHAR(255) DEFAULT NULL, alt VARCHAR(255) DEFAULT NULL, position INT DEFAULT 0 NOT NULL, INDEX IDX_64617F034584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE faq_item (id INT AUTO_INCREMENT NOT NULL, category VARCHAR(120) NOT NULL, question VARCHAR(255) NOT NULL, answer_html LONGTEXT NOT NULL, position INT DEFAULT 0 NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE product_image ADD CONSTRAINT FK_64617F034584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');

        $this->addSql(<<<'SQL'
INSERT INTO product (slug, title, tagline, summary, price, duration, collection_slug, cover_image, hero_image, highlights, specs, position, is_active) VALUES
('cruiser-e-bike', 'Cruiser E-Bike', 'Easy-going comfort for beachfront rides and relaxed cruising.', 'A smooth, upright ride built for comfort. Ideal for beach runs, cafes, and sunset loops.', '$79.00', '4 hours', 'cruiser-e-bikes', 'cruiser-cover.png', 'cruiser-hero.jpg', JSON_ARRAY('Step-through frame for easy on/off', 'Comfort saddle and upright posture', 'Ideal for flat and coastal routes'), JSON_ARRAY('Range up to 60 km', 'Assisted top speed 32 km/h', 'Hydraulic disc brakes', '7-speed drivetrain'), 10, 1),
('sportracer', 'Sportracer', 'Lightweight speed with extra torque for longer loops.', 'A sport-focused e-bike designed for longer rides and a more dynamic feel.', '$79.00', '4 hours', 'adventure-bikes', 'sportracer-cover.jpeg', 'sportracer-hero.png', JSON_ARRAY('Responsive geometry for longer rides', 'Extra torque for rolling hills', 'Balanced for comfort and speed'), JSON_ARRAY('Range up to 80 km', 'Assisted top speed 32 km/h', 'Hydraulic disc brakes', 'Integrated lights'), 20, 1),
('adventurer-e-bike', 'Adventurer E-bike', 'Go further off the beaten track with extra power and grip.', 'Built for adventure with wider tires and extra stability on mixed terrain.', '$79.00', '4 hours', 'adventure-bikes', 'adventurer-cover.png', 'adventurer-hero.jpg', JSON_ARRAY('Wider tires for gravel and trails', 'Strong motor for climbs', 'Stable and confidence-inspiring'), JSON_ARRAY('Range up to 70 km', 'Assisted top speed 32 km/h', 'Hydraulic disc brakes', 'Front suspension'), 30, 1)
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO product_image (product_id, image, alt, position)
SELECT id, 'cruiser-gallery-1.png', 'Cruiser E-Bike', 10 FROM product WHERE slug = 'cruiser-e-bike'
UNION ALL SELECT id, 'cruiser-gallery-2.jpg', 'Cruiser E-Bike', 20 FROM product WHERE slug = 'cruiser-e-bike'
UNION ALL SELECT id, 'sportracer-gallery-1.png', 'Sportracer', 10 FROM product WHERE slug = 'sportracer'
UNION ALL SELECT id, 'sportracer-gallery-2.jpg', 'Sportracer', 20 FROM product WHERE slug = 'sportracer'
UNION ALL SELECT id, 'adventurer-gallery-1.png', 'Adventurer E-bike', 10 FROM product WHERE slug = 'adventurer-e-bike'
UNION ALL SELECT id, 'adventurer-gallery-2.jpg', 'Adventurer E-bike', 20 FROM product WHERE slug = 'adventurer-e-bike'
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO faq_item (category, question, answer_html, position, is_active) VALUES
('General Questions', 'What types of e-bikes do you have?', '<p>Our e-bike range includes the Cruiser, the Sportracer, and the Adventurer, giving you options suited to different riding styles and power needs.</p>', 10, 1),
('General Questions', 'Do I need prior experience to ride an e-bike?', '<p>No, our e-bikes are easy to use and suitable for beginners.</p>', 20, 1),
('General Questions', 'How old do I need to be to rent an e-bike?', '<p>You must be at least 18 years old.</p>', 30, 1),
('Booking and payment', 'How do I make a booking?', '<p>Simply <a href="/collections/all">click here</a> to make a booking online, or call us on <a href="tel:0275645263">027 564 5263</a>.</p>', 10, 1),
('Pick up and return', 'What are your opening hours?', '<p>10am-4pm 7 days a week.</p>', 10, 1)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_image DROP FOREIGN KEY FK_64617F034584665A');
        $this->addSql('DROP TABLE product_image');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE faq_item');
    }
}
