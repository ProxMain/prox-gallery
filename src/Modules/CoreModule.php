<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules;

use Prox\ProxGallery\Contracts\ModuleInterface;

/**
 * Core application module.
 *
 * Acts as the root module responsible for:
 * - registering core services
 * - wiring foundational controllers
 * - preparing the system for feature modules
 *
 * This module proves the module lifecycle and boot flow.
 */
final class CoreModule implements ModuleInterface
{
    public function id(): string
    {
        return 'core';
    }

    public function boot(): void
    {
        /**
         * Core boot logic will live here.
         *
         * At this stage this intentionally remains minimal.
         * The purpose is to validate the module orchestration flow.
         */
	    do_action('prox_gallery/module/core/booted');
    }
}