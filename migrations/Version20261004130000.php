<?php
declare(strict_types=1);
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert existing rental times from Auckland wall time to UTC storage.';
    }

    public function up(Schema $schema): void
    {
        $this->convertBookingItemTimes('Pacific/Auckland', 'UTC');
    }

    public function down(Schema $schema): void
    {
        $this->convertBookingItemTimes('UTC', 'Pacific/Auckland');
    }

    private function convertBookingItemTimes(string $sourceTimezone, string $targetTimezone): void
    {
        $source = new \DateTimeZone($sourceTimezone);
        $target = new \DateTimeZone($targetTimezone);
        $rows = $this->connection->fetchAllAssociative('SELECT id, pickup_at, return_at FROM booking_item');

        foreach ($rows as $row) {
            $pickup = (new \DateTimeImmutable((string) $row['pickup_at'], $source))->setTimezone($target);
            $return = (new \DateTimeImmutable((string) $row['return_at'], $source))->setTimezone($target);

            $this->connection->executeStatement(
                'UPDATE booking_item SET pickup_at = ?, return_at = ? WHERE id = ?',
                [$pickup->format('Y-m-d H:i:s'), $return->format('Y-m-d H:i:s'), $row['id']],
            );
        }
    }
}
