<?php

declare(strict_types=1);

namespace App\Services\Reference;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * The version of the shared `reference` once-prop (ADR-0008, ADR-0009). It is a ULID in the cache: the reference-data
 * Actions replace it after every write, so clients that hold the old key fetch the lists again on their next visit. A
 * cache miss (a flushed cache, a new installation) creates a new one, which costs each client one refetch.
 */
final class ReferenceVersion
{
    public const KEY = 'invory.reference.version';

    public function current(): string
    {
        return Cache::rememberForever(self::KEY, fn (): string => (string) Str::ulid());
    }

    /** Called by every reference-data Action after its write. */
    public function bump(): string
    {
        $version = (string) Str::ulid();

        Cache::forever(self::KEY, $version);

        return $version;
    }

    /** The key the once-prop is registered under, so the client can tell it already holds the current lists. */
    public function propKey(): string
    {
        return 'reference:'.$this->current();
    }
}
