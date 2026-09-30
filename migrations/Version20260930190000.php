<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add half-day, full-day and administrable multi-day pricing tiers.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD half_day_amount INT DEFAULT 7900 NOT NULL, ADD full_day_amount INT DEFAULT 7900 NOT NULL');
        $this->addSql("UPDATE product SET half_day_amount = ROUND(CAST(REPLACE(REPLACE(price, '$', ''), ',', '') AS DECIMAL(10, 2)) * 100), full_day_amount = ROUND(CAST(REPLACE(REPLACE(price, '$', ''), ',', '') AS DECIMAL(10, 2)) * 100)");
        $this->addSql('CREATE TABLE pricing_tier (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, minimum_days INT NOT NULL, daily_amount INT NOT NULL, INDEX IDX_2312951C4584665A (product_id), UNIQUE INDEX uniq_product_minimum_days (product_id, minimum_days), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE pricing_tier ADD CONSTRAINT FK_2312951C4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pricing_tier DROP FOREIGN KEY FK_2312951C4584665A');
        $this->addSql('DROP TABLE pricing_tier');
        $this->addSql('ALTER TABLE product DROP half_day_amount, DROP full_day_amount');
    }
}
