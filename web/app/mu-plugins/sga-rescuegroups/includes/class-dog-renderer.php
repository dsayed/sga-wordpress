<?php
/**
 * Renders dog listings and detail pages as HTML.
 */

if (!defined('ABSPATH')) exit;

class SGA_Dog_Renderer {

    /**
     * Render the full listing page with filters and dog grid.
     */
    public function render_listing(array $dogs): string {
        ob_start();

        // Get current filter values from URL
        $filter_age   = sanitize_text_field($_GET['age'] ?? '');
        $filter_sex   = sanitize_text_field($_GET['sex'] ?? '');
        $filter_breed = sanitize_text_field($_GET['breed'] ?? '');

        // Check if a specific dog detail is requested
        $dog_id = intval(get_query_var('dog', 0));
        if ($dog_id > 0) {
            $client = new SGA_RescueGroups_API_Client();
            $dog = $client->get_dog($dog_id);
            if (!is_wp_error($dog) && !empty($dog)) {
                echo $this->render_detail($dog);
                return ob_get_clean();
            }
        }

        // Apply filters
        $filtered = array_filter($dogs, function ($dog) use ($filter_age, $filter_sex, $filter_breed) {
            if ($filter_age && strcasecmp($dog['age'], $filter_age) !== 0) return false;
            if ($filter_sex && strcasecmp($dog['sex'], $filter_sex) !== 0) return false;
            if ($filter_breed && stripos($dog['breed'], $filter_breed) === false) return false;
            return true;
        });

        // Get unique ages for filter dropdown
        $ages = array_unique(array_filter(array_column($dogs, 'age')));
        sort($ages);

        // Filter bar
        $current_url = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
        echo '<form class="sga-dog-filters" method="get" action="' . esc_url($current_url) . '">';

        echo '<select name="age"><option value="">All Ages</option>';
        foreach ($ages as $age) {
            $selected = ($filter_age === $age) ? ' selected' : '';
            echo '<option value="' . esc_attr($age) . '"' . $selected . '>' . esc_html($age) . '</option>';
        }
        echo '</select>';

        echo '<select name="sex">';
        echo '<option value="">All</option>';
        echo '<option value="Male"' . selected($filter_sex, 'Male', false) . '>Male</option>';
        echo '<option value="Female"' . selected($filter_sex, 'Female', false) . '>Female</option>';
        echo '</select>';

        echo '<input type="text" name="breed" placeholder="Search breed..." value="' . esc_attr($filter_breed) . '">';

        echo '<button type="submit" style="padding:10px 24px;background:#2B3990;color:#fff;border:none;border-radius:8px;font-size:15px;cursor:pointer;">Filter</button>';

        if ($filter_age || $filter_sex || $filter_breed) {
            echo '<a href="' . esc_url($current_url) . '" style="padding:10px 16px;color:#4B5563;font-size:14px;">Clear</a>';
        }

        echo '</form>';

        // Result count
        $count = count($filtered);
        $total = count($dogs);
        if ($count < $total) {
            echo '<p style="color:#4B5563;margin-bottom:16px;">' . $count . ' of ' . $total . ' dogs</p>';
        } else {
            echo '<p style="color:#4B5563;margin-bottom:16px;">' . $total . ' dogs available</p>';
        }

        // Dog grid
        echo '<div class="sga-dogs-grid">';
        foreach ($filtered as $dog) {
            echo $this->render_card($dog);
        }
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Render a single dog card for the grid.
     */
    public function render_card(array $dog): string {
        $photo = $dog['photos'][0]['large'] ?? '';
        $detail_url = add_query_arg('dog', $dog['id']);
        $meta_parts = array_filter([$dog['breed'], $dog['age'], $dog['sex']]);

        ob_start();
        echo '<div class="sga-dog-card">';
        echo '<a href="' . esc_url($detail_url) . '">';

        if ($photo) {
            echo '<img src="' . esc_url($photo) . '" alt="' . esc_attr($dog['name']) . '" loading="lazy">';
        } else {
            echo '<div style="width:100%;height:240px;background:#ddd;display:flex;align-items:center;justify-content:center;color:#999;">No photo</div>';
        }

        echo '<div class="sga-dog-card-body">';
        echo '<h3>' . esc_html($dog['name']) . '</h3>';

        if ($meta_parts) {
            echo '<p class="sga-dog-meta">' . esc_html(implode(' · ', $meta_parts)) . '</p>';
        }

        if ($dog['tagline']) {
            echo '<p class="sga-dog-tagline">' . esc_html($dog['tagline']) . '</p>';
        }

        echo '</div></a></div>';
        return ob_get_clean();
    }

    /**
     * Render a dog's detail page.
     */
    public function render_detail(array $dog): string {
        ob_start();
        echo '<div class="sga-dog-detail">';

        // Back link
        $back_url = remove_query_arg('dog');
        echo '<p><a href="' . esc_url($back_url) . '" style="color:#2B3990;">&larr; Back to all dogs</a></p>';

        echo '<div class="sga-dog-detail-header">';

        // Photo gallery
        echo '<div class="sga-dog-detail-gallery">';
        if (!empty($dog['photos'])) {
            $first = true;
            foreach ($dog['photos'] as $photo) {
                $src = $first ? ($photo['large'] ?: $photo['original']) : ($photo['large'] ?: $photo['original']);
                echo '<img src="' . esc_url($src) . '" alt="' . esc_attr($dog['name']) . '" loading="' . ($first ? 'eager' : 'lazy') . '">';
                $first = false;
            }
        }
        echo '</div>';

        // Info
        echo '<div class="sga-dog-detail-info">';
        echo '<h1>' . esc_html($dog['name']) . '</h1>';

        echo '<dl class="sga-dog-detail-facts">';
        $facts = [
            'Breed'        => $dog['breed'],
            'Age'          => $dog['age'],
            'Sex'          => $dog['sex'],
            'Color'        => $dog['color'],
            'Coat'         => $dog['coat_length'],
            'Housetrained' => $dog['housetrained'],
        ];
        foreach ($facts as $label => $value) {
            if ($value) {
                echo '<dt>' . esc_html($label) . '</dt>';
                echo '<dd>' . esc_html($value) . '</dd>';
            }
        }
        echo '</dl>';

        echo '<a class="sga-adopt-cta" href="https://secure.savinggreatanimals.org" target="_blank" rel="noopener">Apply to Adopt ' . esc_html($dog['name']) . '</a>';

        echo '</div></div>';

        // Description
        if ($dog['description']) {
            echo '<div class="sga-dog-detail-description">';
            echo '<h2>About ' . esc_html($dog['name']) . '</h2>';
            echo wp_kses_post($dog['description']);
            echo '</div>';
        }

        echo '</div>';
        return ob_get_clean();
    }
}
