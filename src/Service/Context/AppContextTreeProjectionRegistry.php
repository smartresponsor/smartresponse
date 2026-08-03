<?php

declare(strict_types=1);

namespace App\Service\Context;

use App\ServiceInterface\Context\AppContextTreeProjectionProviderInterface;

final class AppContextTreeProjectionRegistry
{
    /** @var array<string, AppContextTreeProjectionProviderInterface> */
    private array $providers = [];

    /** @param iterable<AppContextTreeProjectionProviderInterface> $providers */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            $key = trim($provider->key());
            if ('' === $key) {
                throw new \InvalidArgumentException('A context tree projection provider key cannot be empty.');
            }
            if (isset($this->providers[$key])) {
                throw new \InvalidArgumentException(sprintf('Duplicate context tree projection provider "%s".', $key));
            }

            $this->providers[$key] = $provider;
        }
    }

    public function has(string $key): bool
    {
        return isset($this->providers[trim($key)]);
    }

    public function get(string $key): AppContextTreeProjectionProviderInterface
    {
        $key = trim($key);
        if (!isset($this->providers[$key])) {
            throw new \InvalidArgumentException(sprintf('Unknown context tree projection provider "%s".', $key));
        }

        return $this->providers[$key];
    }
}
