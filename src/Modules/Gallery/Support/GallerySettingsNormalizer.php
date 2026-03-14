<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Gallery\Support;

/**
 * Canonical normalization rules for gallery names, templates, and display overrides.
 */
final class GallerySettingsNormalizer
{
    public static function normalizeName(string $value): string
    {
        return trim(\sanitize_text_field($value));
    }

    public static function normalizeDescription(string $value): string
    {
        return trim(\sanitize_text_field($value));
    }

    public static function normalizeTemplate(string $value, string $default = 'basic-grid'): string
    {
        $normalized = trim(\sanitize_text_field($value));

        return $normalized === '' ? $default : $normalized;
    }

    public static function normalizeOptionalTemplate(?string $value, ?string $fallback = null): ?string
    {
        if ($value === null) {
            return $fallback;
        }

        return self::normalizeTemplate($value, $fallback ?? 'basic-grid');
    }

    public static function normalizeOverrideBool(mixed $value): ?bool
    {
        if ($value === null || $value === '' || $value === 'inherit') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        return (bool) $value;
    }

    public static function normalizeOverrideInt(mixed $value, int $min = 2, int $max = 6): ?int
    {
        if ($value === null || $value === '' || $value === 'inherit') {
            return null;
        }

        $number = (int) $value;

        if ($number < $min) {
            return $min;
        }

        if ($number > $max) {
            return $max;
        }

        return $number;
    }

    public static function normalizeTransitionOverride(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 'inherit') {
            return null;
        }

        $normalized = strtolower(trim((string) $value));
        $allowed = ['none', 'slide', 'fade', 'explode', 'implode'];

        if (in_array($normalized, $allowed, true)) {
            return $normalized;
        }

        return null;
    }

    public static function normalizeVisibilityBool(mixed $value, bool $default = true): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        return $default;
    }
}
