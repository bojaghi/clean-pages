<?php
/**
 * Bojaghi Clean Pages
 *
 * @package Bojaghi\CleanPages
 */

declare( strict_types=1 );

namespace Bojaghi\CleanPages;

use Bojaghi\Contract\Module;
use Bojaghi\Helper\Helper;

/**
 * Class Clean_Pages
 *
 * Supports output a whole clean HTML blank page.
 */
class Clean_Pages implements Module {
	/**
	 * Priority number of template_redirect action.
	 *
	 * @var int
	 */
	private int $priority;

	/**
	 * @var array
	 */
	private array $redirects;

	/**
	 * Automatically exits after the template_redirect action.
	 *
	 * @var bool
	 */
	private bool $exit;

	/**
	 * Show admin bar or not.
	 *
	 * @var bool
	 */
	private bool $show_admin_bar;

	/**
	 * Constructor
	 *
	 * @param array|string $config An array or path to setup.
	 */
	public function __construct( array|string $config = '' ) {
		[ $assoc, $indexed ] = Helper::separate_array( Helper::load_config( $config ) );

		$this->setup_redirects( $indexed );
		$this->setup_properties( $assoc );

		add_action( 'template_redirect', array( $this, 'template_redirect' ), $this->priority );
	}

	/**
	 * Setup template redirect callback instruction
	 *
	 * @param array $redirects Output instruction, an indexed array.
	 *
	 * @return void
	 */
	private function setup_redirects( array $redirects ): void {
		/**
		 * Default $redirects array.
		 *
		 * @var string          $name           Required, it should be a unique string.
		 * @var callable        $condition      Required, it should be a callable.
		 * @var callable        $template       Optional, callable.
		 * @var callable        $before         Optional, callable.
		 * @var callable        $after          Optional, callable.
		 * @var callable        $body           Optional, callable.
		 * @var bool            $login_required Optional, boolean.
		 * @var string|callable $login_url      Optional, string|callable.
		 */
		$default = array(
			'name'           => '',
			'condition'      => null,
			'template'       => array( $this, 'callback_template' ),
			'before'         => null,
			'after'          => null,
			'body'           => null,
			'login_required' => false,
			'login_url'      => '',
		);

		$this->redirects = array();

		foreach ( $redirects as $item ) {
			$item = wp_parse_args( $item, $default );
			if ( $item['name'] ) {
				$this->redirects[] = $item;
			}
		}
	}

	/**
	 * Setup template_redirect action properties, and so on.
	 *
	 * @param array $items Setup associative array.
	 *
	 * @return void
	 */
	private function setup_properties( array $items ): void {
		$config = wp_parse_args(
			$items,
			array(
				'exit'           => true,
				'priority'       => 9999,
				'show_admin_bar' => false,
			),
		);

		$this->priority       = absint( $config['priority'] );
		$this->show_admin_bar = boolval( $config['show_admin_bar'] );
		$this->exit           = boolval( $config['exit'] );
	}

	/**
	 * 'template_redirect' callback dispatcher.
	 *
	 * @return void
	 */
	public function template_redirect(): void {
		foreach ( $this->redirects as $item ) {
			$name      = $item['name'];
			$condition = $item['condition'];

			if ( ! $name || ! is_callable( $condition ) || ! $condition( $name ) ) {
				continue;
			}

			$template = $item['template'];
			$before   = $item['before'];
			$after    = $item['after'];
			$body     = $item['body'];

			$login_required = (bool) ( is_callable( $item['login_required'] ) ?
				call_user_func( $item['login_required'], $name ) :
				$item['login_required'] );

			$login_url = $item['login_url'];

			if ( $login_required && ! is_user_logged_in() ) {
				$redirect_url = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
				if ( is_callable( $login_url ) ) {
					$login_url = $login_url( $redirect_url );
				} elseif ( is_string( $login_url ) && ! empty( $login_url ) ) {
					$login_url = add_query_arg( 'redirect_to', $redirect_url, $login_url );
				} else {
					$login_url = wp_login_url( $redirect_url );
				}
				wp_safe_redirect( $login_url );
				exit;
			}

			if ( is_callable( $before ) ) {
				$before( $name );
			}

			$this->remove_admin_bar_menus();

			if ( is_callable( $template ) ) {
				$template( $name, $body );
			} elseif ( is_string( $template ) && file_exists( $template ) && is_file( $template ) && is_readable( $template ) ) {
				( function () use ( $template, $name, $body ) {
					include $template;
				} )();
			}

			if ( is_callable( $after ) ) {
				$after( $name );
			}

			if ( $this->exit ) {
				exit;
			}
		}
	}

	/**
	 * Default blank template
	 *
	 * @param string     $name Name of redirect instruction.
	 * @param mixed|null $body Callback of body part.
	 *
	 * @return void
	 */
	public function callback_template( string $name, mixed $body = null ): void {
		if ( ! $this->show_admin_bar ) {
			wp_deregister_script( 'admin-bar' );
			wp_deregister_style( 'admin-bar' );
		}
		remove_action( 'wp_print_styles', 'print_emoji_styles' ); // Remove emoji styles.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// @formatter:off
		?>
<!DOCTYPE html>
<!--suppress HtmlRequiredLangAttribute -->
<html <?php language_attributes(); ?>>
<!--suppress HtmlRequiredTitleElement -->
<head>
		<?php do_action( 'bojaghi_clean_pages_head_begin', $name ); ?>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
		<?php
		/**
		 * Filter viewport attribute
		 *
		 * @var string $viewport Viewport attribute value.
		 * @var string $name     Name of the template.
		 */
		$viewport = apply_filters( 'bojaghi_clean_pages_head_meta_viewport', 'width=device-width, initial-scale=1', $name );
		?>
	<meta name="viewport" content="<?php echo esc_attr( $viewport ); ?>">
		<?php
		wp_print_head_scripts();
		wp_print_styles();
		do_action( 'bojaghi_clean_pages_head_end', $name );
		?>
		<?php if ( $this->show_admin_bar ) : ?>
		<!-- margin for admin bar -->
		<style>body{margin-top:32px;} @media screen and (max-width:782px){body{margin-top:46px;}}</style>
	<?php endif; ?>
</head>
		<?php
		$body_class = $this->show_admin_bar ? ' wp-admin-bar' : '';
		$body_class = $body_class . ( is_user_logged_in() ? ' logged-in' : '' );
		$body_class = trim( $body_class );
		/**
		 * Filter body class
		 *
		 * @var string $body_class Body class to filter.
		 * @var string $name       Redirecto instruction name.
		 */
		$body_class = apply_filters( 'bojaghi_clean_pages_body_class', $body_class, $name );
		?>
	<body class="<?php echo esc_attr( $body_class ); ?>">
			<?php
			/**
			 * Action before body begins
			 *
			 * @var string $name Redirecto instruction name.
			 */
			do_action( 'bojaghi_clean_pages_body_begin', $name );
			is_callable( $body ) && $body( $name );
			if ( $this->show_admin_bar ) {
				wp_admin_bar_render();
			}
			wp_print_footer_scripts();
			/**
			 * Action after body ends
			 *
			 * @var string $name Redirecto instruction name.
			 */
			do_action( 'bojaghi_clean_pages_body_end', $name );
			?>
	</body>
</html>
		<?php
		// @formatter:on
	}

	/**
	 * Remove all admin bar menus, even if admin bar is shown.
	 *
	 * @return void
	 */
	private function remove_admin_bar_menus(): void {
		remove_action( 'admin_bar_menu', 'wp_admin_bar_customize_menu', 40 ); // Customize.
		remove_action( 'admin_bar_menu', 'wp_admin_bar_edit_site_menu', 40 ); // Edit site.
		remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );  // Comments.
		remove_action( 'admin_bar_menu', 'wp_admin_bar_search_menu', 9999 );  // Search.
	}
}
