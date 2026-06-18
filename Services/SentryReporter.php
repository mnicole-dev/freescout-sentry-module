<?php

namespace Modules\Sentry\Services;

class SentryReporter
{
    const CAPTURE_LEVELS = ['error', 'critical', 'alert', 'emergency'];

    public static function shouldCapture($level): bool
    {
        return in_array(strtolower((string) $level), self::CAPTURE_LEVELS, true);
    }

    /** Robust env read: getenv first, then parse a dotenv file (env() is null at runtime when config is cached). */
    public static function readEnv(string $key): ?string
    {
        $v = getenv($key);
        if ($v !== false && $v !== '') {
            return $v;
        }
        $file = function_exists('base_path') ? base_path('.env') : null;
        return $file ? self::readEnvFromFile($key, $file) : null;
    }

    /** Parse KEY=value from a dotenv file (quotes/whitespace trimmed). Returns null if absent/empty. */
    public static function readEnvFromFile(string $key, string $file): ?string
    {
        if (!is_readable($file)) {
            return null;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
            if (preg_match('/^\s*' . preg_quote($key, '/') . '\s*=\s*(.*)$/', $line, $m)) {
                $val = trim($m[1], " \t\"'");
                return $val === '' ? null : $val;
            }
        }
        return null;
    }

    public static function dsnFromEnv(): ?string
    {
        // SENTRY_DSN = nom écrit par l'image Docker (préfixe FREESCOUT_ retiré).
        // FREESCOUT_SENTRY_DSN = fallback si quelqu'un le pose tel quel (hors image).
        return self::readEnv('SENTRY_DSN') ?: self::readEnv('FREESCOUT_SENTRY_DSN');
    }

    public static function environment(): string
    {
        return self::readEnv('SENTRY_ENVIRONMENT')
            ?: self::readEnv('FREESCOUT_SENTRY_ENVIRONMENT')
            ?: 'production';
    }
}
