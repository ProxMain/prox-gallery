<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\OpenAi;

use Prox\ProxGallery\Contracts\ModuleInterface;

/**
 * OpenAI module boundary.
 */
final class OpenAiModule implements ModuleInterface
{
    public const CAPABILITY_USE = 'prox_gallery_use_openai';

    public function id(): string
    {
        return 'openai';
    }

    public function boot(): void
    {
        $this->registerDefaultCapabilities();

        /**
         * Fires after the OpenAI module boots.
         */
        \do_action('prox_gallery/module/openai/booted');
    }

    private function registerDefaultCapabilities(): void
    {
        // Initialise once so an administrator's later revocation remains effective.
        if ((int) \get_option('prox_gallery_openai_capabilities_version', 0) >= 1) {
            return;
        }

        if (! function_exists('get_role')) {
            return;
        }

        $roles = ['administrator', 'editor', 'author'];

        foreach ($roles as $roleName) {
            $role = \get_role($roleName);

            if (! $role instanceof \WP_Role) {
                continue;
            }

            if (! $role->has_cap(self::CAPABILITY_USE)) {
                $role->add_cap(self::CAPABILITY_USE);
            }
        }

        \update_option('prox_gallery_openai_capabilities_version', 1, false);
    }
}
