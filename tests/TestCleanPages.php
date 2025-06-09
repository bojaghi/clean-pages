<?php

namespace Bojaghi\CleanPages\Tests;

use Bojaghi\CleanPages\CleanPages;
use WP_UnitTestCase;

class TestCleanPages extends WP_UnitTestCase
{
    public function test_action_is_added(): void
    {
        $instance = new CleanPages(['priority' => 100]);

        $this->assertEquals(
            100,
            has_action('template_redirect', [$instance, 'templateRedirect']),
        );
    }

    public function test_template_output(): void
    {
        $before = false;
        $after  = false;
        $body   = false;

        $instance = new CleanPages(
            [
                [
                    'name'      => 'testCleanPages',
                    'condition' => '__return_true',
                    // Default template callback.
                    'before'    => function () use (&$before) { $before = true; },
                    'after'     => function () use (&$after) { $after = true; },
                    'body'      => function () use (&$body) { $body = true; },
                ],
                'exit' => false, // For testing.
            ],
        );

        add_action('bojaghi/clean-pages/head/begin', function () {
            echo '<!-- bojaghi/clean-pages/head/begin -->' . PHP_EOL;
        });

        add_action('bojaghi/clean-pages/head/begin', function () {
            echo '<!-- bojaghi/clean-pages/head/end -->' . PHP_EOL;
        });

        add_action('bojaghi/clean-pages/body/begin', function () {
            echo '<!-- bojaghi/clean-pages/body/begin -->' . PHP_EOL;
        });

        add_action('bojaghi/clean-pages/body/end', function () {
            echo '<!-- bojaghi/clean-pages/body/end -->' . PHP_EOL;
        });

        add_filter('bojaghi/clean-pages/body/class', function () {
            return 'bojaghi--clean_pages--body--class';
        }, 10, 2);

        add_filter('bojaghi/clean-pages/head/meta/viewport', function () {
            return 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no';
        });

        ob_start();
        $instance->templateRedirect();
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertIsString($output);
        $this->assertTrue($before);
        $this->assertTrue($after);
        $this->assertTrue($body);
        $this->assertStringContainsString('<!-- bojaghi/clean-pages/head/begin -->', $output);
        $this->assertStringContainsString('<!-- bojaghi/clean-pages/head/end -->', $output);
        $this->assertStringContainsString('<!-- bojaghi/clean-pages/body/begin -->', $output);
        $this->assertStringContainsString('<!-- bojaghi/clean-pages/body/end -->', $output);
        $this->assertStringContainsString('class="bojaghi--clean_pages--body--class"', $output);
        $this->assertStringContainsString('content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"', $output);
    }
}
