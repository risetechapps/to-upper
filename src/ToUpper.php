<?php

namespace RiseTechApps\ToUpper;

use Illuminate\Support\Arr;

class ToUpper
{
    public function __construct(private readonly array $config = [])
    {
    }

    public function normalize(string $value, ?string $encoding = null, ?bool $trim = null): string
    {
        $encoding ??= $this->config('encoding', mb_internal_encoding());
        $trim ??= $this->config('trim', true);

        $value = $this->ensureEncoding($value, $encoding);

        $normalized = mb_strtoupper($value, $encoding);

        return $trim ? trim($normalized) : $normalized;
    }

    private function ensureEncoding(string $value, string $targetEncoding): string
    {
        if (!function_exists('mb_detect_encoding')) {
            return $value;
        }

        // Se a string é ASCII pura, não precisa de conversão (ASCII é compatível com todos os encodings)
        if (mb_check_encoding($value, 'ASCII')) {
            return $value;
        }

        // Usar lista restrita de encodings comuns para evitar falsos positivos com strings curtas
        $encodings = ['UTF-8', 'ISO-8859-1', 'ISO-8859-15', 'Windows-1252'];
        $detected = mb_detect_encoding($value, $encodings, true);

        if ($detected === false || $detected === $targetEncoding) {
            return $value;
        }

        $converted = mb_convert_encoding($value, $targetEncoding, $detected);

        return $converted !== false ? $converted : $value;
    }

    public function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

        return Arr::get($this->config, $key, $default);
    }
}
