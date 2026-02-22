<?php
/**
 * One-time staging setup script.
 * Hit this URL with ?token=sga-setup-2026 to configure staging.
 * DELETE THIS FILE after use.
 */

if (($_GET['token'] ?? '') !== 'sga-setup-2026') {
    http_response_code(403);
    die('Forbidden');
}

// Bootstrap WordPress
require_once __DIR__ . '/wp/wp-load.php';

header('Content-Type: text/plain');
$results = [];

// 1. Activate SGA theme
switch_theme('sga');
$results[] = 'Theme: SGA activated (current: ' . get_stylesheet() . ')';

// 2. Activate Events Calendar plugin
$plugin = 'the-events-calendar/the-events-calendar.php';
if (!is_plugin_active($plugin)) {
    $activated = activate_plugin($plugin);
    $results[] = is_wp_error($activated)
        ? 'Plugin: FAILED - ' . $activated->get_error_message()
        : 'Plugin: The Events Calendar activated';
} else {
    $results[] = 'Plugin: The Events Calendar already active';
}

// 3. Set permalink structure
global $wp_rewrite;
$wp_rewrite->set_permalink_structure('/%postname%/');
$wp_rewrite->flush_rules(true);
$results[] = 'Permalinks: set to /%postname%/';

// 4. Set site options
update_option('blogdescription', 'The Right Dog For The Right Home');
update_option('timezone_string', 'America/Los_Angeles');
update_option('date_format', 'F j, Y');
update_option('show_on_front', 'posts');
$results[] = 'Options: tagline, timezone, date format set';

// 5. Delete default content
wp_delete_post(1, true); // Hello world
wp_delete_post(2, true); // Sample page
wp_delete_comment(1, true);
$results[] = 'Default content: deleted';

// 6. Create pages (only if they don't exist)
$pages = [
    ['Adopt', 'adopt', '[available_dogs]'],
    ['Foster', 'foster', ''],
    ['Dogs Needing Fosters', 'dogs-needing-fosters', '[foster_dogs]'],
    ['Get Involved', 'get-involved', ''],
    ['About', 'about', ''],
    ['Donate', 'donate', ''],
    ['Surrender', 'surrender', ''],
    ['Resources', 'resources', ''],
    ['Events', 'events', ''],
];

foreach ($pages as [$title, $slug, $content]) {
    $existing = get_page_by_path($slug);
    if (!$existing) {
        $id = wp_insert_post([
            'post_type'    => 'page',
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_status'  => 'publish',
            'post_content' => $content,
        ]);
        $results[] = "Page: Created '$title' (ID $id)";
    } else {
        $results[] = "Page: '$title' already exists (ID {$existing->ID})";
    }
}

// 7. Create editor accounts
if (!username_exists('lily')) {
    wp_create_user('lily', wp_generate_password(), 'lily@savinggreatanimals.org');
    $user = get_user_by('login', 'lily');
    $user->set_role('editor');
    wp_update_user(['ID' => $user->ID, 'display_name' => 'Lily Piecora']);
    $results[] = 'User: Created lily (editor)';
} else {
    $results[] = 'User: lily already exists';
}

if (!username_exists('jacintha')) {
    wp_create_user('jacintha', wp_generate_password(), 'jacintha@savinggreatanimals.org');
    $user = get_user_by('login', 'jacintha');
    $user->set_role('editor');
    wp_update_user(['ID' => $user->ID, 'display_name' => 'Jacintha Sayed']);
    $results[] = 'User: Created jacintha (editor)';
} else {
    $results[] = 'User: jacintha already exists';
}

echo "=== SGA Staging Setup ===\n";
echo implode("\n", $results) . "\n";
echo "\n=== DONE ===\n";
echo "DELETE this file now!\n";
