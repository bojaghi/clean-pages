<?php
declare(strict_types=1);

namespace Bojaghi\CleanPages;

use Bojaghi\Contract\Module;
use Bojaghi\Helper\Helper;

class CleanPages implements Module
{
    private int $priority;

    private array $redirects;

    private bool $exit;

    private bool $showAdminBar;

    public function __construct(array|string $config = '')
    {
        [$assoc, $indexed] = Helper::separateArray(Helper::loadConfig($config));

        $this->setupRedirects($indexed);
        $this->setupProperties($assoc);

        add_action('template_redirect', [$this, 'templateRedirect'], $this->priority);
    }

    private function setupRedirects(array $redirects): void
    {
        $default = [
            'name'      => '',   // Required, it should be a unique string
            'condition' => null, // Required, it should be a callable
            'template'  => [$this, 'callbackTemplate'], // Optional, callable.
            'before'    => null, // Optional, callable.
            'after'     => null, // Optional, callable.
            'body'      => null, // Optional, callable.
        ];

        foreach ($redirects as $item) {
            $item = wp_parse_args($item, $default);
            $name = $item['name'];

            if ($name) {
                $this->redirects[] = $item;
            }
        }
    }

    private function setupProperties(array $items): void
    {
        $config = wp_parse_args(
            $items,
            [
                'exit'           => true,
                'priority'       => 9999,
                'show_admin_bar' => false,
            ],
        );

        $this->priority     = absint($config['priority']);
        $this->showAdminBar = boolval($config['show_admin_bar']);
        $this->exit         = boolval($config['exit']);
    }

    public function templateRedirect(): void
    {
        foreach ($this->redirects as $item) {
            $name      = $item['name'];
            $condition = $item['condition'];
            $template  = $item['template'];
            $before    = $item['before'];
            $after     = $item['after'];
            $body      = $item['body'];

            if (!$condition($name)) {
                continue;
            }

            if (is_callable($item['before'])) {
                $before($name);
            }

            if (is_callable($template)) {
                $template($name, $body);
            } elseif (is_string($template) && file_exists($template) && is_file($template) && is_readable($template)) {
                (function () use ($template, $name, $body) { include $template; })();
            }

            if (is_callable($after)) {
                $after($name);
            }

            if ($this->exit) {
                exit;
            }
        }
    }

    public function callbackTemplate(string $name, mixed $body = null): void
    {
        if (!$this->showAdminBar) {
            wp_deregister_script('admin-bar');
            wp_deregister_style('admin-bar');
        }
        remove_action('admin_bar_menu', 'wp_admin_bar_customize_menu', 40); // Remove admin bar's customize menu.
        remove_action('wp_print_styles', 'print_emoji_styles'); // Remove emoji styles.
        // @formatter:off
        ?>
<!DOCTYPE html>
<!--suppress HtmlRequiredLangAttribute -->
<html <?php language_attributes(); ?>>
<!--suppress HtmlRequiredTitleElement -->
<head>
    <?php do_action('bojaghi/clean-pages/head/begin', $name); ?>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php
    wp_print_head_scripts();
    wp_print_styles();
    if ($this->showAdminBar) { ?><style>body{margin-top:32px;} @media screen and (max-width:782px){body{margin-top:46px;}}</style><?php } ?>
    <?php do_action('bojaghi/clean-pages/head/end', $name); ?>
</head>
<body class="<?php echo esc_attr(apply_filters('bojaghi/clean-pages/body/class', '', $name)); ?>">
    <?php
    do_action('bojaghi/clean-pages/body/begin', $name);
    is_callable($body) && $body();
    if ($this->showAdminBar) {
        // Remove customizer support script.
        add_action('wp_before_admin_bar_render', function () {
            remove_action('wp_before_admin_bar_render', 'wp_customize_support_script');
        }, 5);
        wp_admin_bar_render();
    }
    wp_print_footer_scripts();
    do_action('bojaghi/clean-pages/body/end', $name);
    ?>
</body>
</html>
        <?php
        // @formatter:on
    }
}
