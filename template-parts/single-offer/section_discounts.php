<?php

$post_id = get_the_ID();
$taxonomies = ['program', 'degree', 'city'];
$current_slugs = [];

foreach ($taxonomies as $taxonomy) {
    $terms = get_the_terms($post_id, $taxonomy);
    if (!empty($terms) && !is_wp_error($terms)) {
        $current_slugs[$taxonomy] = wp_list_pluck($terms, 'slug');
    }
}

if (empty($current_slugs['program'])) {
    return '';
}

$tax_query = [
    [
        'taxonomy' => 'program',
        'field' => 'slug',
        'terms' => $current_slugs['program'],
        'operator' => 'IN',
    ]
];

if (!empty($current_slugs['degree'])) {
    $tax_query[] = [
        'taxonomy' => 'degree',
        'field' => 'slug',
        'terms' => $current_slugs['degree'],
        'operator' => 'IN',
    ];
}

if (!empty($current_slugs['city'])) {
    $tax_query[] = [
        'taxonomy' => 'city',
        'field' => 'slug',
        'terms' => $current_slugs['city'],
        'operator' => 'IN',
    ];
}

$query = new WP_Query([
    'post_type' => 'discounts',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'tax_query' => [
        'relation' => 'AND',
        ...$tax_query
    ]
]);

if (!$query->have_posts()) {
    return '';
}
?>

<section id="discounts" class="section_discounts">
    <div class="container">
        <h2 class="title_section mb-5"><?php esc_html_e('Zniżki, promocje, stypendia', 'akademiata'); ?></h2>

        <div class="discounts">
            <?php foreach ($query->posts as $discount_post) :
                $title = get_the_title($discount_post->ID);
                $description = get_field('description', $discount_post->ID) ?: '';
                $entry_fee_content = get_field('entry_fee_content', $discount_post->ID);

                // Optional: separate long-form content for the popup (fallback to $description)
                $popup_content = get_field('more_description', $discount_post->ID);
                ?>
                <div>
                    <div class="discount_card">
                        <div class="discount_card_body w-100">
                            <h3 class="small_title mb-5"><?php echo esc_html($title); ?></h3>

                            <?php if (!empty($entry_fee_content)) : ?>
                                <div class="discount_number title_section primary_color">
                                    <?php echo wp_kses_post(preg_replace('/<\/?p[^>]*>/', '', $entry_fee_content)); ?>
                                </div>
                            <?php endif; ?>

                            <div class="content">
                                <?php echo wp_kses_post($description); ?>
                            </div>
                        </div>

                        <!-- Arrow button (opens modal) -->
                        <?php if (!empty(trim($popup_content))) : ?>
                            <div class="discount_card_button">
                                <button type="button"
                                        class="open-discount-modal"
                                        aria-haspopup="dialog"
                                        aria-controls="discountModal"
                                        aria-label="<?php echo esc_attr(sprintf(__('Open details: %s', 'akademiata'), $title)); ?>">
                                </button>
                            </div>

                            <!-- Hidden modal content (cloned into global modal on click) -->
                            <div class="js-modal-content" hidden>
                                <h3 class="modal_title"><?php echo esc_html($title); ?></h3>
                                <div class="modal_content">
                                    <?php echo wp_kses_post($popup_content); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Global modal shell (one per page) -->
    <div id="discountModal" class="discount-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="discount-modal__backdrop" data-close-modal></div>
        <div class="discount-modal__dialog" role="document">
            <button type="button" class="discount-modal__close" aria-label="<?php esc_attr_e('Close', 'akademiata'); ?>"
                    data-close-modal></button>
            <div class="discount-modal__body"><!-- content injected here --></div>
        </div>
    </div>
</section>

