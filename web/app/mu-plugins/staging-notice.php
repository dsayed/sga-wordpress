<?php
/**
 * Show an environment banner on non-production sites (frontend + admin).
 */
if (defined('WP_ENV') && WP_ENV !== 'production') {
    $env_banner = function () {
        $env = strtoupper(WP_ENV);
        $color = WP_ENV === 'staging' ? '#E8772B' : '#2B3990';
        echo '<div style="background:' . $color . ';color:#fff;text-align:center;padding:8px;font-family:sans-serif;font-size:14px;">';
        echo esc_html($env) . ' — Saving Great Animals';
        echo '</div>';
    };

    // Frontend
    add_action('wp_body_open', $env_banner);

    // Admin
    add_action('in_admin_header', $env_banner);
}
