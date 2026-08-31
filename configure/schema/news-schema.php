<?php
/**
 * JSON-LD NewsArticle + news archive CollectionPage.
 *
 * @package akademiata
 */

/**
 * @param int $post_id
 * @return array<string, mixed>|null
 */
function akademiata_build_news_article_schema($post_id) {
    $post_id = (int) $post_id;
    if (!akademiata_schema_is_news_post($post_id)) {
        return null;
    }

    $base = akademiata_schema_published_post_base($post_id);
    if ($base === null) {
        return null;
    }

    $permalink = user_trailingslashit($base['permalink']);
    $home      = home_url('/');
    $org       = akademiata_get_schema_organization();

    $publisher = array(
        '@type' => 'Organization',
        '@id'   => $org['@id'],
        'name'  => $org['name'],
    );
    if (!empty($org['logo'])) {
        $publisher['logo'] = $org['logo'];
    }

    $schema = array(
        '@context'         => 'https://schema.org',
        '@type'            => 'NewsArticle',
        '@id'              => $permalink . '#article',
        'headline'         => akademiata_schema_clean_text($base['title']),
        'url'              => $permalink,
        'mainEntityOfPage' => array(
            '@type' => 'WebPage',
            '@id'   => $permalink . '#webpage',
        ),
        'datePublished'    => get_the_date('c', $post_id),
        'dateModified'     => get_the_modified_date('c', $post_id),
        'author'           => array('@id' => $org['@id']),
        'publisher'        => $publisher,
        'isPartOf'         => array('@id' => $home . '#website'),
        'inLanguage'       => akademiata_schema_current_language_code(),
    );

    $description = akademiata_schema_page_description($post_id);
    if ($description !== '') {
        $schema['description'] = $description;
    }

    $image = get_the_post_thumbnail_url($post_id, 'full');
    if ($image) {
        $schema['image'] = array($image);
    }

    if (function_exists('akademiata_get_post_news_city_label')) {
        $city_label = akademiata_schema_clean_text(akademiata_get_post_news_city_label($post_id));
        if ($city_label !== '') {
            $schema['contentLocation'] = array(
                '@type' => 'Place',
                'name'  => $city_label,
            );
        }
    }

    return $schema;
}

/**
 * @param int $post_id
 * @return array<string, mixed>|null
 */
function akademiata_build_generic_post_schema($post_id) {
    $post_id = (int) $post_id;
    if (get_post_type($post_id) !== 'post' || akademiata_schema_is_news_post($post_id)) {
        return null;
    }

    $base = akademiata_schema_published_post_base($post_id);
    if ($base === null) {
        return null;
    }

    $schema = akademiata_schema_build_webpage(
        $base['permalink'],
        $base['title'],
        akademiata_schema_page_description($post_id),
        array(
            '@type' => 'Article',
        )
    );

    $published = get_the_date('c', $post_id);
    $modified  = get_the_modified_date('c', $post_id);
    if ($published) {
        $schema['datePublished'] = $published;
    }
    if ($modified) {
        $schema['dateModified'] = $modified;
    }

    $image = get_the_post_thumbnail_url($post_id, 'full');
    if ($image) {
        $schema['image'] = $image;
    }

    return $schema;
}

/**
 * @return array<string, mixed>|null
 */
function akademiata_build_news_archive_schema() {
    if (!akademiata_schema_is_news_archive_view()) {
        return null;
    }

    $url   = akademiata_schema_current_canonical_url();
    $title = akademiata_schema_get_archive_heading();

    if ($title === '' && is_page()) {
        $title = get_the_title();
    }
    if ($title === '') {
        $title = __('Aktualności', 'akademiata');
    }

    $description = '';
    if (is_page()) {
        $description = akademiata_schema_page_description((int) get_queried_object_id());
    }

    $posts     = akademiata_schema_query_news_archive_posts(12);
    $item_list = akademiata_schema_build_item_list(
        $posts,
        $title,
        $url
    );

    return akademiata_schema_build_collection_page($url, $title, $description, $item_list);
}

function akademiata_output_news_schema() {
    if (is_admin()) {
        return;
    }

    if (is_singular('post')) {
        if (akademiata_schema_is_news_post()) {
            akademiata_schema_output_json_ld(
                akademiata_build_news_article_schema((int) get_queried_object_id())
            );
            return;
        }

        akademiata_schema_output_json_ld(
            akademiata_build_generic_post_schema((int) get_queried_object_id())
        );
        return;
    }

    if (akademiata_schema_is_news_archive_view()) {
        akademiata_schema_output_json_ld(akademiata_build_news_archive_schema());
    }
}
