<?php

namespace RiseTechApps\ToUpper\Traits;

use RiseTechApps\ToUpper\ToUpper;

trait HasToUpper
{
    private array $toUpperConfigCache = [];

    public function setAttribute($key, $value)
    {
        if ($this->shouldNormalize($key, $value)) {
            if (method_exists($this, 'beforeToUpper')) {
                $value = $this->beforeToUpper($key, $value);
            }

            $value = $this->getToUpperService()->normalize(
                $value,
                $this->resolveEncoding(),
                $this->shouldTrim()
            );

            if (method_exists($this, 'afterToUpper')) {
                $value = $this->afterToUpper($key, $value);
            }
        }

        return parent::setAttribute($key, $value);
    }

    private function shouldNormalize(string $key, mixed $value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        if ($this->hasCast($key)) {
            $castType = $this->getCasts()[$key] ?? null;
            if (in_array($castType, ['array', 'json', 'object', 'collection', 'encrypted'], true)) {
                return false;
            }
        }

        return $this->shouldConvertToUpper($key);
    }

    private function shouldConvertToUpper($key): bool
    {
        $onlyUpper = $this->resolveOnlyUpperAttributes();
        if (!empty($onlyUpper)) {
            return in_array($key, $onlyUpper, true);
        }

        $noUpper = $this->resolveNoUpperAttributes();

        return !$this->isIgnoredAttribute($key)
            && !$this->isMorphAttribute($key)
            && !in_array($key, $noUpper, true);
    }

    protected function resolveOnlyUpperAttributes(): array
    {
        return $this->mergeConfiguredAttributes('only_upper');
    }

    protected function resolveNoUpperAttributes(): array
    {
        return $this->mergeConfiguredAttributes('no_upper');
    }

    protected function isIgnoredAttribute(string $key): bool
    {
        $ignore = $this->mergeConfiguredAttributes('ignore_attributes', 'ignore_upper');

        return in_array($key, $ignore, true);
    }

    protected function isMorphAttribute(string $key): bool
    {
        $suffixes = $this->mergeConfiguredAttributes('morph_suffixes');

        foreach ($suffixes as $suffix) {
            if ($suffix !== '' && str_ends_with($key, (string) $suffix)) {
                return true;
            }
        }

        return false;
    }

    protected function shouldTrim(): bool
    {
        if (property_exists($this, 'uppercase_trim')) {
            return (bool) $this->uppercase_trim;
        }

        return (bool) $this->getToUpperService()->config('trim', true);
    }

    protected function resolveEncoding(): string
    {
        if (property_exists($this, 'uppercase_encoding') && is_string($this->uppercase_encoding)) {
            return $this->uppercase_encoding;
        }

        return (string) $this->getToUpperService()->config('encoding', mb_internal_encoding());
    }

    protected function mergeConfiguredAttributes(string $configKey, ?string $property = null): array
    {
        $property ??= $configKey;
        $cacheKey = $configKey . '_' . $property;

        if (isset($this->toUpperConfigCache[$cacheKey])) {
            return $this->toUpperConfigCache[$cacheKey];
        }

        $configured = $this->normalizeArray($this->getToUpperService()->config($configKey, []));
        $modelValues = [];

        if (property_exists($this, $property)) {
            $modelValues = $this->normalizeArray($this->{$property});
        }

        $result = array_values(array_unique([...$configured, ...$modelValues]));
        $this->toUpperConfigCache[$cacheKey] = $result;

        return $result;
    }

    protected function normalizeArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, fn ($item) => is_string($item) && $item !== ''));
    }

    protected function getToUpperService(): ToUpper
    {
        return app(ToUpper::class);
    }

    public function scopeWhereUpper($query, string $column, string $value)
    {
        return $query->whereRaw(
            "LOWER({$this->getTable()}.{$column}) = LOWER(?)",
            [$value]
        );
    }

    public function scopeOrWhereUpper($query, string $column, string $value)
    {
        return $query->orWhereRaw(
            "LOWER({$this->getTable()}.{$column}) = LOWER(?)",
            [$value]
        );
    }
}
