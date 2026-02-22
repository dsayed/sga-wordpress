<?php
/**
 * Title: SGA Foster Urgency
 * Slug: sga/foster-urgency
 * Categories: sga
 * Description: Dynamic section showing dogs that need foster homes. Hides when none need fosters.
 */

$dogs = new WP_Query([
    'post_type'      => 'foster_dog',
    'posts_per_page' => 3,
    'post_status'    => 'publish',
    'meta_query'     => [
        'urgency_clause' => [
            'key'     => 'foster_dog_urgency',
            'value'   => ['urgent', 'needed'],
            'compare' => 'IN',
        ],
    ],
    'orderby' => ['urgency_clause' => 'DESC'],
]);

// Hide the entire section if no dogs need fosters
if (!$dogs->have_posts()) {
    return;
}

// Count all dogs needing fosters (not just the 3 we display)
$total = new WP_Query([
    'post_type'      => 'foster_dog',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'fields'         => 'ids',
    'meta_query'     => [
        [
            'key'     => 'foster_dog_urgency',
            'value'   => ['urgent', 'needed'],
            'compare' => 'IN',
        ],
    ],
]);
$count = $total->found_posts;

// Sort: urgent first, then needed
$sorted = [];
while ($dogs->have_posts()) {
    $dogs->the_post();
    $urgency = get_post_meta(get_the_ID(), 'foster_dog_urgency', true) ?: 'needed';
    $sorted[] = [
        'title'   => get_the_title(),
        'breed'   => get_post_meta(get_the_ID(), 'foster_dog_breed', true),
        'age'     => get_post_meta(get_the_ID(), 'foster_dog_age', true),
        'urgency' => $urgency,
        'image'   => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
    ];
}
wp_reset_postdata();

usort($sorted, function ($a, $b) {
    $priority = ['urgent' => 0, 'needed' => 1];
    return ($priority[$a['urgency']] ?? 1) - ($priority[$b['urgency']] ?? 1);
});

$headline = $count === 1
    ? '1 dog needs a foster home'
    : $count . ' dogs need foster homes';
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"24px","right":"24px"}},"color":{"background":"#FFF5EB"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="background-color:#FFF5EB;padding-top:var(--wp--preset--spacing--70);padding-right:24px;padding-bottom:var(--wp--preset--spacing--70);padding-left:24px">

<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2.5rem)"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:clamp(1.5rem, 3vw, 2.5rem)"><?php echo esc_html($headline); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"18px"},"spacing":{"margin":{"bottom":"32px"}}}} -->
<p class="has-text-align-center" style="font-size:18px;margin-bottom:32px">Can you open your home to a dog in need?</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:24px;max-width:1200px;margin:0 auto 40px;">
<?php foreach ($sorted as $dog) :
    $badge_color = $dog['urgency'] === 'urgent' ? '#E8772B' : '#2B3990';
    $badge_text  = $dog['urgency'] === 'urgent' ? 'Urgent' : 'Foster Needed';
    $details     = array_filter([$dog['breed'], $dog['age']]);
?>
    <div style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
        <?php if ($dog['image']) : ?>
            <img src="<?php echo esc_url($dog['image']); ?>" alt="<?php echo esc_attr($dog['title']); ?>" style="width:100%;height:200px;object-fit:cover;" loading="lazy">
        <?php else : ?>
            <div style="width:100%;height:200px;background:#F3F0EC;display:flex;align-items:center;justify-content:center;color:#999;">No photo yet</div>
        <?php endif; ?>
        <div style="padding:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                <h3 style="margin:0;font-size:20px;color:#2B3990;font-family:var(--wp--preset--font-family--heading-font, 'Fraunces', serif);"><?php echo esc_html($dog['title']); ?></h3>
                <span style="background:<?php echo esc_attr($badge_color); ?>;color:#fff;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;white-space:nowrap;"><?php echo esc_html($badge_text); ?></span>
            </div>
            <?php if ($details) : ?>
                <p style="margin:0;color:#4B5563;font-size:15px;"><?php echo esc_html(implode(' · ', $details)); ?></p>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
<!-- /wp:html -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"accent-1","textColor":"accent-6","style":{"typography":{"fontSize":"18px","fontWeight":"700"},"border":{"radius":"6px"},"spacing":{"padding":{"top":"16px","bottom":"16px","left":"32px","right":"32px"}}}} -->
<div class="wp-block-button" style="font-size:18px;font-weight:700"><a class="wp-block-button__link has-accent-6-color has-accent-1-background-color has-text-color has-background wp-element-button" href="/foster/" style="border-radius:6px;padding-top:16px;padding-right:32px;padding-bottom:16px;padding-left:32px">Learn About Fostering</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

</div>
<!-- /wp:group -->
