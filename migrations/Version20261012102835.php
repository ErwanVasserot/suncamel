<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261012102835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Synchronize the password reset column and pricing tier constraint names with Doctrine mapping.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user CHANGE password_reset_expires_at password_reset_expires_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE pricing_tier DROP FOREIGN KEY `FK_2312951C4584665A`');
        $this->addSql('DROP INDEX idx_2312951c4584665a ON pricing_tier');
        $this->addSql('CREATE INDEX IDX_F889D344584665A ON pricing_tier (product_id)');
        $this->addSql('ALTER TABLE pricing_tier ADD CONSTRAINT `FK_2312951C4584665A` FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user CHANGE password_reset_expires_at password_reset_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE pricing_tier DROP FOREIGN KEY FK_F889D344584665A');
        $this->addSql('DROP INDEX idx_f889d344584665a ON pricing_tier');
        $this->addSql('CREATE INDEX IDX_2312951C4584665A ON pricing_tier (product_id)');
        $this->addSql('ALTER TABLE pricing_tier ADD CONSTRAINT FK_F889D344584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }
}
