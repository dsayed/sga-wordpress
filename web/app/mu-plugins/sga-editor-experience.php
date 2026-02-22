<?php
/**
 * Plugin Name: SGA Editor Experience
 * Description: Simplifies wp-admin for editors (Jacintha, Lily). Hides menus and panels they don't need.
 * Version: 1.0.0
 * Author: SGA
 */

if (!defined('ABSPATH')) exit;

/**
 * Only apply simplifications for users with the editor role.
 * Admins see the full WordPress interface.
 */
function sga_is_editor() {
    $user = wp_get_current_user();
    return $user->exists() && in_array('editor', $user->roles, true);
}

/**
 * Remove admin menus that editors don't need.
 * Editors should only see: Foster Dogs, Events, Media, and their Profile.
 */
add_action('admin_menu', function () {
    if (!sga_is_editor()) return;

    remove_menu_page('index.php');           // Dashboard
    remove_menu_page('edit.php');            // Posts
    remove_menu_page('edit.php?post_type=page'); // Pages
    remove_menu_page('edit-comments.php');   // Comments
    remove_menu_page('themes.php');          // Appearance
    remove_menu_page('plugins.php');         // Plugins
    remove_menu_page('users.php');           // Users
    remove_menu_page('tools.php');           // Tools
    remove_menu_page('options-general.php'); // Settings

    // Clean up Events Calendar submenu — editors only need "Events" and "Add New Event"
    $events_submenus_to_remove = [
        'edit-tags.php?taxonomy=post_tag&post_type=tribe_events',
        'edit-tags.php?taxonomy=tribe_events_cat&post_type=tribe_events',
        'edit.php?post_type=tribe_venue',
        'edit.php?post_type=tribe_organizer',
        'edit.php?post_type=tribe_events&page=aggregator',
        'edit.php?post_type=tribe_events&page=tec-events-settings',
        'edit.php?post_type=tribe_events&page=tec-events-help-hub',
        'edit.php?post_type=tribe_events&page=tec-troubleshooting',
        'edit.php?post_type=tribe_events&page=tribe-app-shop',
        'edit.php?post_type=tribe_events&page=first-time-setup',
        'edit.php?post_type=tec_calendar_embed',
    ];
    foreach ($events_submenus_to_remove as $submenu) {
        remove_submenu_page('edit.php?post_type=tribe_events', $submenu);
    }
}, 999);

/**
 * Redirect editors to Foster Dogs list instead of Dashboard on login.
 */
add_filter('login_redirect', function ($redirect_to, $requested, $user) {
    if (!is_wp_error($user) && in_array('editor', $user->roles, true)) {
        return admin_url('edit.php?post_type=foster_dog');
    }
    return $redirect_to;
}, 10, 3);

/**
 * Also redirect editors away from Dashboard if they land there directly.
 */
add_action('admin_init', function () {
    if (!sga_is_editor()) return;

    global $pagenow;
    if ($pagenow === 'index.php') {
        wp_redirect(admin_url('edit.php?post_type=foster_dog'));
        exit;
    }
});

/**
 * Clean up the admin bar for editors.
 */
add_action('admin_bar_menu', function ($wp_admin_bar) {
    if (!sga_is_editor()) return;

    $wp_admin_bar->remove_node('wp-logo');
    $wp_admin_bar->remove_node('comments');
    $wp_admin_bar->remove_node('new-content');
    $wp_admin_bar->remove_node('updates');
}, 999);

/**
 * Hide unnecessary panels and polish the editor experience with CSS.
 */
add_action('admin_head', function () {
    if (!sga_is_editor()) return;
    ?>
    <style>
        /* Hide "Post Attributes" meta box (template selector — editors don't need this) */
        #pageparentdiv,
        #post-attributes-meta-box { display: none !important; }

        /* Hide Screen Options and Help tabs */
        #screen-options-link-wrap,
        #contextual-help-link-wrap { display: none !important; }

        /* Hide Bedrock and plugin notices */
        .notice { display: none !important; }

        /* Hide remaining Events Calendar submenu items that resist removal */
        #menu-posts-tribe_events .wp-submenu li:not(:nth-child(-n+2)) { display: none !important; }

        /* Hide WordPress version in footer */
        #footer-upgrade { display: none !important; }
        #footer-thankyou { display: none !important; }

        /* Simplify Publish box — hide Status, Visibility, Schedule options */
        .misc-pub-section { display: none !important; }

        /* Hide unnecessary Events meta boxes for editors */
        #tagsdiv-post_tag,
        #tribe_events_catdiv,
        #tribe_events_event_options,
        #postcustom,
        #authordiv,
        #postexcerpt { display: none !important; }

        /* Hide Events Calendar upsell/promo notices within the event form */
        .tribe-events-admin .tribe-notice-container,
        .tec-admin__upsell,
        tr:has(.tribe-events-admin-upsell),
        tr:has(a[href*="evnt.is"]),
        tr:has(a[href*="theeventscalendar.com/products"]) { display: none !important; }

        /* Hide "Events Status" meta box */
        #tribe-events-status { display: none !important; }

        /* Hide "Additional Functionality" upsell in Event Cost table */
        #event_cost tr:last-child,
        #event_cost tr:nth-last-child(2) { display: none !important; }

        /* Simplify Event Cost — hide Currency Symbol and ISO Code rows, keep only Cost */
        #event_cost tr:has(#EventCurrencySymbol),
        #event_cost tr:has(#EventCurrencyCode) { display: none !important; }

        /* Hide "Rate The Events Calendar" footer */
        .post-type-tribe_events #wpfooter { display: none !important; }

        /* Make the title placeholder more descriptive for Foster Dogs */
        .post-type-foster_dog #title-prompt-text {
            font-size: 1.3em !important;
        }
    </style>
    <?php
});

/**
 * Change the "Add title" placeholder to "Dog's Name" for Foster Dogs.
 */
add_filter('enter_title_here', function ($title, $post) {
    if ($post->post_type === 'foster_dog') {
        return "Dog's Name (e.g. Binky, Miss Piggy)";
    }
    if ($post->post_type === 'tribe_events') {
        return "Event Name (e.g. Adoption Day at Magnuson Park)";
    }
    return $title;
}, 10, 2);

/**
 * Remove unnecessary meta boxes for editors.
 */
add_action('add_meta_boxes', function () {
    if (!sga_is_editor()) return;

    // Foster Dogs: remove template selector
    remove_meta_box('pageparentdiv', 'foster_dog', 'side');

    // Events: remove clutter
    remove_meta_box('tagsdiv-post_tag', 'tribe_events', 'side');
    remove_meta_box('tribe_events_catdiv', 'tribe_events', 'side');
    remove_meta_box('tribe_events_event_options', 'tribe_events', 'side');
    remove_meta_box('tribe-events-status', 'tribe_events', 'side');
    remove_meta_box('postcustom', 'tribe_events', 'normal');
    remove_meta_box('authordiv', 'tribe_events', 'normal');
    remove_meta_box('postexcerpt', 'tribe_events', 'normal');
}, 99);
