<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\OpenAi\Services;

use Prox\ProxGallery\Storage\AtomicOptionStore;
use Prox\ProxGallery\Modules\OpenAi\Support\QuotaExceededException;

/** Site and user reservations commit together, before any paid provider request. */
final class OpenAiUsageService
{
    private const OPTION = 'prox_gallery_openai_usage';

    public function __construct(private AtomicOptionStore $store = new AtomicOptionStore())
    {
    }

    public function reserve(int $userId, array $settings): array
    {
        if ($userId <= 0) {
            throw new QuotaExceededException('Sign in before generating an AI story.');
        }

        return $this->store->mutate(self::OPTION, function (array &$raw) use ($userId, $settings): array {
            $today = \gmdate('Y-m-d');
            $usage = $this->forDay($raw, $today);
            $status = $this->summarize($usage, $userId, $settings, $today);
            if ($status['site_remaining'] === 0 || $status['user_remaining'] === 0) {
                throw new QuotaExceededException('AI daily quota reached or generation disabled. Quotas reset at 00:00 UTC.');
            }

            $usage['site']++;
            $usage['users'][(string) $userId] = $status['user_used'] + 1;
            $raw = $usage;
            return $this->summarize($usage, $userId, $settings, $today);
        });
    }

    public function status(int $userId, array $settings): array
    {
        $today = \gmdate('Y-m-d');
        return $this->summarize($this->forDay($this->store->read(self::OPTION), $today), $userId, $settings, $today);
    }

    private function forDay(array $raw, string $today): array
    {
        if (($raw['day'] ?? '') !== $today) {
            return ['day' => $today, 'site' => 0, 'users' => []];
        }
        return [
            'day' => $today,
            'site' => max(0, (int) ($raw['site'] ?? 0)),
            'users' => is_array($raw['users'] ?? null) ? $raw['users'] : [],
        ];
    }

    private function summarize(array $usage, int $userId, array $settings, string $today): array
    {
        $userLimit = max(0, (int) $settings['user_daily_limit']);
        $siteLimit = max(0, (int) $settings['site_daily_limit']);
        $userUsed = max(0, (int) ($usage['users'][(string) $userId] ?? 0));
        return [
            'user_limit' => $userLimit,
            'site_limit' => $siteLimit,
            'user_used' => $userUsed,
            'site_used' => $usage['site'],
            'user_remaining' => max(0, $userLimit - $userUsed),
            'site_remaining' => max(0, $siteLimit - $usage['site']),
            'resets_at' => \gmdate('c', (int) \strtotime($today . ' +1 day UTC')),
        ];
    }
}
