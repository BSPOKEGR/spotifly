<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\UserRegisterEvent;
use App\Facades\CommandHelperFacade;
use App\Libraries\CommandHelper;
use App\Listeners\UserRegisterListener;
use App\Services\FarmCalendarService;
use App\Services\PestDiseaseService;
use App\Services\WeatherService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CommandHelperFacade::class, function () {
            return new CommandHelper();
        });

        $this->app->singleton(FarmCalendarService::class, function () {
            return new FarmCalendarService();
        });

        $this->app->singleton(WeatherService::class, function () {
            return new WeatherService();
        });

        $this->app->singleton(PestDiseaseService::class, function () {
            return new PestDiseaseService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Queue::failing(function (JobFailed $event) {
            /** @var Job $job */
            $job = $event->job;
            $channel = Config::get('logging.queue_error');
            $message = \sprintf('Failed Queue %s Reason: %s', $job->getQueue(), $event->exception->getMessage());

            Log::channel($channel)->critical($message, [$job->getRawBody(), $event->exception]);
            Log::channel('slack')->critical($message);
        });

        RateLimiter::for('api', function (Request $request) {
            /** phpstan-ignore-next-line */
            return Limit::perMinute(1000)->by(optional($request->user())->id ?: $request->ip());
        });

        Event::listen(
            UserRegisterEvent::class,
            UserRegisterListener::class
        );
    }
}
