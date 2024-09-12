<?php

namespace RiseTech\ToUpper\Traits;

use RiseTech\ToUpper\ToUpper;

trait hasToUpper
{
    protected $no_upper;

    public function setAttribute($key, $value): void
    {
        parent::setAttribute($key, $value);

        if (is_string($value) && $this->shouldConvertToUpper($key)) {
            $this->attributes[$key] = trim(mb_strtoupper($value));
        }
    }

    private function shouldConvertToUpper($key): bool
    {
        if (!empty($this->only_upper)) {
            return in_array($key, $this->only_upper);
        }

        $no_upper = $this->no_upper ?? [];
        return !$this->isIgnore($key) && !$this->isMorph($key) && !in_array($key, $no_upper);
    }

    protected function isIgnore($key): bool
    {
        return in_array($key, [
            'id', 'password', 'password_confirm', 'remember_token',
            'slug', 'language', 'tenant', 'tenant_branch',
            'model_auth', 'model'
        ]);
    }

    protected function isMorph($key): bool
    {
        foreach ( ['_type', '_id'] as $morph) {
            if (str_contains($key, $morph)) {
                return true;
            }
        }
        return false;
    }
}
