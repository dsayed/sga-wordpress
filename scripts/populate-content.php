<?php
/**
 * Populate page content for all SGA pages.
 *
 * Usage: docker compose exec -T wordpress wp --allow-root --path=/var/www/html/web/wp eval-file /var/www/html/scripts/populate-content.php
 */

// Helper: find page by slug
function sga_get_page_id($slug) {
    $page = get_page_by_path($slug);
    if ($page) return $page->ID;
    echo "  WARNING: Page '$slug' not found\n";
    return null;
}

// Helper: update page content
function sga_update_page($slug, $content) {
    $id = sga_get_page_id($slug);
    if (!$id) return;
    wp_update_post(['ID' => $id, 'post_content' => $content]);
    echo "  Updated: $slug (ID: $id)\n";
}

echo "=== Populating Page Content ===\n";

// ─── ABOUT ───────────────────────────────────────────────────
sga_update_page('about', '
<!-- wp:cover {"dimRatio":100,"overlayColor":"contrast","isDark":true,"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"24px","right":"24px"}},"dimensions":{"minHeight":"300px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-dark" style="padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--60);padding-left:24px;min-height:300px"><span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-100 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"clamp(2rem, 4vw, 3.5rem)"},"elements":{"link":{"color":{"text":"var:preset|color|accent-6"}}}},"textColor":"accent-6"} -->
<h1 class="wp-block-heading has-text-align-center has-accent-6-color has-text-color has-link-color" style="font-size:clamp(2rem, 4vw, 3.5rem)">About Saving Great Animals</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"20px"},"elements":{"link":{"color":{"text":"var:preset|color|accent-3"}}}},"textColor":"accent-3"} -->
<p class="has-text-align-center has-accent-3-color has-text-color has-link-color" style="font-size:20px">The right dog for the right home — since 2007</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"24px","right":"24px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--60);padding-left:24px">

<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2.5rem)"},"spacing":{"margin":{"bottom":"24px"}}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:clamp(1.5rem, 3vw, 2.5rem);margin-bottom:24px">8,500+ dogs lovingly homed</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">Saving Great Animals is a matchmaking rescue organization focused mainly on dogs in the Greater Seattle area. We work tirelessly to match the best pet to your family based on breed, lifestyle, and other factors. With more than 8,500 lovingly homed since 2007, we are proud of our dedicated team and foster homes for bringing new life to pets with loving homes to last their lifetime.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">We adopt out only after a dog has been spayed or neutered, updated on shots, received proper medical care, and been chipped. We are dedicated to lowering the dog reproduction population, which leads to millions of lost lives.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">We use a trial adoption program, which includes training and counsel, and dogs are only adopted after that period. As a result, our return rates are very low. We love every single animal we rescue and we are cradle to grave, staying in touch with adopting families for years, often adding new furry loves to their homes.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">Adopters must sign a contract to ensure that if for some unforeseen reason the dog needs to be rehomed, he or she is returned to Saving Great Animals for rehoming. Our dogs are never to see a high-kill shelter again in their lifetime.</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"style":{"spacing":{"margin":{"top":"40px","bottom":"40px"}}}} -->
<hr class="wp-block-separator has-alpha-channel-opacity" style="margin-top:40px;margin-bottom:40px"/>
<!-- /wp:separator -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">We are a federally recognized 501(c)(3) nonprofit (EIN: 80-0323640), relying solely on adoption fees, donations, and grants. Join our active community on <a href="https://www.facebook.com/SGADogRescue/">Facebook</a> and <a href="https://www.instagram.com/savinggreatanimals/">Instagram</a>.</p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"sga/cta-section"} /-->
');

// ─── FOSTER ──────────────────────────────────────────────────
// Template already includes foster-hero pattern; post_content is the body below it
sga_update_page('foster', '
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|50","left":"0","right":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-right:0;padding-bottom:var(--wp--preset--spacing--50);padding-left:0">

<!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2.5rem)"},"spacing":{"margin":{"bottom":"24px"}}}} -->
<h2 class="wp-block-heading" style="font-size:clamp(1.5rem, 3vw, 2.5rem);margin-bottom:24px">Foster homes are desperately needed</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px"><strong>Foster families are needed now more than ever!</strong> Dogs in Washington state are in crisis. Shelters are experiencing massive overcrowding and rescues are operating far over capacity. Intakes at animal control facilities have reached an all-time high, while adoptions are at an all-time low. This leads to a sharp increase in euthanasia rates. Having access to foster homes means we can save lives!</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">All you need to be a foster is the ability to provide a home for a dog for a few weeks or more. We will match you with a dog suited to your preferences and provide guidance and support along the way. While the dog is staying with you, SGA takes care of any vet bills and you provide the food and bedding. <strong>Fostering does not cost you a penny!</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">Fostering can be done around your schedule! Many of our fosters work full time, so don\'t worry if your foster dog needs to hang out on their own during the day. If you travel or some weeks are more hectic than others, you can foster at your convenience.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">Often people worry that it will be hard to let their foster dog go. Some fosters do find it challenging, but there is nothing more rewarding than seeing a dog whose life would otherwise have ended tragically go home with a family full of joy.</p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"24px","right":"24px"}},"color":{"background":"#FFF5EB"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="background-color:#FFF5EB;padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--60);padding-left:24px">

<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2.2rem)"},"spacing":{"margin":{"bottom":"16px"}}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:clamp(1.5rem, 3vw, 2.2rem);margin-bottom:16px">Dogs Needing Foster Homes</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"18px"},"spacing":{"margin":{"bottom":"32px"}}}} -->
<p class="has-text-align-center" style="font-size:18px;margin-bottom:32px">These dogs are waiting for a foster family. Could it be you?</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[foster_dogs show_secured="no"]
<!-- /wp:shortcode -->

</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"24px","right":"24px"}},"color":{"background":"#F3F0EC"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="background-color:#F3F0EC;padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--60);padding-left:24px">

<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2.2rem)"},"spacing":{"margin":{"bottom":"32px"}}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:clamp(1.5rem, 3vw, 2.2rem);margin-bottom:32px">Frequently Asked Questions</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"20px"},"spacing":{"margin":{"top":"24px","bottom":"8px"}}}} -->
<h3 class="wp-block-heading" style="font-size:20px;margin-top:24px;margin-bottom:8px">What does a foster parent do?</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"17px"}}} -->
<p style="font-size:17px">Foster parenting is a wonderful way to enjoy the love and attention of a dog without making a permanent commitment. Because we don\'t have a facility, SGA\'s foster program is one of the most crucial elements of our rescue. By taking a dog into your home, you are allowing it to become accustomed to a safe and loving home life.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"20px"},"spacing":{"margin":{"top":"24px","bottom":"8px"}}}} -->
<h3 class="wp-block-heading" style="font-size:20px;margin-top:24px;margin-bottom:8px">Do I get to choose which dog I foster?</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"17px"}}} -->
<p style="font-size:17px">Yes! Whether you\'re looking for a mellow companion or an active ball-chasing dog you can take on jogs, our goal is to place a dog with you that fits your needs and lifestyle. We\'ll help determine which of our dogs fits that description.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"20px"},"spacing":{"margin":{"top":"24px","bottom":"8px"}}}} -->
<h3 class="wp-block-heading" style="font-size:20px;margin-top:24px;margin-bottom:8px">What if the dog doesn\'t work out for me?</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"17px"}}} -->
<p style="font-size:17px">If at any time the fit isn\'t working, we will be happy to take the dog back and try a different one. Just like people, dogs all have different personalities, and our goal is to help find the right match.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"20px"},"spacing":{"margin":{"top":"24px","bottom":"8px"}}}} -->
<h3 class="wp-block-heading" style="font-size:20px;margin-top:24px;margin-bottom:8px">Who pays for veterinary care?</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"17px"}}} -->
<p style="font-size:17px">SGA gives all foster parents a list of approved veterinarians and pays for all veterinary fees and inoculations for foster dogs.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"20px"},"spacing":{"margin":{"top":"24px","bottom":"8px"}}}} -->
<h3 class="wp-block-heading" style="font-size:20px;margin-top:24px;margin-bottom:8px">How will the dog find a permanent home while with me?</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"17px"}}} -->
<p style="font-size:17px">SGA requires the dog to be available for a meet-and-greet with approved applicants. Once an application is received, an adoption coordinator will connect you with the applicant to set up an appointment.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"20px"},"spacing":{"margin":{"top":"24px","bottom":"8px"}}}} -->
<h3 class="wp-block-heading" style="font-size:20px;margin-top:24px;margin-bottom:8px">I have kids — can I still foster?</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"17px"}}} -->
<p style="font-size:17px">Dogs who live with very young children need higher tolerance levels. Because we can\'t always guarantee tolerance with all ages, we can only approve foster families with children over 6 years of age.</p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"24px","right":"24px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--60);padding-left:24px">

<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2.2rem)"},"spacing":{"margin":{"bottom":"16px"}}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:clamp(1.5rem, 3vw, 2.2rem);margin-bottom:16px">Ready to start fostering?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"18px"},"spacing":{"margin":{"bottom":"24px"}}}} -->
<p class="has-text-align-center" style="font-size:18px;margin-bottom:24px">Fill out our application or email us with any questions at <a href="mailto:info@savinggreatanimals.org">info@savinggreatanimals.org</a></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"16px"}}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"accent-1","textColor":"accent-6","style":{"typography":{"fontSize":"18px","fontWeight":"700"},"border":{"radius":"6px"},"spacing":{"padding":{"top":"16px","bottom":"16px","left":"32px","right":"32px"}}}} -->
<div class="wp-block-button" style="font-size:18px;font-weight:700"><a class="wp-block-button__link has-accent-6-color has-accent-1-background-color has-text-color has-background wp-element-button" href="https://secure.savinggreatanimals.org" style="border-radius:6px;padding-top:16px;padding-right:32px;padding-bottom:16px;padding-left:32px">Apply to Foster</a></div>
<!-- /wp:button -->
<!-- wp:button {"backgroundColor":"contrast","textColor":"accent-6","style":{"typography":{"fontSize":"18px","fontWeight":"700"},"border":{"radius":"6px"},"spacing":{"padding":{"top":"16px","bottom":"16px","left":"32px","right":"32px"}}}} -->
<div class="wp-block-button" style="font-size:18px;font-weight:700"><a class="wp-block-button__link has-accent-6-color has-contrast-background-color has-text-color has-background wp-element-button" href="/dogs-needing-fosters/" style="border-radius:6px;padding-top:16px;padding-right:32px;padding-bottom:16px;padding-left:32px">See Dogs Needing Fosters</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

</div>
<!-- /wp:group -->
');

// ─── GET INVOLVED ────────────────────────────────────────────
sga_update_page('get-involved', '
<!-- wp:cover {"dimRatio":100,"overlayColor":"contrast","isDark":true,"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"24px","right":"24px"}},"dimensions":{"minHeight":"300px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-dark" style="padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--60);padding-left:24px;min-height:300px"><span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-100 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"clamp(2rem, 4vw, 3.5rem)"},"elements":{"link":{"color":{"text":"var:preset|color|accent-6"}}}},"textColor":"accent-6"} -->
<h1 class="wp-block-heading has-text-align-center has-accent-6-color has-text-color has-link-color" style="font-size:clamp(2rem, 4vw, 3.5rem)">Get Involved</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"20px"},"elements":{"link":{"color":{"text":"var:preset|color|accent-3"}}}},"textColor":"accent-3"} -->
<p class="has-text-align-center has-accent-3-color has-text-color has-link-color" style="font-size:20px">Every act of kindness makes a difference</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|50","left":"24px","right":"24px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--50);padding-left:24px">

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">We love volunteers, and there are many ways to help Saving Great Animals. We are always looking for qualified foster homes and volunteers to help with animal care and at special events. We could not do our work without your support.</p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"24px","right":"24px"}},"color":{"background":"#F3F0EC"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="background-color:#F3F0EC;padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--60);padding-left:24px">

<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2.2rem)"},"spacing":{"margin":{"bottom":"32px"}}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:clamp(1.5rem, 3vw, 2.2rem);margin-bottom:32px">Ways to Help</h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"32px"}}}} -->
<div class="wp-block-columns">
<!-- wp:column {"style":{"spacing":{"padding":{"top":"32px","right":"32px","bottom":"32px","left":"32px"}},"border":{"radius":"12px"},"color":{"background":"#ffffff"}}} -->
<div class="wp-block-column" style="border-radius:12px;background-color:#ffffff;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"22px"}}} -->
<h3 class="wp-block-heading" style="font-size:22px">Foster a Dog</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
<p style="font-size:16px">Open your home to a dog in need. SGA covers all vet bills — you provide the love. Foster for a few weeks or longer.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"accent-1","textColor":"accent-6","style":{"border":{"radius":"6px"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-accent-6-color has-accent-1-background-color has-text-color has-background wp-element-button" href="/foster/" style="border-radius:6px">Learn About Fostering</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"top":"32px","right":"32px","bottom":"32px","left":"32px"}},"border":{"radius":"12px"},"color":{"background":"#ffffff"}}} -->
<div class="wp-block-column" style="border-radius:12px;background-color:#ffffff;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"22px"}}} -->
<h3 class="wp-block-heading" style="font-size:22px">Donate</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
<p style="font-size:16px">Your tax-deductible donation supports medical care, housing, and behavioral support for dogs in our care.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"accent-1","textColor":"accent-6","style":{"border":{"radius":"6px"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-accent-6-color has-accent-1-background-color has-text-color has-background wp-element-button" href="/donate/" style="border-radius:6px">Donate Now</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"top":"32px","right":"32px","bottom":"32px","left":"32px"}},"border":{"radius":"12px"},"color":{"background":"#ffffff"}}} -->
<div class="wp-block-column" style="border-radius:12px;background-color:#ffffff;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"22px"}}} -->
<h3 class="wp-block-heading" style="font-size:22px">Volunteer</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
<p style="font-size:16px">Help with animal care, transport, events, and more. Every bit of time makes a difference for dogs in need.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"accent-1","textColor":"accent-6","style":{"border":{"radius":"6px"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-accent-6-color has-accent-1-background-color has-text-color has-background wp-element-button" href="mailto:info@savinggreatanimals.org" style="border-radius:6px">Contact Us</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"24px","right":"24px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--60);padding-left:24px">

<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.3rem, 2.5vw, 2rem)"},"spacing":{"margin":{"bottom":"24px"}}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:clamp(1.3rem, 2.5vw, 2rem);margin-bottom:24px">Other Ways to Support SGA</h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"32px"}}}} -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"18px"}}} -->
<h3 class="wp-block-heading" style="font-size:18px">Chewy Wish List</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
<p style="font-size:16px">Donate supplies from our <a href="https://www.chewy.com/g/saving-great-animals_b93206164#wish-list">Chewy wish list</a> — food, toys, and essentials shipped directly to our fosters.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"18px"}}} -->
<h3 class="wp-block-heading" style="font-size:18px">Donate Your Car</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
<p style="font-size:16px">Support SGA with a <a href="https://careasy.org/nonprofit/saving-great-animals">vehicle donation</a>. Easy to get started — call 855-500-7438.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"18px"}}} -->
<h3 class="wp-block-heading" style="font-size:18px">Bark Buddies</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
<p style="font-size:16px">Become a <a href="https://secure.savinggreatanimals.org/subscribe.jsp?subscription=6">monthly donor</a> and join our Bark Buddies circle — our most vital supporters.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->
');

// ─── DONATE ──────────────────────────────────────────────────
sga_update_page('donate', '
<!-- wp:cover {"dimRatio":100,"overlayColor":"contrast","isDark":true,"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"24px","right":"24px"}},"dimensions":{"minHeight":"300px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-dark" style="padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--60);padding-left:24px;min-height:300px"><span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-100 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"clamp(2rem, 4vw, 3.5rem)"},"elements":{"link":{"color":{"text":"var:preset|color|accent-6"}}}},"textColor":"accent-6"} -->
<h1 class="wp-block-heading has-text-align-center has-accent-6-color has-text-color has-link-color" style="font-size:clamp(2rem, 4vw, 3.5rem)">Support Our Mission</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"20px"},"elements":{"link":{"color":{"text":"var:preset|color|accent-3"}}}},"textColor":"accent-3"} -->
<p class="has-text-align-center has-accent-3-color has-text-color has-link-color" style="font-size:20px">Every dollar saves a life</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|50","left":"24px","right":"24px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--50);padding-left:24px">

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">Saving Great Animals operates as a federally recognized 501(c)(3) nonprofit (EIN: 80-0323640). Your tax-deductible donations support medical care, housing, and behavioral support for dogs in our care. Rescue needs never slow down and veterinary costs keep rising.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"32px","bottom":"32px"}}}} -->
<div class="wp-block-buttons" style="margin-top:32px;margin-bottom:32px">
<!-- wp:button {"backgroundColor":"accent-1","textColor":"accent-6","style":{"typography":{"fontSize":"20px","fontWeight":"700"},"border":{"radius":"6px"},"spacing":{"padding":{"top":"18px","bottom":"18px","left":"40px","right":"40px"}}}} -->
<div class="wp-block-button" style="font-size:20px;font-weight:700"><a class="wp-block-button__link has-accent-6-color has-accent-1-background-color has-text-color has-background wp-element-button" href="https://secure.savinggreatanimals.org/forms/donate" style="border-radius:6px;padding-top:18px;padding-right:40px;padding-bottom:18px;padding-left:40px">Donate Now</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"24px","right":"24px"}},"color":{"background":"#FFF5EB"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="background-color:#FFF5EB;padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--60);padding-left:24px">

<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2.2rem)"},"spacing":{"margin":{"bottom":"16px"}}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:clamp(1.5rem, 3vw, 2.2rem);margin-bottom:16px">Become a Bark Buddy</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"18px"},"spacing":{"margin":{"bottom":"24px"}}}} -->
<p class="has-text-align-center" style="font-size:18px;margin-bottom:24px">Make a lasting difference by becoming a monthly donor. Your recurring gift provides year-round, lifesaving care for dogs who need us most. Monthly donors join our Bark Buddies circle — our most vital supporters.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"contrast","textColor":"accent-6","style":{"typography":{"fontSize":"18px","fontWeight":"700"},"border":{"radius":"6px"},"spacing":{"padding":{"top":"16px","bottom":"16px","left":"32px","right":"32px"}}}} -->
<div class="wp-block-button" style="font-size:18px;font-weight:700"><a class="wp-block-button__link has-accent-6-color has-contrast-background-color has-text-color has-background wp-element-button" href="https://secure.savinggreatanimals.org/subscribe.jsp?subscription=6" style="border-radius:6px;padding-top:16px;padding-right:32px;padding-bottom:16px;padding-left:32px">Join Bark Buddies</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"24px","right":"24px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--60);padding-left:24px">

<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.3rem, 2.5vw, 2rem)"},"spacing":{"margin":{"bottom":"24px"}}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:clamp(1.3rem, 2.5vw, 2rem);margin-bottom:24px">Other Ways to Give</h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"32px"}}}} -->
<div class="wp-block-columns">
<!-- wp:column {"style":{"spacing":{"padding":{"top":"24px","right":"24px","bottom":"24px","left":"24px"}},"border":{"radius":"12px"},"color":{"background":"#F3F0EC"}}} -->
<div class="wp-block-column" style="border-radius:12px;background-color:#F3F0EC;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"20px"}}} -->
<h3 class="wp-block-heading" style="font-size:20px">Chewy Wish List</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
<p style="font-size:16px">Donate food, supplies, and essentials from our <a href="https://www.chewy.com/g/saving-great-animals_b93206164#wish-list">Chewy wish list</a>, shipped directly to fosters.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"style":{"spacing":{"padding":{"top":"24px","right":"24px","bottom":"24px","left":"24px"}},"border":{"radius":"12px"},"color":{"background":"#F3F0EC"}}} -->
<div class="wp-block-column" style="border-radius:12px;background-color:#F3F0EC;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"20px"}}} -->
<h3 class="wp-block-heading" style="font-size:20px">Donate Your Car</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
<p style="font-size:16px">Support SGA with a <a href="https://careasy.org/nonprofit/saving-great-animals">vehicle donation</a>. Easy to get started — call 855-500-7438.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"style":{"spacing":{"padding":{"top":"24px","right":"24px","bottom":"24px","left":"24px"}},"border":{"radius":"12px"},"color":{"background":"#F3F0EC"}}} -->
<div class="wp-block-column" style="border-radius:12px;background-color:#F3F0EC;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"20px"}}} -->
<h3 class="wp-block-heading" style="font-size:20px">Shop for SGA</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
<p style="font-size:16px">Visit our <a href="https://sga-shop.fourthwall.com">merch store</a> — proceeds support rescue operations.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->
');

// ─── SURRENDER ───────────────────────────────────────────────
sga_update_page('surrender', '
<!-- wp:cover {"dimRatio":100,"overlayColor":"contrast","isDark":true,"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"24px","right":"24px"}},"dimensions":{"minHeight":"300px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-dark" style="padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--60);padding-left:24px;min-height:300px"><span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-100 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"clamp(2rem, 4vw, 3.5rem)"},"elements":{"link":{"color":{"text":"var:preset|color|accent-6"}}}},"textColor":"accent-6"} -->
<h1 class="wp-block-heading has-text-align-center has-accent-6-color has-text-color has-link-color" style="font-size:clamp(2rem, 4vw, 3.5rem)">Surrender a Dog</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"20px"},"elements":{"link":{"color":{"text":"var:preset|color|accent-3"}}}},"textColor":"accent-3"} -->
<p class="has-text-align-center has-accent-3-color has-text-color has-link-color" style="font-size:20px">We are here to help find your dog a loving home</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|50","left":"24px","right":"24px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--50);padding-left:24px">

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">If you are no longer able to care for your dog and need help finding him or her another home, please contact us. We will do our best to assist you.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">In order to ensure that your dog goes to a loving and suitable home, we complete reference checks and home checks for all applicants. If you wish, you can be closely involved in the rehoming process.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">Please contact <a href="mailto:jacintha@savinggreatanimals.org">jacintha@savinggreatanimals.org</a> for more information on surrendering your dog.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"},"elements":{"link":{"color":{"text":"var:preset|color|accent-1"}}}}} -->
<p style="font-size:18px"><em>We may not be able to help in every case. We do not accept animals with aggression issues towards other animals or humans.</em> Before we accept your dog, our Intake Coordinator will visit you for an evaluation.</p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"24px","right":"24px"}},"color":{"background":"#F3F0EC"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="background-color:#F3F0EC;padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--60);padding-left:24px">

<!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2.2rem)"},"spacing":{"margin":{"bottom":"24px"}}}} -->
<h2 class="wp-block-heading" style="font-size:clamp(1.5rem, 3vw, 2.2rem);margin-bottom:24px">How to Get Ready</h2>
<!-- /wp:heading -->

<!-- wp:list {"ordered":true,"style":{"typography":{"fontSize":"18px"},"spacing":{"blockGap":"12px"}}} -->
<ol style="font-size:18px" class="wp-block-list">
<!-- wp:list-item -->
<li>Gather up all medical records.</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li>If your dog is not up to date on vaccinations, please get that taken care of first.</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li>Take some good pictures of your dog in clear daylight — face and full body.</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li>Create a summary of your dog\'s behavior. Include everything — the good, the bad, and the ugly.</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li>Contact us at <a href="mailto:jacintha@savinggreatanimals.org">jacintha@savinggreatanimals.org</a> to begin the process.</li>
<!-- /wp:list-item -->
</ol>
<!-- /wp:list -->

</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"24px","right":"24px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--60);padding-left:24px">

<!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.3rem, 2.5vw, 1.8rem)"},"spacing":{"margin":{"bottom":"16px"}}}} -->
<h2 class="wp-block-heading" style="font-size:clamp(1.3rem, 2.5vw, 1.8rem);margin-bottom:16px">Alternative Resource</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">If we are unable to assist due to volume or behavior concerns, you may consider <a href="https://getyourpet.com">GetYourPet.com</a> where you can list your dog yourself. Review their <a href="https://getyourpet.com/tips-for-guardians/">tips for rehoming</a> to ensure your pet finds the right home.</p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
');

// ─── RESOURCES ───────────────────────────────────────────────
sga_update_page('resources', '
<!-- wp:cover {"dimRatio":100,"overlayColor":"contrast","isDark":true,"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"24px","right":"24px"}},"dimensions":{"minHeight":"300px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-dark" style="padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--60);padding-left:24px;min-height:300px"><span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-100 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"clamp(2rem, 4vw, 3.5rem)"},"elements":{"link":{"color":{"text":"var:preset|color|accent-6"}}}},"textColor":"accent-6"} -->
<h1 class="wp-block-heading has-text-align-center has-accent-6-color has-text-color has-link-color" style="font-size:clamp(2rem, 4vw, 3.5rem)">Resources</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"20px"},"elements":{"link":{"color":{"text":"var:preset|color|accent-3"}}}},"textColor":"accent-3"} -->
<p class="has-text-align-center has-accent-3-color has-text-color has-link-color" style="font-size:20px">Helpful tools and information for dog owners</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|50","left":"24px","right":"24px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--50);padding-left:24px">

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">As dog people, there are things we love that make life as dog owners a little easier. From training to supplies to end-of-life resources, here are some recommendations from our team.</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"style":{"spacing":{"margin":{"top":"32px","bottom":"32px"}}}} -->
<hr class="wp-block-separator has-alpha-channel-opacity" style="margin-top:32px;margin-bottom:32px"/>
<!-- /wp:separator -->

<!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.3rem, 2.5vw, 2rem)"},"spacing":{"margin":{"bottom":"16px"}}}} -->
<h2 class="wp-block-heading" style="font-size:clamp(1.3rem, 2.5vw, 2rem);margin-bottom:16px">Food and Supplies</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">Visit <a href="https://www.chewy.com">Chewy.com</a> for a wide array of food, supplies, and medications — all shipped to your home. You can also donate from our <a href="https://www.chewy.com/g/saving-great-animals_b93206164#wish-list">Chewy wish list</a> to support dogs in our care.</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"style":{"spacing":{"margin":{"top":"32px","bottom":"32px"}}}} -->
<hr class="wp-block-separator has-alpha-channel-opacity" style="margin-top:32px;margin-bottom:32px"/>
<!-- /wp:separator -->

<!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.3rem, 2.5vw, 2rem)"},"spacing":{"margin":{"bottom":"16px"}}}} -->
<h2 class="wp-block-heading" style="font-size:clamp(1.3rem, 2.5vw, 2rem);margin-bottom:16px">End of Life Resources</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px"><strong>CodaPet</strong> offers compassionate in-home euthanasia services:</p>
<!-- /wp:paragraph -->

<!-- wp:list {"style":{"typography":{"fontSize":"18px"},"spacing":{"blockGap":"8px"}}} -->
<ul style="font-size:18px" class="wp-block-list">
<!-- wp:list-item -->
<li><a href="https://www.codapet.com/cities/bellevue-wa">In-home pet euthanasia in Bellevue</a> — A compassionate service that walks families through the process</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="https://www.codapet.com/quality-of-life-scale">Quality-of-life scale questionnaire</a> — Helps assess your pet\'s well-being</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="https://www.codapet.com/grief-counselors">Pet loss grief counselors</a> — Emotional support for families during difficult times</li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->

</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"sga/cta-section"} /-->
');

// ─── EVENTS ──────────────────────────────────────────────────
// The Events Calendar plugin handles the event listing via its own template system.
// We add a minimal page hero and let the plugin render events below.
sga_update_page('events', '
<!-- wp:cover {"dimRatio":100,"overlayColor":"contrast","isDark":true,"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"24px","right":"24px"}},"dimensions":{"minHeight":"300px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-dark" style="padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--60);padding-left:24px;min-height:300px"><span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-100 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"clamp(2rem, 4vw, 3.5rem)"},"elements":{"link":{"color":{"text":"var:preset|color|accent-6"}}}},"textColor":"accent-6"} -->
<h1 class="wp-block-heading has-text-align-center has-accent-6-color has-text-color has-link-color" style="font-size:clamp(2rem, 4vw, 3.5rem)">Events</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"20px"},"elements":{"link":{"color":{"text":"var:preset|color|accent-3"}}}},"textColor":"accent-3"} -->
<p class="has-text-align-center has-accent-3-color has-text-color has-link-color" style="font-size:20px">Join us at upcoming SGA events</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"24px","right":"24px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-right:24px;padding-bottom:var(--wp--preset--spacing--60);padding-left:24px">

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">Check out our upcoming events below. Our biggest fundraiser of the year is <strong>The Bark Benefit</strong> — an evening of live and silent auction items, rescue stories, and more. All funds raised go directly to supporting the hundreds of rescue dogs we receive each year.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">Want to volunteer at an event? Contact us at <a href="mailto:info@savinggreatanimals.org">info@savinggreatanimals.org</a>.</p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"sga/cta-section"} /-->
');

echo "=== Page content populated ===\n";
