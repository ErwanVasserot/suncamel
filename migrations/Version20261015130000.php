<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add administrable promotion codes and persist booking discounts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE promotion (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, discount_percent INT NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_C11D7DD377153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE booking ADD subtotal_amount INT NOT NULL DEFAULT 0, ADD discount_amount INT NOT NULL DEFAULT 0, ADD promotion_code VARCHAR(50) DEFAULT NULL, ADD promotion_percent INT DEFAULT NULL');
        $this->addSql('UPDATE booking SET subtotal_amount = total_amount');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE promotion');
        $this->addSql('ALTER TABLE booking DROP subtotal_amount, DROP discount_amount, DROP promotion_code, DROP promotion_percent');
    }
}
