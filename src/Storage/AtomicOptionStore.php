<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Storage;

use RuntimeException;

/** Compare-and-swap on the existing option row; callbacks may be replayed. */
final class AtomicOptionStore
{
    public function read(string $key): array
    {
        return $this->decode($this->raw($key));
    }

    /**
     * The callback changes its array argument by reference and returns the operation result.
     * It must have no external side effects: a competing writer causes a fresh read and replay.
     *
     * @template T
     * @param callable(array):T $change
     * @return T
     */
    public function mutate(string $key, callable $change): mixed
    {
        global $wpdb;

        for ($attempt = 0; $attempt < 24; $attempt++) {
            $raw = $this->raw($key);
            $value = $this->decode($raw);
            $result = $change($value);
            $next = \maybe_serialize($value);

            if ($next === $raw) {
                return $result;
            }

            // Binary comparison matters: the options table usually has a case-insensitive collation.
            $sql = $raw === null
                ? $wpdb->prepare(
                    "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
                    $key, $next
                )
                : $wpdb->prepare(
                    "UPDATE {$wpdb->options} SET option_value = %s, autoload = 'no' WHERE option_name = %s AND BINARY option_value = BINARY %s",
                    $next, $key, $raw
                );
            $affected = $wpdb->query($sql);

            if ($affected === false) {
                throw new RuntimeException('Unable to save gallery storage.');
            }
            if ($affected === 1) {
                // All reads here bypass the option cache, including persistent caches. Invalidate
                // core caches as well for integrations that use get_option() to inspect these rows.
                \wp_cache_delete($key, 'options');
                \wp_cache_delete('alloptions', 'options');
                \wp_cache_delete('notoptions', 'options');
                return $result;
            }

            \usleep(\random_int(1000, 5000));
        }

        throw new StorageBusyException('Gallery storage is busy. Please retry.');
    }

    private function raw(string $key): ?string
    {
        global $wpdb;
        $raw = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $key));
        if ($wpdb->last_error !== '') {
            throw new RuntimeException('Unable to read gallery storage.');
        }
        return is_string($raw) ? $raw : null;
    }

    private function decode(?string $raw): array
    {
        $value = $raw === null ? [] : \maybe_unserialize($raw);
        return is_array($value) ? $value : [];
    }
}
