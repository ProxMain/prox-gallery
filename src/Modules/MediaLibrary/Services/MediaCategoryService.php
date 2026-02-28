<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\MediaLibrary\Services;

use InvalidArgumentException;

/**
 * Provides taxonomy-backed category management for media attachments.
 */
final class MediaCategoryService
{
    public const TAXONOMY = 'prox_media_category';
    private const MAX_SUGGESTIONS = 30;

    public function boot(): void
    {
        \add_action('init', [$this, 'registerTaxonomy']);
    }

    public function taxonomy(): string
    {
        return self::TAXONOMY;
    }

    public function registerTaxonomy(): void
    {
        if (\taxonomy_exists(self::TAXONOMY)) {
            return;
        }

        \register_taxonomy(
            self::TAXONOMY,
            'attachment',
            [
                'label' => 'Media Categories',
                'public' => false,
                'hierarchical' => false,
                'show_ui' => false,
                'show_in_rest' => false,
                'show_admin_column' => false,
                'show_tagcloud' => false,
                'rewrite' => false,
                'query_var' => false,
                'capabilities' => [
                    'manage_terms' => 'manage_options',
                    'edit_terms' => 'manage_options',
                    'delete_terms' => 'manage_options',
                    'assign_terms' => 'manage_options',
                ],
            ]
        );
    }

    /**
     * @return list<array{id:int, name:string, slug:string, count:int}>
     */
    public function suggest(string $query = '', int $limit = 10): array
    {
        $this->ensureTaxonomyRegistered();

        $sanitizedLimit = max(1, min(self::MAX_SUGGESTIONS, $limit));

        $terms = \get_terms(
            [
                'taxonomy' => self::TAXONOMY,
                'hide_empty' => false,
                'search' => $query,
                'number' => $sanitizedLimit,
                'orderby' => 'count',
                'order' => 'DESC',
            ]
        );

        if ($terms instanceof \WP_Error || ! is_array($terms)) {
            return [];
        }

        $rows = [];

        foreach ($terms as $term) {
            if (! $term instanceof \WP_Term) {
                continue;
            }

            $rows[] = [
                'id' => (int) $term->term_id,
                'name' => (string) $term->name,
                'slug' => (string) $term->slug,
                'count' => (int) $term->count,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{id:int, name:string, slug:string, count:int}>
     */
    public function listForAttachment(int $attachmentId): array
    {
        $this->ensureTaxonomyRegistered();

        if ($attachmentId <= 0) {
            throw new InvalidArgumentException('Attachment ID is required.');
        }

        $terms = \wp_get_object_terms(
            $attachmentId,
            self::TAXONOMY,
            [
                'orderby' => 'name',
                'order' => 'ASC',
            ]
        );

        if ($terms instanceof \WP_Error || ! is_array($terms)) {
            return [];
        }

        $rows = [];

        foreach ($terms as $term) {
            if (! $term instanceof \WP_Term) {
                continue;
            }

            $rows[] = [
                'id' => (int) $term->term_id,
                'name' => (string) $term->name,
                'slug' => (string) $term->slug,
                'count' => (int) $term->count,
            ];
        }

        return $rows;
    }

    /**
     * @param list<string> $categories
     *
     * @return list<array{id:int, name:string, slug:string, count:int}>
     */
    public function assignToAttachment(int $attachmentId, array $categories): array
    {
        $this->ensureTaxonomyRegistered();

        if ($attachmentId <= 0) {
            throw new InvalidArgumentException('Attachment ID is required.');
        }

        $post = \get_post($attachmentId);

        if (! $post instanceof \WP_Post || $post->post_type !== 'attachment') {
            throw new InvalidArgumentException('Attachment not found.');
        }

        $normalized = $this->normalizeCategoryNames($categories);
        $termIds = [];

        foreach ($normalized as $categoryName) {
            $existing = \term_exists($categoryName, self::TAXONOMY);

            if (is_array($existing) && isset($existing['term_id'])) {
                $termIds[] = (int) $existing['term_id'];
                continue;
            }

            if (is_int($existing) && $existing > 0) {
                $termIds[] = $existing;
                continue;
            }

            $created = \wp_insert_term($categoryName, self::TAXONOMY);

            if ($created instanceof \WP_Error || ! is_array($created) || ! isset($created['term_id'])) {
                continue;
            }

            $termIds[] = (int) $created['term_id'];
        }

        $termIds = array_values(array_unique(array_filter($termIds, static fn (int $id): bool => $id > 0)));
        \wp_set_object_terms($attachmentId, $termIds, self::TAXONOMY, false);

        return $this->listForAttachment($attachmentId);
    }

    /**
     * @param list<string> $categories
     *
     * @return list<string>
     */
    private function normalizeCategoryNames(array $categories): array
    {
        $normalized = [];

        foreach ($categories as $category) {
            $name = trim(\sanitize_text_field((string) $category));

            if ($name === '') {
                continue;
            }

            $normalized[] = $name;
        }

        return array_values(array_unique($normalized));
    }

    private function ensureTaxonomyRegistered(): void
    {
        if (! \taxonomy_exists(self::TAXONOMY)) {
            $this->registerTaxonomy();
        }
    }
}
