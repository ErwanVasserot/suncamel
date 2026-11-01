<?php

namespace App\Tests\Content;

use App\Content\RaglanContent;
use PHPUnit\Framework\TestCase;

final class RaglanContentTest extends TestCase
{
    public function testAllDestinationsHaveUniqueAnchorsAndAlignedMapLabels(): void
    {
        $data = RaglanContent::data();
        self::assertCount(14, $data['places']);
        self::assertCount(14, array_unique(array_column($data['places'], 'id')));

        foreach ($data['places'] as $place) {
            self::assertMatchesRegularExpression('/^[a-z]+$/', $place['id']);
            self::assertContains($place['category'], $data['categories']);
            self::assertNotEmpty($place['description']);
            self::assertNotEmpty($place['query']);
            [$left, $top, $width, $height] = $place['box'];
            self::assertGreaterThanOrEqual(0, $left);
            self::assertGreaterThanOrEqual(0, $top);
            self::assertGreaterThan(0, $width);
            self::assertGreaterThan(0, $height);
            self::assertLessThanOrEqual(1000, $left + $width);
            self::assertLessThanOrEqual(582, $top + $height);
        }
    }

    public function testProvisionalLocationsKeepTheirConfirmationNotes(): void
    {
        $places = array_column(RaglanContent::data()['places'], null, 'id');
        foreach (['jurassic', 'reef', 'kite'] as $id) {
            self::assertStringContainsString('confirm', strtolower($places[$id]['address']));
            self::assertStringContainsString('confirm', strtolower($places[$id]['note']));
        }
    }
}
