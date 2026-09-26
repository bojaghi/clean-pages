<?php

namespace Bojaghi\CleanPages\Tests;

use Bojaghi\CleanPages\Clean_Pages;
use WP_UnitTestCase;

class Clean_Pages_Test extends WP_UnitTestCase {
	public function test_action_is_added(): void {
		$instance = new Clean_Pages( array( 'priority' => 100 ) );

		$this->assertEquals(
			100,
			has_action( 'template_redirect', array( $instance, 'template_redirect' ) ),
		);
	}

	public function test_template_output(): void {
		$before = false;
		$after  = false;
		$body   = false;

		$instance = new Clean_Pages(
			array(
				array(
					'name'      => 'testCleanPages',
					'condition' => '__return_true',
					// Default template callback.
					'before'    => function () use ( &$before ) { $before = true; },
					'after'     => function () use ( &$after ) { $after = true; },
					'body'      => function () use ( &$body ) { $body = true; },
				),
				'exit' => false, // For testing.
			),
		);

		add_action( 'bojaghi_clean_pages_head_begin', function () {
			echo '<!-- bojaghi_clean_pages_head_begin -->' . PHP_EOL;
		} );

		add_action( 'bojaghi_clean_pages_head_begin', function () {
			echo '<!-- bojaghi_clean_pages_head_end -->' . PHP_EOL;
		} );

		add_action( 'bojaghi_clean_pages_body_begin', function () {
			echo '<!-- bojaghi_clean_pages_body_begin -->' . PHP_EOL;
		} );

		add_action( 'bojaghi_clean_pages_body_end', function () {
			echo '<!-- bojaghi_clean_pages_body_end -->' . PHP_EOL;
		} );

		add_filter( 'bojaghi_clean_pages_body_class', function () {
			return 'bojaghi--clean_pages--body--class';
		}, 10, 2 );

		add_filter( 'bojaghi_clean_pages_head_meta_viewport', function () {
			return 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no';
		} );

		ob_start();
		$instance->template_redirect();
		$output = ob_get_clean();

		$this->assertNotEmpty( $output );
		$this->assertIsString( $output );
		$this->assertTrue( $before );
		$this->assertTrue( $after );
		$this->assertTrue( $body );
		$this->assertStringContainsString( '<!-- bojaghi_clean_pages_head_begin -->', $output );
		$this->assertStringContainsString( '<!-- bojaghi_clean_pages_head_end -->', $output );
		$this->assertStringContainsString( '<!-- bojaghi_clean_pages_body_begin -->', $output );
		$this->assertStringContainsString( '<!-- bojaghi_clean_pages_body_end -->', $output );
		$this->assertStringContainsString( 'class="bojaghi--clean_pages--body--class"', $output );
		$this->assertStringContainsString( 'content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"', $output );
	}
}
