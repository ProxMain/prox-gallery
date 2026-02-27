<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Models;

use Prox\ProxGallery\Contracts\ModelInterface;

/**
 * Gallery domain model.
 */
final class GalleryModel implements ModelInterface
{
    /**
     * @param list<array<string, mixed>> $items
     */
    public function __construct(private array $items = [])
    {
    }

    public function id(): string
    {
        return 'gallery';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function items(): array
    {
        return $this->items;
    }
}
