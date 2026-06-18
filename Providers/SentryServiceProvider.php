<?php

namespace Modules\Sentry\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Sentry\Services\SentryReporter;

class SentryServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'sentry');

        $dsn = SentryReporter::dsnFromEnv();
        if (!$dsn) {
            return; // module inerte tant qu'aucun DSN n'est configuré
        }

        \Sentry\init([
            'dsn'                => $dsn,
            'environment'        => SentryReporter::environment(),
            'release'            => (string) \Config::get('app.version'),
            'traces_sample_rate' => 0.0,
        ]);

        \Log::listen(function ($event) {
            try {
                // Laravel 5.5 : $event est un MessageLogged (level/message/context).
                $level = is_object($event) && isset($event->level) ? $event->level : '';
                if (!SentryReporter::shouldCapture($level)) {
                    return;
                }
                $context = (is_object($event) && isset($event->context) && is_array($event->context)) ? $event->context : [];
                if (!empty($context['exception']) && $context['exception'] instanceof \Throwable) {
                    \Sentry\captureException($context['exception']);
                } else {
                    $message = is_object($event) && isset($event->message) ? (string) $event->message : '';
                    \Sentry\captureMessage($message, \Sentry\Severity::error());
                }
            } catch (\Throwable $e) {
                // Ne jamais casser l'application ni re-logguer (éviterait toute boucle).
            }
        });
    }

    public function register()
    {
    }
}
