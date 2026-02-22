<?php
/**
 * SGA child theme functions.
 */

add_action('init', function () {
    register_block_pattern_category('sga', [
        'label' => __('SGA', 'sga'),
    ]);
});

/**
 * Remove the Site Editor menu for all users.
 *
 * Templates and global styles are managed via git-tracked files only
 * (theme.json, parts/*.html, templates/*.html). Preventing Site Editor
 * access ensures no one accidentally creates database overrides that
 * diverge from the git source of truth.
 *
 * Content editors (Lily, Jacintha) use the block editor for page/post
 * content -- that's unaffected by this.
 */
add_action('admin_menu', function () {
    remove_submenu_page('themes.php', 'site-editor.php');
});
