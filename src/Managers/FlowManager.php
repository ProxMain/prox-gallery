<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Managers;

use Prox\ProxGallery\Contracts\FlowInterface;

/**
 * Boots registered application flows.
 */
final class FlowManager extends AbstractManager
{
    /**
     * @var array<string, FlowInterface>
     */
    private array $flows = [];

    public function id(): string
    {
        return 'flows';
    }

    public function add(FlowInterface $flow): void
    {
        $this->flows[$flow->id()] = $flow;
    }

    protected function register(): void
    {
        foreach ($this->flows as $flow) {
            $flow->boot();
        }
    }
}
