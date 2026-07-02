<?php

namespace App\Services\StreamVault;

use Illuminate\Support\Facades\Cache;

class StreamVaultCache
{
    public function get(string $key): mixed
    {
        return Cache::get($key);
    }

    public function set(string $key, mixed $value, int $ttl): void
    {
        Cache::put($key, $value, $ttl);
    }

    public function ttl(string $type): int
    {
        return (int) config("streamvault.cache_ttl.{$type}", 300);
    }
}
