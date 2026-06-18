<?php

namespace Modules\Sentry\Tests;

use Modules\Sentry\Services\SentryReporter;
use PHPUnit\Framework\TestCase;

class SentryReporterTest extends TestCase
{
    public function test_captures_error_and_above(): void
    {
        foreach (['error', 'critical', 'alert', 'emergency'] as $level) {
            $this->assertTrue(SentryReporter::shouldCapture($level), $level);
        }
        $this->assertTrue(SentryReporter::shouldCapture('ERROR'), 'case-insensitive');
    }

    public function test_ignores_below_error(): void
    {
        foreach (['debug', 'info', 'notice', 'warning', '', 'unknown'] as $level) {
            $this->assertFalse(SentryReporter::shouldCapture($level), $level);
        }
    }

    public function test_read_env_parses_a_dotenv_line(): void
    {
        $file = sys_get_temp_dir() . '/sentry_test_' . getmypid() . '.env';
        file_put_contents($file, "FOO=bar\nSENTRY_DSN=\"https://k@h/6\"\n# c\n");
        $this->assertSame('https://k@h/6', SentryReporter::readEnvFromFile('SENTRY_DSN', $file));
        $this->assertNull(SentryReporter::readEnvFromFile('MISSING', $file));
        unlink($file);
    }
}
