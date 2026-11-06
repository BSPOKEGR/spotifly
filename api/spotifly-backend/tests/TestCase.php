<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Config;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var TestHelper;
     */
    protected $helper;

    public static bool $initialized = false;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        if (!self::$initialized) {
            // We utilize the filesystem as shared mutable state to coordinate between processes
            touch('/tmp/test-initialization-lock-file');
            $lockFile = fopen('/tmp/test-initialization-lock-file', 'r');

            // Attempt to get an exclusive lock - first process wins
            if (flock($lockFile, LOCK_EX | LOCK_NB)) {
                // Since we are the single process that has an exclusive lock, we run the initialization
                self::$initialized = true;
            } else {
                // If no exclusive lock is available, block until the first process is done with initialization
                flock($lockFile, LOCK_SH);
            }

            self::$initialized = true;
        }
        parent::setUp();

        $this->withCsrfToken();
        $this->withOrigin();

        $this->helper = new TestHelper();
    }

    protected function withCsrfToken(): void
    {
        $response = $this->get('sanctum/csrf-cookie');

        // Retrieve the XSRF-TOKEN from the response cookies
        $xsrfToken = $response->headers->getCookies()[0]->getValue();

        // Automatically include the X-XSRF-TOKEN header in all requests
        $this->withHeader('X-XSRF-TOKEN', $xsrfToken);
    }

    protected function withOrigin(): void
    {
        // Automatically include the origin header in all requests
        $this->withHeader('origin', 'http://' . Config::get('sanctum.stateful')[0]);
    }
}
