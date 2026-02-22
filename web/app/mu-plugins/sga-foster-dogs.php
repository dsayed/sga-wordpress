<?php
/**
 * Plugin Name: SGA Foster Dogs
 * Description: Custom post type for dogs needing foster homes. Provides a structured form instead of manual page editing.
 * Version: 1.0.0
 * Author: SGA
 */

if (!defined('ABSPATH')) exit;

/**
 * Register the foster_dog post type.
 */
add_action('init', function () {
    register_post_type('foster_dog', [
        'labels' => [
            'name'               => 'Foster Dogs',
            'singular_name'      => 'Foster Dog',
            'add_new'            => 'Add New Dog',
            'add_new_item'       => 'Add New Foster Dog',
            'edit_item'          => 'Edit Foster Dog',
            'new_item'           => 'New Foster Dog',
            'view_item'          => 'View Foster Dog',
            'search_items'       => 'Search Foster Dogs',
            'not_found'          => 'No foster dogs found',
            'not_found_in_trash' => 'No foster dogs found in trash',
            'menu_name'          => 'Foster Dogs',
        ],
        'public'       => true,
        'has_archive'  => false,
        'rewrite'      => ['slug' => 'foster-dog'],
        'menu_icon'    => 'dashicons-pets',
        'menu_position'=> 5,
        'supports'     => ['title', 'thumbnail'],
        'show_in_rest' => true,
    ]);
});

/**
 * Limit uploaded image size and compress JPEGs.
 * WordPress scales down images above the "big image" threshold and saves
 * the scaled version as the main file. We lower the threshold from the
 * default 2560px to 1200px — more than enough for foster dog cards and
 * prevents multi-MB originals from eating up storage.
 */
add_filter('big_image_size_threshold', function () {
    return 1200;
});

add_filter('jpeg_quality', function () {
    return 82;
});

/**
 * Register custom meta fields for REST API (needed for block editor).
 */
add_action('init', function () {
    $fields = [
        'foster_dog_breed'   => 'string',
        'foster_dog_age'     => 'string',
        'foster_dog_urgency' => 'string',
        'foster_dog_notes'   => 'string',
    ];

    foreach ($fields as $key => $type) {
        register_post_meta('foster_dog', $key, [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $type,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
});

/**
 * Add meta box for foster dog details in the classic editor sidebar.
 */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'foster_dog_details',
        'Dog Details',
        'sga_foster_dog_meta_box',
        'foster_dog',
        'normal',
        'high'
    );
});

function sga_foster_dog_meta_box($post) {
    wp_nonce_field('sga_foster_dog_nonce', 'sga_foster_dog_nonce');

    $breed   = get_post_meta($post->ID, 'foster_dog_breed', true);
    $age     = get_post_meta($post->ID, 'foster_dog_age', true);
    $urgency = get_post_meta($post->ID, 'foster_dog_urgency', true) ?: 'needed';
    $notes   = get_post_meta($post->ID, 'foster_dog_notes', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="foster_dog_breed">Breed</label></th>
            <td><input type="text" id="foster_dog_breed" name="foster_dog_breed"
                       value="<?php echo esc_attr($breed); ?>" class="regular-text"
                       placeholder="e.g. Lab mix, German Shepherd"></td>
        </tr>
        <tr>
            <th><label for="foster_dog_age">Age</label></th>
            <td><input type="text" id="foster_dog_age" name="foster_dog_age"
                       value="<?php echo esc_attr($age); ?>" class="regular-text"
                       placeholder="e.g. 2 years, puppy, senior"></td>
        </tr>
        <tr>
            <th><label for="foster_dog_urgency">Status</label></th>
            <td>
                <select id="foster_dog_urgency" name="foster_dog_urgency">
                    <option value="urgent" <?php selected($urgency, 'urgent'); ?>>Urgent — needs foster ASAP</option>
                    <option value="needed" <?php selected($urgency, 'needed'); ?>>Needed — looking for foster</option>
                    <option value="secured" <?php selected($urgency, 'secured'); ?>>Foster Secured</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="foster_dog_notes">Notes</label></th>
            <td><textarea id="foster_dog_notes" name="foster_dog_notes"
                          rows="4" class="large-text"
                          placeholder="Brief description — temperament, special needs, ideal foster home"><?php echo esc_textarea($notes); ?></textarea></td>
        </tr>
    </table>
    <p class="description">Set a Featured Image (right sidebar) to add a photo of this dog.</p>
    <?php
}

/**
 * Save meta box data.
 */
add_action('save_post_foster_dog', function ($post_id) {
    if (!isset($_POST['sga_foster_dog_nonce']) ||
        !wp_verify_nonce($_POST['sga_foster_dog_nonce'], 'sga_foster_dog_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $fields = ['foster_dog_breed', 'foster_dog_age', 'foster_dog_urgency', 'foster_dog_notes'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
});

/**
 * Shortcode: [foster_dogs] — renders all foster dogs as cards.
 * Use this on any page to display the foster dogs listing.
 */
add_shortcode('foster_dogs', function ($atts) {
    $atts = shortcode_atts(['show_secured' => 'yes'], $atts);

    $args = [
        'post_type'      => 'foster_dog',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_key'       => 'foster_dog_urgency',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
    ];

    $dogs = new WP_Query($args);
    if (!$dogs->have_posts()) {
        return '<p>All dogs currently have foster homes! Check back soon.</p>';
    }

    // Sort: urgent first, then needed, then secured
    $priority = ['urgent' => 0, 'needed' => 1, 'secured' => 2];
    $sorted = [];
    while ($dogs->have_posts()) {
        $dogs->the_post();
        $sorted[] = [
            'id'      => get_the_ID(),
            'title'   => get_the_title(),
            'breed'   => get_post_meta(get_the_ID(), 'foster_dog_breed', true),
            'age'     => get_post_meta(get_the_ID(), 'foster_dog_age', true),
            'urgency' => get_post_meta(get_the_ID(), 'foster_dog_urgency', true) ?: 'needed',
            'notes'   => get_post_meta(get_the_ID(), 'foster_dog_notes', true),
            'image'   => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
        ];
    }
    wp_reset_postdata();

    usort($sorted, function ($a, $b) use ($priority) {
        return ($priority[$a['urgency']] ?? 1) - ($priority[$b['urgency']] ?? 1);
    });

    ob_start();
    echo '<div class="sga-foster-dogs" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px;">';

    foreach ($sorted as $dog) {
        if ($atts['show_secured'] === 'no' && $dog['urgency'] === 'secured') continue;

        $badge_color = match ($dog['urgency']) {
            'urgent'  => '#E8772B',
            'secured' => '#22C55E',
            default   => '#2B3990',
        };
        $badge_text = match ($dog['urgency']) {
            'urgent'  => 'Urgent',
            'secured' => 'Foster Secured',
            default   => 'Foster Needed',
        };

        $permalink = get_permalink($dog['id']);
        echo '<a href="' . esc_url($permalink) . '" class="sga-foster-card" style="display:block;background:#F3F0EC;border-radius:12px;overflow:hidden;text-decoration:none;color:inherit;transition:box-shadow 0.2s;">';

        if ($dog['image']) {
            echo '<img src="' . esc_url($dog['image']) . '" alt="' . esc_attr($dog['title']) . '"'
               . ' style="width:100%;height:200px;object-fit:cover;" loading="lazy">';
        } else {
            echo '<div style="width:100%;height:200px;background:#ddd;display:flex;align-items:center;justify-content:center;color:#999;">No photo yet</div>';
        }

        echo '<div style="padding:20px;">';
        echo '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">';
        echo '<h3 style="margin:0;font-size:22px;color:#2B3990;">' . esc_html($dog['title']) . '</h3>';
        echo '<span style="background:' . $badge_color . ';color:#fff;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:700;white-space:nowrap;">' . esc_html($badge_text) . '</span>';
        echo '</div>';

        if ($dog['breed'] || $dog['age']) {
            $details = array_filter([$dog['breed'], $dog['age']]);
            echo '<p style="margin:0 0 8px;color:#4B5563;font-size:15px;">' . esc_html(implode(' · ', $details)) . '</p>';
        }

        if ($dog['notes']) {
            echo '<p style="margin:0;color:#4B5563;font-size:15px;">' . esc_html($dog['notes']) . '</p>';
        }

        echo '</div></a>';
    }

    echo '</div>';
    return ob_get_clean();
});

/**
 * Add admin column for urgency status.
 */
add_filter('manage_foster_dog_posts_columns', function ($columns) {
    $new = [];
    foreach ($columns as $key => $label) {
        $new[$key] = $label;
        if ($key === 'title') {
            $new['foster_urgency'] = 'Status';
            $new['foster_breed'] = 'Breed';
        }
    }
    return $new;
});

add_action('manage_foster_dog_posts_custom_column', function ($column, $post_id) {
    if ($column === 'foster_urgency') {
        $urgency = get_post_meta($post_id, 'foster_dog_urgency', true) ?: 'needed';
        $labels = ['urgent' => '🔴 Urgent', 'needed' => '🟡 Needed', 'secured' => '🟢 Secured'];
        echo esc_html($labels[$urgency] ?? $urgency);
    }
    if ($column === 'foster_breed') {
        echo esc_html(get_post_meta($post_id, 'foster_dog_breed', true) ?: '—');
    }
}, 10, 2);

/**
 * Card hover effect.
 */
add_action('wp_head', function () {
    if (!is_singular('foster_dog') && !has_shortcode(get_post()->post_content ?? '', 'foster_dogs')) return;
    echo '<style>.sga-foster-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.12);}</style>';
});

/**
 * Render the single foster dog detail page.
 * Hooked into the_content for foster_dog posts since the CPT has no editor support.
 */
add_filter('the_content', function ($content) {
    if (!is_singular('foster_dog') || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $post_id = get_the_ID();
    $breed   = get_post_meta($post_id, 'foster_dog_breed', true);
    $age     = get_post_meta($post_id, 'foster_dog_age', true);
    $urgency = get_post_meta($post_id, 'foster_dog_urgency', true) ?: 'needed';
    $notes   = get_post_meta($post_id, 'foster_dog_notes', true);
    $image   = get_the_post_thumbnail_url($post_id, 'large');

    $badge_color = match ($urgency) {
        'urgent'  => '#E8772B',
        'secured' => '#22C55E',
        default   => '#2B3990',
    };
    $badge_text = match ($urgency) {
        'urgent'  => 'Urgent — needs foster ASAP',
        'secured' => 'Foster Secured',
        default   => 'Foster Needed',
    };

    ob_start();
    echo '<div style="max-width:720px;margin:0 auto;">';

    if ($image) {
        echo '<img src="' . esc_url($image) . '" alt="' . esc_attr(get_the_title()) . '"'
           . ' style="width:100%;max-height:480px;object-fit:cover;border-radius:12px;margin-bottom:24px;">';
    }

    echo '<span style="display:inline-block;background:' . $badge_color . ';color:#fff;padding:6px 14px;border-radius:20px;font-size:14px;font-weight:700;margin-bottom:16px;">' . esc_html($badge_text) . '</span>';

    if ($breed || $age) {
        $details = array_filter([$breed, $age]);
        echo '<p style="font-size:18px;color:#4B5563;margin:0 0 16px;">' . esc_html(implode(' · ', $details)) . '</p>';
    }

    if ($notes) {
        echo '<p style="font-size:16px;color:#4B5563;line-height:1.6;margin:0 0 32px;">' . esc_html($notes) . '</p>';
    }

    echo '<div style="display:flex;flex-wrap:wrap;gap:12px;">';
    echo '<a href="https://secure.savinggreatanimals.org" style="display:inline-block;background:#E8772B;color:#fff;padding:14px 28px;border-radius:6px;font-weight:700;font-size:16px;text-decoration:none;">Apply to Foster</a>';
    echo '<a href="/foster/" style="display:inline-block;background:#2B3990;color:#fff;padding:14px 28px;border-radius:6px;font-weight:700;font-size:16px;text-decoration:none;">Back to Foster Page</a>';
    echo '</div>';

    echo '</div>';
    return ob_get_clean();
});
