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

        // On NE PAS utiliser \Sentry\init() : il active les intégrations par défaut qui posent des
        // handlers d'erreur PHP globaux, incompatibles avec le Laravel 5.5 de FreeScout sous PHP 8.4
        // (fatal sur Illuminate\Log\Writer). On binde le client manuellement, SANS intégrations
        // par défaut → aucun handler global. On capture uniquement via Log::listen ci-dessous.
        // captureException conserve la stack trace (natif, indépendant des intégrations).
        try {
            $client = \Sentry\ClientBuilder::create([
                'dsn'                  => $dsn,
                'environment'          => SentryReporter::environment(),
                'release'              => (string) \Config::get('app.version'),
                'traces_sample_rate'   => 0.0,
                'default_integrations' => false,
            ])->getClient();
            \Sentry\SentrySdk::getCurrentHub()->bindClient($client);
        } catch (\Throwable $e) {
            return; // un échec de setup Sentry ne doit jamais casser l'application
        }

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
