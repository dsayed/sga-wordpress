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

/**
 * Style The Events Calendar to match SGA theme.
 */
add_action('wp_enqueue_scripts', function () {
    if (!class_exists('Tribe__Events__Main')) return;

    wp_register_style('sga-events-calendar', false);
    wp_enqueue_style('sga-events-calendar');
    wp_add_inline_style('sga-events-calendar', '
        .tribe-events .tribe-events-calendar-list__event-title a {
            color: #2B3990;
            font-family: var(--wp--preset--font-family--heading-font, "Fraunces", serif);
        }
        .tribe-events .tribe-events-calendar-list__event-title a:hover {
            color: #E8772B;
        }
        .tribe-events .tribe-events-c-nav__prev,
        .tribe-events .tribe-events-c-nav__next {
            color: #2B3990;
        }
        .tribe-common .tribe-common-c-btn-border,
        .tribe-common a.tribe-common-c-btn-border {
            border-color: #2B3990;
            color: #2B3990;
        }
        .tribe-common .tribe-common-c-btn-border:hover {
            background: #2B3990;
            color: #fff;
        }
    ');
});

/**
 * Mobile refinements and accessibility improvements.
 */
add_action('wp_enqueue_scripts', function () {
    wp_register_style('sga-mobile', false);
    wp_enqueue_style('sga-mobile');
    wp_add_inline_style('sga-mobile', '
        /* Ensure minimum touch target size (WCAG 2.5.8) */
        .wp-block-navigation-item a,
        .wp-block-social-link a,
        .wp-block-button__link {
            min-height: 48px;
            display: inline-flex;
            align-items: center;
        }

        /* Focus styles for keyboard navigation */
        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        select:focus-visible,
        textarea:focus-visible {
            outline: 3px solid #E8772B;
            outline-offset: 2px;
        }

        /* Skip to content link */
        .skip-to-content {
            position: absolute;
            left: -9999px;
            top: 0;
            z-index: 9999;
            background: #2B3990;
            color: #fff;
            padding: 12px 24px;
            font-size: 16px;
        }
        .skip-to-content:focus {
            left: 0;
        }

        /* Mobile: sticky bottom donate bar */
        @media (max-width: 768px) {
            .sga-mobile-donate {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: rgba(251, 248, 244, 0.92);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                border-top: 1px solid rgba(0,0,0,0.1);
                padding: 8px 12px;
                z-index: 9999;
                display: flex;
                gap: 8px;
            }
            .sga-mobile-donate a {
                flex: 1;
                text-align: center;
                padding: 10px;
                border-radius: 8px;
                font-weight: 700;
                font-size: 14px;
                text-decoration: none;
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .sga-mobile-donate .btn-adopt {
                background: #2B3990;
                color: #fff;
            }
            .sga-mobile-donate .btn-donate {
                background: #E8772B;
                color: #fff;
            }
            body { padding-bottom: 60px; }
        }
        @media (min-width: 769px) {
            .sga-mobile-donate { display: none; }
        }
    ');
});

/**
 * Add mobile sticky bottom bar for Adopt + Donate.
 */
add_action('wp_footer', function () {
    echo '<div class="sga-mobile-donate">';
    echo '<a href="/adopt/" class="btn-adopt">Adopt</a>';
    echo '<a href="/donate/" class="btn-donate">Donate</a>';
    echo '</div>';
});
