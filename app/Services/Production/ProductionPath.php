<?php

declare(strict_types=1);

namespace App\Services\Production;

use App\Models\ProductionPhase;
use Illuminate\Support\Collection;

/**
 * The path a batch follows (INV-5, ADR-0023): In cutting, then the workshop's own phases in order; Put in stock ends it and is not a
 * step. A step is named by its ref: 'cutting' for In cutting, a phase's id for a phase.
 */
final readonly class ProductionPath
{
    public const CUTTING = 'cutting';

    /** @param  list<array{ref: 'cutting'|int, name: string}>  $steps */
    private function __construct(public array $steps) {}

    /** The current workshop's path, read now (under the production lock when the caller holds it). */
    public static function current(): self
    {
        return self::of(ProductionPhase::query()->inOrder()->get());
    }

    /** @param  Collection<int, ProductionPhase>  $phases  in path order */
    public static function of(Collection $phases): self
    {
        return new self([
            ['ref' => self::CUTTING, 'name' => ProductionMessages::IN_CUTTING],
            ...$phases->map(static fn (ProductionPhase $phase): array => ['ref' => $phase->id, 'name' => $phase->name])->values()->all(),
        ]);
    }

    /** The ref of a batch's phase id: null is In cutting. */
    public static function refOf(?int $phaseId): string|int
    {
        return $phaseId ?? self::CUTTING;
    }

    /** The phase id of a ref: In cutting has none. */
    public static function phaseIdOf(string|int $ref): ?int
    {
        return $ref === self::CUTTING ? null : (int) $ref;
    }

    public function has(string|int $ref): bool
    {
        return $this->indexOf($ref) !== null;
    }

    /** @return array{ref: 'cutting'|int, name: string}|null the step after `$ref`; null when it is the last (next is Put in stock) */
    public function next(string|int $ref): ?array
    {
        $index = $this->indexOf($ref);

        return $index === null ? null : ($this->steps[$index + 1] ?? null);
    }

    /** @return array{ref: 'cutting'|int, name: string}|null the step before `$ref`; null for In cutting */
    public function previous(string|int $ref): ?array
    {
        $index = $this->indexOf($ref);

        return $index === null || $index === 0 ? null : ($this->steps[$index - 1] ?? null);
    }

    public function name(string|int $ref): ?string
    {
        $index = $this->indexOf($ref);

        return $index === null ? null : $this->steps[$index]['name'];
    }

    /** Whether `$ref` is the last step, so its forward move is Put in stock. */
    public function isLast(string|int $ref): bool
    {
        return $this->has($ref) && $this->next($ref) === null;
    }

    private function indexOf(string|int $ref): ?int
    {
        foreach ($this->steps as $i => $step) {
            if ($step['ref'] === $ref) {
                return $i;
            }
        }

        return null;
    }
}
