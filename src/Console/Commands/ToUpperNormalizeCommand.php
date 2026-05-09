<?php

namespace RiseTechApps\ToUpper\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RiseTechApps\ToUpper\ToUpper;
use Symfony\Component\Finder\Finder;

class ToUpperNormalizeCommand extends Command
{
    protected $signature = 'toupper:normalize
                            {model? : Nome do model (ex: Client)}
                            {--columns= : Colunas para normalizar (separadas por vírgula)}
                            {--dry-run : Mostra o que seria feito sem executar}
                            {--chunk=100 : Registros por chunk}';

    protected $description = 'Normaliza para maiúsculas dados existentes nos models';

    public function handle(): int
    {
        $modelName = $this->argument('model');

        if (!$modelName) {
            $modelName = $this->askForModel();
            if (!$modelName) {
                return self::FAILURE;
            }
        }

        $modelClass = $this->resolveModelClass($modelName);

        if (!class_exists($modelClass)) {
            $this->error("Model '{$modelName}' não encontrado.");
            return self::FAILURE;
        }

        if (!in_array(\RiseTechApps\ToUpper\Traits\HasToUpper::class, class_uses_recursive($modelClass))) {
            $this->error("O model '{$modelName}' não usa a trait HasToUpper.");
            return self::FAILURE;
        }

        $columns = $this->getColumns($modelClass);
        if (empty($columns)) {
            $this->warn('Nenhuma coluna encontrada para normalizar.');
            return self::SUCCESS;
        }

        $this->info("Normalizando: {$modelClass}");
        $this->info("Colunas: " . implode(', ', $columns));

        if ($this->option('dry-run')) {
            $this->dryRun($modelClass, $columns);
            return self::SUCCESS;
        }

        return $this->normalize($modelClass, $columns);
    }

    private function askForModel(): ?string
    {
        $models = $this->getModelsWithTrait();

        if (empty($models)) {
            $this->error('Nenhum model com HasToUpper encontrado no app/Models.');
            return null;
        }

        return $this->choice('Qual model deseja normalizar?', $models);
    }

    private function getModelsWithTrait(): array
    {
        $models = [];
        $modelPath = app_path('Models');

        if (!is_dir($modelPath)) {
            return $models;
        }

        $finder = new Finder();
        $finder->files()->in($modelPath)->name('*.php');

        foreach ($finder as $file) {
            $class = 'App\\Models\\' . $file->getBasename('.php');
            if (class_exists($class) && in_array(\RiseTechApps\ToUpper\Traits\HasToUpper::class, class_uses_recursive($class))) {
                $models[] = $file->getBasename('.php');
            }
        }

        return $models;
    }

    private function resolveModelClass(string $name): string
    {
        $prefixes = ['App\\Models\\', 'App\\', ''];

        foreach ($prefixes as $prefix) {
            $class = $prefix . $name;
            if (class_exists($class)) {
                return $class;
            }
        }

        return $name;
    }

    private function getColumns(string $modelClass): array
    {
        $columnsOption = $this->option('columns');

        if ($columnsOption) {
            return array_map('trim', explode(',', $columnsOption));
        }

        /** @var Model $instance */
        $instance = new $modelClass();
        $fillable = $instance->getFillable();

        return array_filter($fillable, fn($col) => is_string($col) && $col !== '');
    }

    private function dryRun(string $modelClass, array $columns): void
    {
        /** @var Model $instance */
        $instance = new $modelClass();
        $total = $instance::count();
        $toUpper = app(ToUpper::class);

        $this->newLine();
        $this->warn("[DRY-RUN] {$total} registros seriam afetados");

        $instance::chunk($this->option('chunk'), function ($items) use ($columns, $toUpper) {
            foreach ($items as $item) {
                $changes = [];
                foreach ($columns as $col) {
                    if (!isset($item->$col)) continue;
                    $original = $item->$col;
                    if (is_string($original) && $original !== '') {
                        $expected = $toUpper->normalize($original);
                        if ($original !== $expected) {
                            $changes[] = "{$col}: '{$original}' → '{$expected}'";
                        }
                    }
                }
                if (!empty($changes)) {
                    $this->info("ID {$item->getKey()}: " . implode(', ', $changes));
                }
            }
        });
    }

    private function normalize(string $modelClass, array $columns): int
    {
        $updated = 0;
        $skipped = 0;
        $chunkSize = (int) $this->option('chunk');
        $toUpper = app(ToUpper::class);

        /** @var Model $instance */
        $instance = new $modelClass();
        $total = $instance::count();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $instance::chunk($chunkSize, function ($items) use ($columns, &$updated, &$skipped, $bar, $toUpper) {
            foreach ($items as $item) {
                $hasChanges = false;

                foreach ($columns as $col) {
                    if (!isset($item->$col)) continue;
                    $original = $item->$col;
                    if (is_string($original) && $original !== '') {
                        $expected = $toUpper->normalize($original);
                        if ($original !== $expected) {
                            $item->$col = $expected;
                            $hasChanges = true;
                        }
                    }
                }

                if ($hasChanges) {
                    $item->timestamps = false;
                    $item->saveQuietly();
                    $updated++;
                } else {
                    $skipped++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Concluído: {$updated} atualizados, {$skipped} ignorados (já em maiúsculas).");

        return self::SUCCESS;
    }
}
