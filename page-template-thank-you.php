<?php
/*
Template Name: Thank You Page
*/

get_header();

$title = get_the_title();

$mail2_body = '';
$thank_you_text = '';

$page_slug = get_post_field('post_name', get_the_ID());

$form_map = [
        'dziekujemy-wroclaw'  => 26382, // Open Day Form WRO
        'dziekujemy-warszawa' => 26494, // Open Day Form Warszawa
];

$form_id = $form_map[$page_slug] ?? 0;

if ($form_id && function_exists('wpcf7_contact_form')) {
    $contact_form = wpcf7_contact_form($form_id);

    if ($contact_form) {
        $properties = $contact_form->get_properties();

        if (!empty($properties['mail_2']['body'])) {
            $mail2_body = trim((string) $properties['mail_2']['body']);
        }
    }
}

if (!empty($mail2_body)) {
    if (preg_match('/<!--START-->(.*?)<!--END-->/s', $mail2_body, $matches) && !empty($matches[1])) {
        $thank_you_text = trim($matches[1]);
    } else {
        $parts = preg_split(
                '/Ta wiadomość e-mail jest potwierdzeniem wysłania formularza kontaktowego/i',
                $mail2_body
        );

        if (!empty($parts[0])) {
            $thank_you_text = trim($parts[0]);
        }
    }
}

if (empty($thank_you_text)) {
    $thank_you_text = ' ';
}
?>

    <section class="thank-you-page">
        <div class="container">
            <div class="thank-you-page__inner row">
                <div class="thank-you-page__content col-lg-6 col-12">
                    <div class="thank-you-page__content-inner">
                        <h1 class="thank-you-page__title">
                            <?php echo esc_html($title); ?>
                        </h1>

                        <div class="thank-you-page__text">
                            <?php echo wpautop(esc_html($thank_you_text)); ?>
                        </div>
                    </div>
                </div>

                <div class="thank-you-page__media col-lg-6 col-12">
                    <div class="thank-you-page__image-box">
                        <?php
                        if (has_post_thumbnail()) {
                            echo get_the_post_thumbnail(
                                    get_the_ID(),
                                    'full',
                                    [
                                            'class' => 'thank-you-page__image',
                                            'alt'   => $title,
                                    ]
                            );
                        } else {
                            ?>
                            <img
                                    class="thank-you-page__image"
                                    src="<?php echo esc_url(get_template_directory_uri() . '/static/img/dzien-otwarty-banner-wro.webp'); ?>"
                                    alt="<?php echo esc_attr($title); ?>"
                            >
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php get_footer(); ?>