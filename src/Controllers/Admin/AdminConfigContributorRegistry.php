<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Controllers\Admin;

use Prox\ProxGallery\Contracts\AdminConfigContributorInterface;

/**
 * Registers admin config contributors behind a single payload filter.
 */
final class AdminConfigContributorRegistry
{
    /**
     * @var array<int, AdminConfigContributorInterface>
     */
    private array $contributors = [];
    private bool $filterRegistered = false;

    public function addContributor(AdminConfigContributorInterface $contributor): void
    {
        $this->contributors[\spl_object_id($contributor)] = $contributor;

        if ($this->filterRegistered) {
            return;
        }

        \add_filter('prox_gallery/admin/config_payload', [$this, 'applyContributors']);
        $this->filterRegistered = true;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    public function applyContributors(array $config): array
    {
        $current = $config;

        foreach ($this->contributors as $contributor) {
            $extended = $contributor->extendAdminConfig($current);

            if (is_array($extended)) {
                $current = $extended;
            }
        }

        return $current;
    }
}
