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

        // Collect unique values for filter dropdowns
        $ages = array_unique(array_filter(array_column($dogs, 'age')));
        sort($ages);
        $sizes = array_unique(array_filter(array_column($dogs, 'size')));
        sort($sizes);
        $total = count($dogs);

        // Filter bar — no <form>, all filtering is client-side
        echo '<div class="sga-dog-filters">';

        echo '<input type="text" class="sga-filter-search" placeholder="Search by name or breed...">';

        echo '<select class="sga-filter-age"><option value="">All Ages</option>';
        foreach ($ages as $age) {
            echo '<option value="' . esc_attr($age) . '">' . esc_html($age) . '</option>';
        }
        echo '</select>';

        echo '<select class="sga-filter-sex">';
        echo '<option value="">All</option>';
        echo '<option value="Male">Male</option>';
        echo '<option value="Female">Female</option>';
        echo '</select>';

        if (!empty($sizes)) {
            echo '<select class="sga-filter-size"><option value="">All Sizes</option>';
            foreach ($sizes as $size) {
                echo '<option value="' . esc_attr($size) . '">' . esc_html($size) . '</option>';
            }
            echo '</select>';
        }

        echo '</div>';

        // Active filter chips (populated by JS)
        echo '<div class="sga-filter-chips"></div>';

        // Result count (updated by JS)
        echo '<p class="sga-dogs-count">' . $total . ' dogs available</p>';

        // No results message (hidden by default)
        echo '<div class="sga-no-results" style="display:none">';
        echo '<p>No dogs match your filters. Try removing a filter or searching for something else.</p>';
        echo '</div>';

        // Dog grid — all dogs rendered, JS controls visibility
        echo '<div class="sga-dogs-grid">';
        foreach ($dogs as $dog) {
            echo $this->render_card($dog);
        }
        echo '</div>';

        // Inline JS for instant filtering
        echo $this->render_filter_script($total);

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
        echo '<div class="sga-dog-card"';
        echo ' data-name="' . esc_attr(strtolower($dog['name'])) . '"';
        echo ' data-breed="' . esc_attr(strtolower($dog['breed'])) . '"';
        echo ' data-age="' . esc_attr($dog['age']) . '"';
        echo ' data-sex="' . esc_attr($dog['sex']) . '"';
        echo ' data-size="' . esc_attr($dog['size'] ?? '') . '"';
        echo '>';
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
            'Size'         => $dog['size'] ?? '',
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

    /**
     * Enqueue inline JavaScript for instant client-side filtering.
     *
     * Uses wp_footer to avoid WordPress content filters (wptexturize)
     * mangling && operators inside <script> tags in shortcode output.
     */
    private function render_filter_script(int $total): string {
        $t = (int) $total;
        add_action('wp_footer', function () use ($t) {
            echo '<script>';
            echo $this->get_filter_js($t);
            echo '</script>';
        });
        return '';
    }

    private function get_filter_js(int $total): string {
        return <<<JS
(function() {
    var cards = document.querySelectorAll('.sga-dog-card');
    var search = document.querySelector('.sga-filter-search');
    var ageSelect = document.querySelector('.sga-filter-age');
    var sexSelect = document.querySelector('.sga-filter-sex');
    var sizeSelect = document.querySelector('.sga-filter-size');
    var countEl = document.querySelector('.sga-dogs-count');
    var chipsEl = document.querySelector('.sga-filter-chips');
    var noResults = document.querySelector('.sga-no-results');
    var total = {$total};

    if (!search || !ageSelect || !sexSelect || !countEl || !chipsEl || !noResults) return;

    function applyFilters() {
        var q = search.value.toLowerCase().trim();
        var age = ageSelect.value;
        var sex = sexSelect.value;
        var size = sizeSelect ? sizeSelect.value : '';
        var visible = 0;

        cards.forEach(function(card) {
            var matchSearch = !q || card.dataset.name.indexOf(q) !== -1 || card.dataset.breed.indexOf(q) !== -1;
            var matchAge = !age || card.dataset.age === age;
            var matchSex = !sex || card.dataset.sex === sex;
            var matchSize = !size || card.dataset.size === size;

            if (matchSearch && matchAge && matchSex && matchSize) {
                card.hidden = false;
                visible++;
            } else {
                card.hidden = true;
            }
        });

        if (visible < total) {
            countEl.textContent = visible + ' of ' + total + ' dogs';
        } else {
            countEl.textContent = total + ' dogs available';
        }

        noResults.style.display = visible === 0 ? '' : 'none';
        buildChips(q, age, sex, size);
        syncUrl(q, age, sex, size);
    }

    function buildChips(q, age, sex, size) {
        while (chipsEl.firstChild) chipsEl.removeChild(chipsEl.firstChild);
        if (q) addChip('Search: ' + q, function() { search.value = ''; applyFilters(); });
        if (age) addChip('Age: ' + age, function() { ageSelect.value = ''; applyFilters(); });
        if (sex) addChip('Sex: ' + sex, function() { sexSelect.value = ''; applyFilters(); });
        if (size && sizeSelect) addChip('Size: ' + size, function() { sizeSelect.value = ''; applyFilters(); });
    }

    function addChip(label, onRemove) {
        var chip = document.createElement('span');
        chip.className = 'sga-filter-chip';
        chip.textContent = label + ' ';
        var btn = document.createElement('button');
        btn.textContent = '\u00d7';
        btn.setAttribute('aria-label', 'Remove filter');
        btn.addEventListener('click', onRemove);
        chip.appendChild(btn);
        chipsEl.appendChild(chip);
    }

    function syncUrl(q, age, sex, size) {
        var params = new URLSearchParams(window.location.search);
        q ? params.set('search', q) : params.delete('search');
        age ? params.set('age', age) : params.delete('age');
        sex ? params.set('sex', sex) : params.delete('sex');
        size ? params.set('size', size) : params.delete('size');
        var qs = params.toString();
        var url = window.location.pathname + (qs ? '?' + qs : '');
        history.replaceState(null, '', url);
    }

    function restoreFromUrl() {
        var params = new URLSearchParams(window.location.search);
        if (params.has('search')) search.value = params.get('search');
        if (params.has('age')) ageSelect.value = params.get('age');
        if (params.has('sex')) sexSelect.value = params.get('sex');
        if (sizeSelect && params.has('size')) sizeSelect.value = params.get('size');
    }

    search.addEventListener('input', applyFilters);
    ageSelect.addEventListener('change', applyFilters);
    sexSelect.addEventListener('change', applyFilters);
    if (sizeSelect) sizeSelect.addEventListener('change', applyFilters);

    restoreFromUrl();
    applyFilters();
})();
JS;
    }
}
