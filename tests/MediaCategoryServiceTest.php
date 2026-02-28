<?php

declare(strict_types=1);

use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaCategoryService;

final class MediaCategoryServiceTest extends WP_UnitTestCase
{
    private MediaCategoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MediaCategoryService();
        $this->service->registerTaxonomy();
    }

    public function test_it_registers_media_category_taxonomy(): void
    {
        self::assertTrue(\taxonomy_exists(MediaCategoryService::TAXONOMY));
    }

    public function test_it_suggests_existing_categories_by_query(): void
    {
        \wp_insert_term('Nature', MediaCategoryService::TAXONOMY);
        \wp_insert_term('City', MediaCategoryService::TAXONOMY);

        $items = $this->service->suggest('nat', 10);

        self::assertNotEmpty($items);
        self::assertSame('Nature', $items[0]['name']);
    }

    public function test_it_assigns_categories_to_attachment_and_lists_them(): void
    {
        $attachmentId = $this->createAttachment('image/jpeg', 'Tagged Photo');

        $assigned = $this->service->assignToAttachment(
            $attachmentId,
            ['Nature', ' Sunset ', 'Nature']
        );

        self::assertCount(2, $assigned);

        $names = array_map(static fn (array $term): string => $term['name'], $assigned);
        sort($names);

        self::assertSame(['Nature', 'Sunset'], $names);

        $listed = $this->service->listForAttachment($attachmentId);
        self::assertCount(2, $listed);
    }

    private function createAttachment(string $mimeType, string $title): int
    {
        $attachmentId = \wp_insert_attachment(
            [
                'post_title' => $title,
                'post_mime_type' => $mimeType,
                'post_type' => 'attachment',
                'post_status' => 'inherit',
            ],
            ''
        );

        self::assertIsInt($attachmentId);
        self::assertGreaterThan(0, $attachmentId);

        return $attachmentId;
    }
}
