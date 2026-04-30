<?php
/* Template for single exams (Exams) */

$acf_fields = get_fields();
set_query_var('acf_fields', $acf_fields);

$is_mobile = wp_is_mobile();

$register_url = !empty($acf_fields['register_url']) ? $acf_fields['register_url'] : '';
$subtitle = get_field('exam_subtitle');

// If the exam date taxonomy marks registration as closed, hide the signup button.
$is_registration_closed = false;
$exam_date_terms = get_the_terms(get_the_ID(), 'exam_date');
if (!empty($exam_date_terms) && !is_wp_error($exam_date_terms)) {
	foreach ($exam_date_terms as $t) {
		if (isset($t->slug) && $t->slug === 'rejestracja-na-egzamin-zamknieta') {
			$is_registration_closed = true;
			break;
		}
	}
}
// Top taxonomy (same header structure as courses)
$top_taxonomies_with_labels = [
    'exam_city' => __('MIASTO', 'akademiata'),
];

if (!function_exists('render_exam_taxonomy_details')) {
    function render_exam_taxonomy_details($taxonomies_with_labels)
    {
        echo '<div class="offer_details_wrapper">';

        foreach ($taxonomies_with_labels as $taxonomy => $label) {
            $terms = get_the_terms(get_the_ID(), $taxonomy);

            if (!empty($terms) && !is_wp_error($terms)) {
                echo '<div class="taxonomy_info">';
                echo '<div class="row">';
                echo '<div class="col-5 col-md-4 item">' . esc_html($label) . ':</div>';
                echo '<div class="col-7 col-md-8 item">';

                $term_names = array_map('esc_html', wp_list_pluck($terms, 'name'));
                echo implode('<br>', $term_names);

                echo '</div></div></div>';
            }
        }

        echo '</div>';
    }
}
?>

<section class="section_header right_space mb-5">
    <div class="container">

        <?php if ($is_mobile) : ?>
            <div class="offer_header my-3 mobile_visible">
                <?php the_breadcrumb(); ?>
                <div class="top_details">
                    <div class="row">
                        <?php render_taxonomy_info($top_taxonomies_with_labels); ?>
                    </div>
                </div>
                <?php if ($subtitle) : ?>
                    <div class="exam-subtitle">
                        <?php echo $subtitle; ?>
                    </div>
                <?php endif; ?>
                <div class="main_title">
                    <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="offer_wrapper d-flex flex-column-reverse flex-lg-row-reverse">
            <div class="col-lg-6">
                <div class="offer_body">

                    <?php if (!$is_mobile) : ?>
                        <div class="offer_header my-3 desktop_visible">
                            <?php the_breadcrumb(); ?>
                            <div class="top_details">
                                <div class="row">
                                    <?php render_taxonomy_info($top_taxonomies_with_labels); ?>
                                </div>
                            </div>
                            <?php if ($subtitle) : ?>
                                <div class="exam-subtitle">
                                    <?php echo $subtitle; ?>
                                </div>
                            <?php endif; ?>
                            <div class="main_title">
                                <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="offer_details_wrapper">
                        <?php
                        $taxonomies_with_labels = [
                            'exam_price' => __('CENA', 'akademiata'),
                            'exam_date' => __('TERMIN EGZAMINU', 'akademiata'),
                            'exam_location' => __('LOKALIZACJA', 'akademiata'),
                        ];

                        render_exam_taxonomy_details($taxonomies_with_labels);
                        ?>
                    </div>

                    <?php if (!empty($register_url) && !$is_registration_closed) : ?>
                        <a id="sourceLink"
                           href="<?= esc_url($register_url); ?>"
                           target="_blank"
                           class="button-sing_up"><?php _e('ZAPISZ SIĘ', 'akademiata'); ?></a>
                    <?php endif; ?>


                    <?php
                    // Optional: keep partners block if you want the same module here
                    $offer_partners = $acf_fields['offer_partners'] ?? [];
                    set_query_var('offer_partners', $offer_partners);
                    locate_template('template-parts/single-offer/offer_partners.php', true, true);
                    ?>
                </div>
            </div>

            <div class="col-lg-6">
                <?php if (has_post_thumbnail()) :
                    $thumbnail_id = get_post_thumbnail_id(get_the_ID());
                    $desktop_size = 'program_banner';
                    $mobile_size = 'specialization_card_thumb';

                    $image_url_mobile = wp_get_attachment_image_src($thumbnail_id, $mobile_size)[0] ?? '';
                    $image_url_desktop = wp_get_attachment_image_src($thumbnail_id, $desktop_size)[0] ?? '';
                    ?>
                    <div class="image_bg responsive-image" role="img"
                         data-mobile="<?= esc_url($image_url_mobile); ?>"
                         data-desktop="<?= esc_url($image_url_desktop); ?>"
                         style="background-image: url('<?= esc_url($image_url_desktop); ?>');">
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>
<?php
$rows = get_field('single_exam_content', get_the_ID());

if ($rows) : ?>
    <div class="single-exam-content my-5">
        <div class="container">
            <?php foreach ($rows as $row) : ?>
                <?php if (($row['acf_fc_layout'] ?? '') === 'section') : ?>
                    <?php
                    $title = $row['section_title'] ?? '';
                    $subtitle = $row['section_subtitle'] ?? '';
                    $text = $row['section_text'] ?? '';
                    $list = $row['section_list'] ?? [];
                    $note_normal = $row['section_note_normal'] ?? '';
                    $note = $row['section_note'] ?? '';
                    $note_highlight = !empty($row['section_note_highlight']);
                    ?>

                    <section class="exam-section my-5">
                        <?php if ($title) : ?>
                            <h2 class="exam-section__title title_section"><?php echo esc_html($title); ?></h2>
                        <?php endif; ?>

                        <?php if ($subtitle) : ?>
                            <h3 class="exam-section__subtitle sub_title_section mb-5"><?php echo esc_html($subtitle); ?></h3>
                        <?php endif; ?>

                        <?php if ($text) : ?>
                            <div class="exam-section__text wysiwyg">
                                <?php echo wp_kses_post($text); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($note) : ?>
                            <div class="exam-note <?php echo $note_highlight ? 'is-highlight' : ''; ?>">
                                <?php echo $note; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($note_normal) : ?>
                            <div class="exam-note">
                                <?php echo $note_normal; ?>
                            </div>
                        <?php endif; ?>

                    </section>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

