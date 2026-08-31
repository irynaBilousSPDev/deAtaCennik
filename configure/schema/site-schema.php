<?php
/**
 * Site-wide JSON-LD dispatcher — homepage, pages, news, archives.
 *
 * Runs at priority 19 (offer CPT modules at 20).
 *
 * @package akademiata
 */

function akademiata_output_site_schema() {
    if (is_admin() || is_feed() || akademiata_schema_is_offer_single_view()) {
        return;
    }

    if (is_front_page()) {
        akademiata_output_homepage_schema();
        return;
    }

    if (is_singular('post')) {
        akademiata_output_news_schema();
        return;
    }

    if (akademiata_schema_is_news_archive_view()) {
        akademiata_output_news_schema();
        return;
    }

    if (is_singular('page')) {
        akademiata_output_page_schema();
        return;
    }

    akademiata_output_archive_schema();
}

add_action('wp_head', 'akademiata_output_site_schema', 19);
