<?php

/**
 * Plugin Name: QuickLatex Lazy Loader
 * Description: Add lazyload to quicklatex images before rendering
 * Author: MilosDj21
 * Version: 1.0.1
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

        // 3) Add filters for content to update post before rendering, quicklatex_parser priority is 7, so this is ran after that
        add_filter('the_content', array($this, 'custom_parser'), 8);
        add_filter('comment_text', array($this, 'custom_parser'), 8);
        add_filter('the_title', array($this, 'custom_parser'), 8);
        add_filter('the_excerpt', array($this, 'custom_parser'), 8);
        add_filter('thesis_comment_text', array($this, 'custom_parser'), 8);

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

    public function custom_parser($content)
    {
        $content = preg_replace_callback('/<img[^>]+>/', function ($matches) {
            $img_tag = $matches[0];

            // Skip if already has loading attr
            if (strpos($img_tag, 'loading=') !== false) {
                return $img_tag;
            }

            // Apply lazy load ONLY if it includes 'ql-cache' in the src
            if (preg_match('/src=[\"\']([^\"\']*ql-cache[^\"\']*)[\"\']/', $img_tag)) {
                // Insert loading="lazy" into the tag
                return preg_replace('/<img/', '<img loading="lazy"', $img_tag, 1);
            }

            return $img_tag;
        }, $content);

        if (preg_match('/<span class="ql-left-eqno"> &nbsp; <\/span>/', $content)) {
            $content = preg_replace('/<span class="ql-left-eqno"> &nbsp; <\/span>/', '', $content);
        }

        if (preg_match('/<span class="ql-right-eqno"> &nbsp; <\/span>/', $content)) {
            $content = preg_replace('/<span class="ql-right-eqno"> &nbsp; <\/span>/', '', $content);
        }
        return $content;
    }
}

$className = new QuickLatexBatch();
