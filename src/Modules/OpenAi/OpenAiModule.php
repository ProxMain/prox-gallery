<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\OpenAi;

use Prox\ProxGallery\Contracts\ModuleInterface;

/**
 * OpenAI module boundary.
 */
final class OpenAiModule implements ModuleInterface
{
    public function id(): string
    {
        return 'openai';
    }

    public function boot(): void
    {
        /**
         * Fires after the OpenAI module boots.
         */
        \do_action('prox_gallery/module/openai/booted');
    }
}
