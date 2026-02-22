<?php
/**
 * Plugin Name: SGA RescueGroups
 * Description: Fetches adoptable dogs from RescueGroups.org API and displays them as SGA-styled cards.
 * Version: 1.0.0
 * Author: SGA
 */

if (!defined('ABSPATH')) exit;

define('SGA_RG_PLUGIN_DIR', __DIR__);
define('SGA_RG_ORG_ID', '5558');
define('SGA_RG_API_KEY', 'uMlVrXYu');
define('SGA_RG_CACHE_TTL', 15 * MINUTE_IN_SECONDS);

require_once SGA_RG_PLUGIN_DIR . '/includes/class-api-client.php';
require_once SGA_RG_PLUGIN_DIR . '/includes/class-dog-renderer.php';

/**
 * Shortcode: [available_dogs] — renders the adoptable dogs listing.
 */
add_shortcode('available_dogs', function () {
    $client = new SGA_RescueGroups_API_Client();
    $dogs = $client->get_available_dogs();

    if (is_wp_error($dogs)) {
        return '<p>Unable to load available dogs right now. Please try again later.</p>';
    }

    if (empty($dogs)) {
        return '<p>No dogs are currently listed. Check back soon!</p>';
    }

    $renderer = new SGA_Dog_Renderer();
    return $renderer->render_listing($dogs);
});

/**
 * Shortcode: [dog_detail id="12345"] — renders a single dog's detail page.
 */
add_shortcode('dog_detail', function ($atts) {
    $atts = shortcode_atts(['id' => ''], $atts);
    if (empty($atts['id'])) return '';

    $client = new SGA_RescueGroups_API_Client();
    $dog = $client->get_dog(intval($atts['id']));

    if (is_wp_error($dog) || empty($dog)) {
        return '<p>Dog not found.</p>';
    }

    $renderer = new SGA_Dog_Renderer();
    return $renderer->render_detail($dog);
});

/**
 * Shortcode: [dog_count] — returns the number of available dogs (for homepage tile).
 */
add_shortcode('dog_count', function () {
    $client = new SGA_RescueGroups_API_Client();
    $count = $client->get_available_count();
    return is_wp_error($count) ? '30+' : strval($count);
});

/**
 * Handle dog detail page via query var.
 * URL: /adopt/?dog=12345 shows the detail view.
 */
add_filter('query_vars', function ($vars) {
    $vars[] = 'dog';
    return $vars;
});

/**
 * Enqueue plugin styles.
 */
add_action('wp_enqueue_scripts', function () {
    wp_register_style('sga-rescuegroups', false);
    wp_enqueue_style('sga-rescuegroups');
    wp_add_inline_style('sga-rescuegroups', sga_rg_get_styles());
});

function sga_rg_get_styles() {
    return '
    .sga-dogs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
    }
    .sga-dog-card {
        background: #F3F0EC;
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .sga-dog-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }
    .sga-dog-card a {
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .sga-dog-card img {
        width: 100%;
        height: 240px;
        object-fit: cover;
    }
    .sga-dog-card-body {
        padding: 20px;
    }
    .sga-dog-card h3 {
        margin: 0 0 4px;
        font-size: 22px;
        color: #2B3990;
        font-family: var(--wp--preset--font-family--heading-font, "Fraunces", serif);
    }
    .sga-dog-card .sga-dog-meta {
        margin: 0 0 8px;
        color: #4B5563;
        font-size: 15px;
    }
    .sga-dog-card .sga-dog-tagline {
        margin: 0;
        color: #4B5563;
        font-size: 15px;
        font-style: italic;
    }
    .sga-dog-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 32px;
        align-items: center;
    }
    .sga-dog-filters select {
        padding: 10px 16px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 15px;
        font-family: inherit;
        background: #fff;
    }
    .sga-dog-filters input {
        padding: 10px 16px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 15px;
        font-family: inherit;
        background: #fff;
        flex: 1;
        min-width: 200px;
    }
    .sga-filter-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }
    .sga-filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #2B3990;
        color: #fff;
        border-radius: 20px;
        padding: 6px 12px;
        font-size: 13px;
    }
    .sga-filter-chip button {
        border: none;
        background: none;
        color: #fff;
        cursor: pointer;
        font-weight: bold;
        font-size: 15px;
        padding: 0;
        line-height: 1;
    }
    .sga-dogs-count {
        color: #4B5563;
        margin-bottom: 16px;
    }
    .sga-no-results {
        text-align: center;
        padding: 48px 16px;
        color: #4B5563;
    }
    .sga-dog-card[hidden] {
        display: none;
    }
    .sga-dog-detail {
        max-width: 900px;
        margin: 0 auto;
    }
    .sga-dog-detail-header {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 32px;
        margin-bottom: 32px;
    }
    .sga-dog-detail-gallery img {
        width: 100%;
        border-radius: 12px;
        margin-bottom: 12px;
    }
    .sga-dog-detail-info h1 {
        font-size: 36px;
        color: #2B3990;
        margin: 0 0 16px;
    }
    .sga-dog-detail-facts {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px 24px;
        margin-bottom: 24px;
    }
    .sga-dog-detail-facts dt {
        font-weight: 700;
        color: #2B3990;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .sga-dog-detail-facts dd {
        margin: 0 0 8px;
        color: #4B5563;
    }
    .sga-dog-detail-description {
        line-height: 1.7;
        color: #4B5563;
    }
    .sga-dog-detail-description .rgFooter { display: none; }
    .sga-adopt-cta {
        display: inline-block;
        background: #E8772B;
        color: #fff !important;
        padding: 16px 32px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 18px;
        text-decoration: none !important;
        margin-top: 24px;
    }
    .sga-adopt-cta:hover {
        background: #D46820;
    }
    @media (max-width: 768px) {
        .sga-dogs-grid {
            grid-template-columns: 1fr;
        }
        .sga-dog-detail-header {
            grid-template-columns: 1fr;
        }
        .sga-dog-filters {
            flex-direction: column;
        }
        .sga-dog-filters select,
        .sga-dog-filters input {
            width: 100%;
            min-width: 0;
        }
        .sga-filter-chips {
            flex-wrap: wrap;
        }
    }
    ';
}
