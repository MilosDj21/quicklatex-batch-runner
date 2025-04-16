<?php

if (!defined('WP_CLI')) {
    return;
}
/**
* Generate & cache all LaTeX images, replace code with <img> tags in every post.
*/
class QuickLaTeX_Batch_Command
{
    /**
     * wp quicklatex batch
     */
    public function __invoke($args, $assoc_args)
    {
        WP_CLI::log('Started job');
        $posts = get_posts([
            'post_type'      => 'post',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);
        WP_CLI::log('Posts retrieved');

        foreach ($posts as $post_id) {
            WP_CLI::log("Post $post_id");
            $post    = get_post($post_id);
            $content = $post->post_content;

            // run the exact same parser that would normally fire on-the-fly
            $new_content = quicklatex_parser($content);
            WP_CLI::log("Latex parsed");

            if ($new_content !== $content) {
                wp_update_post([
                    'ID'           => $post_id,
                    'post_content' => $new_content,
                ]);
                WP_CLI::log("✅ Post {$post_id} updated.");
            } else {
                WP_CLI::log("— Post {$post_id} had no LaTeX.");
            }
        }

        WP_CLI::success("All done!");
    }
}
