<?php

namespace App\Providers;

use App\Listeners\NotifyUsers;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Management can do everything, in every department, without holding other roles.
        // Business gates inside the services (required ticks, B/L before arrival...) still apply.
        Gate::before(fn (User $user) => $user->isManagement() ? true : null);

        Event::subscribe(NotifyUsers::class);
        Paginator::useBootstrapFive();

        RateLimiter::for('leads', fn (Request $r) => Limit::perMinute(20)->by($r->ip()));
        RateLimiter::for('ai', fn (Request $r) => Limit::perMinute(10)->by($r->user()?->id ?: $r->ip()));

        Password::defaults(fn () => Password::min(10)->letters()->numbers());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
