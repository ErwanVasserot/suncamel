<?php
declare(strict_types=1);
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002180100 extends AbstractMigration
{
    public function getDescription(): string { return 'Remove the redundant rental closure date index.'; }
    public function up(Schema $schema): void { $this->addSql('DROP INDEX rental_closure_dates_idx ON rental_closure'); }
    public function down(Schema $schema): void { $this->addSql('CREATE INDEX rental_closure_dates_idx ON rental_closure (start_date, end_date)'); }
}
