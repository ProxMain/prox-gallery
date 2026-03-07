<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Contracts;

/**
 * Contributes entries to the shared admin bootstrap config payload.
 */
interface AdminConfigContributorInterface
{
    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    public function extendAdminConfig(array $config): array;
}
