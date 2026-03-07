<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Controllers\Admin;

/**
 * Builds the base admin bootstrap payload for the React app.
 */
final class AdminConfigProvider
{
    /**
     * @return array{
     *     screen:string,
     *     rest_nonce:string,
     *     ajax_url:string
     * }
     */
    public function payload(string $screenHookSuffix): array
    {
        $payload = [
            'screen' => $screenHookSuffix,
            'rest_nonce' => (string) \wp_create_nonce('wp_rest'),
            'ajax_url' => (string) \admin_url('admin-ajax.php'),
        ];

        /** @var mixed $filtered */
        $filtered = \apply_filters('prox_gallery/admin/config_payload', $payload);

        return is_array($filtered) ? $filtered : $payload;
    }
}
