<?php
declare(strict_types=1);
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002180000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add configurable rental closure periods.'; }
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE rental_closure (id INT AUTO_INCREMENT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, message VARCHAR(255) DEFAULT 'Rental is closed' NOT NULL, INDEX rental_closure_dates_idx (start_date, end_date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4");
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE rental_closure'); }
}
