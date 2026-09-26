<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add application users with user and admin roles.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql(<<<'SQL'
INSERT INTO app_user (email, roles, password) VALUES
('admin@suncamel.local', JSON_ARRAY('ROLE_ADMIN'), '$2y$12$AHoLLnMqpcQjlX6B0BO.NOa4TNB7SOrH5UwlALftwAmTFEo6lRzgC'),
('user@suncamel.local', JSON_ARRAY('ROLE_USER'), '$2y$12$AHoLLnMqpcQjlX6B0BO.NOa4TNB7SOrH5UwlALftwAmTFEo6lRzgC')
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_user');
    }
}
