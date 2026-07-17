<?php

namespace RiseTechApps\ToUpper;

use Illuminate\Support\ServiceProvider;
use RiseTechApps\ToUpper\Console\Commands\ToUpperNormalizeCommand;

class ToUpperServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('to-upper.php'),
            ], 'config');

            $this->commands([
                ToUpperNormalizeCommand::class,
            ]);
        }
    }

    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'to-upper');

        $this->app->singleton(ToUpper::class, fn($app) => new ToUpper($app['config']->get('to-upper', [])));

        $this->app->alias(ToUpper::class, 'to-upper');

        $this->registerQueryBuilderMacro();
    }

    private function registerQueryBuilderMacro(): void
    {
        \Illuminate\Database\Query\Builder::macro('toupper', function (array $columns, ?string $encoding = null) {
            // O parâmetro $encoding é reservado para uso futuro.
            // UPPER() do SQL opera no encoding do banco de dados, não aceita encoding PHP.
            $updates = [];

            foreach ($columns as $column) {
                $updates[$column] = \Illuminate\Support\Facades\DB::raw("UPPER({$column})");
            }

            return $this->update($updates);
        });

        \Illuminate\Database\Eloquent\Builder::macro('toupper', fn(array $columns, ?string $encoding = null) => $this->toBase()->toupper($columns, $encoding));
    }
}
