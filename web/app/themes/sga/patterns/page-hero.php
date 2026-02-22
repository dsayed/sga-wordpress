<?php
/**
 * Title: SGA Page Hero
 * Slug: sga/page-hero
 * Categories: sga
 * Description: Page-level hero with title and subtitle on a colored background.
 */
?>
<!-- wp:cover {"dimRatio":100,"overlayColor":"contrast","isUserOverlayColor":true,"minHeight":300,"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"24px","right":"24px"}}}} -->
<div class="wp-block-cover alignfull" style="padding-top:var(--wp--preset--spacing--80);padding-right:24px;padding-bottom:var(--wp--preset--spacing--80);padding-left:24px;min-height:300px">
<span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-100 has-background-dim"></span>
<div class="wp-block-cover__inner-container">

<!-- wp:group {"layout":{"type":"constrained","contentSize":"800px"}} -->
<div class="wp-block-group">

<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"clamp(2rem, 4vw, 3.5rem)","fontWeight":"900"},"color":{"text":"#ffffff"}}} -->
<h1 class="wp-block-heading has-text-align-center" style="color:#ffffff;font-size:clamp(2rem, 4vw, 3.5rem);font-weight:900">Page Title</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#dddddd"},"typography":{"fontSize":"1.2rem"}}} -->
<p class="has-text-align-center" style="color:#ffffff;font-size:1.2rem">A short description of this page.</p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->

</div>
</div>
<!-- /wp:cover -->
