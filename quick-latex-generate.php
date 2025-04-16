<?php

/**
 * Plugin Name: QuickLatex Batch Runner
 * Description: Generate images with quick latex plugin and import them directly in posts
 * Author: MilosDj21
 * Version: 1.0
 */

defined('ABSPATH') or die("Cannot access pages directly.");

class QuickLatexBatch
{
    public function __construct()
    {
        // 1) On activation, enforce dependency:
        register_activation_hook(__FILE__, array($this, 'myql_check_quicklatex_dependency'));

        // 2) In case someone deactivates QuickLaTeX later, catch it on admin init:
        add_action('admin_init', array($this, 'myql_admin_check_quicklatex'));

        add_action('plugins_loaded', array($this, 'batch_runner'));

    }

    public function myql_check_quicklatex_dependency()
    {
        if (! function_exists('quicklatex_parser')) {
            deactivate_plugins(plugin_basename(__FILE__));
            // show error and halt activation
            wp_die(
                '“My QuickLaTeX Batch Runner” requires the WP QuickLaTeX plugin to be installed and active. ' .
                'Please install/activate WP QuickLaTeX first, then re‑activate this plugin.',
                'Plugin dependency check',
                [ 'back_link' => true ]
            );
        }
    }

    public function myql_admin_check_quicklatex()
    {
        // only run in admin, and only if our plugin is active
        if (is_admin() && current_user_can('activate_plugins') && function_exists('quicklatex_parser') === false) {
            // deactivate ourselves
            deactivate_plugins(plugin_basename(__FILE__));
            // add a notice
            add_action('admin_notices', function () {
                echo '<div class="error"><p>';
                echo 'My QuickLaTeX Batch Runner has been deactivated because it requires WP QuickLaTeX.';
                echo '</p></div>';
            });
        }
    }

    public function batch_runner()
    {
        // bail if QuickLaTeX isn’t active
        if (defined('WP_CLI') && WP_CLI && function_exists('quicklatex_parser')) {
            require_once __DIR__ . '/quicklatex-batch-command.php';
            WP_CLI::add_command('quicklatex batch', 'QuickLaTeX_Batch_Command');
        }
    }
}

$className = new QuickLatexBatch();
