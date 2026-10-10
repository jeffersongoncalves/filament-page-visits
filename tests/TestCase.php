<?php

namespace JeffersonGoncalves\Filament\PageVisits\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use JeffersonGoncalves\Filament\PageVisits\FilamentPageVisitsServiceProvider;
use JeffersonGoncalves\Filament\PageVisits\Tests\Fixtures\TestPanelProvider;
use JeffersonGoncalves\LaravelPageVisits\LaravelPageVisitsServiceProvider;
use Livewire\LivewireServiceProvider;
use Livewire\Mechanisms\DataStore;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->shareEmptyErrorBag();
    }

    /**
     * Pre-populate the shared error bag so Livewire's SupportValidation hook
     * doesn't crash when running components outside the HTTP `web` middleware
     * stack (where ShareErrorsFromSession would normally do this).
     */
    protected function shareEmptyErrorBag(): void
    {
        $errors = tap(new ViewErrorBag)->put('default', new MessageBag);

        $this->app['session']->put('errors', $errors);
        $this->app['view']->share('errors', $errors);
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            LaravelPageVisitsServiceProvider::class,
            FilamentPageVisitsServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Livewire 4 + Testbench 10 bug: Mechanism::register() calls app()->instance()
        // too early, and the binding is overwritten before the singleton is needed.
        // Pin DataStore as a singleton so it persists across calls within a test.
        $app->singleton(DataStore::class);

        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('session.driver', 'array');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $stubsPath = __DIR__.'/../vendor/jeffersongoncalves/laravel-page-visits/database/migrations';
        $tempPath = sys_get_temp_dir().'/filament-page-visits-migrations-'.getmypid();

        if (! is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        array_map(unlink(...), glob($tempPath.'/*.php'));

        // Migrations run in filename order: prefix create_* stubs so they run
        // before stubs altering those tables (e.g. add_response_time_stats_*).
        foreach (glob($stubsPath.'/*.php.stub') as $stub) {
            $name = basename($stub, '.php.stub');
            $prefix = str_starts_with($name, 'create_') ? '0_' : '1_';

            copy($stub, $tempPath.'/'.$prefix.$name.'.php');
        }

        $this->loadMigrationsFrom($tempPath);
    }
}
