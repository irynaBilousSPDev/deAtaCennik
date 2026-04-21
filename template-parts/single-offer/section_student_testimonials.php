<?php
$post_id = get_the_ID();
$category = 'program';
$youtube_acf_field = 'id_youtube_playlist_testimonials';
$acf_fields = get_query_var('acf_fields', []);
$student_testimonials = !empty($acf_fields['student_testimonials']) ? $acf_fields['student_testimonials'] : [];
$youtube_playlist = !empty($student_testimonials['id_youtube_playlist']) ? $student_testimonials['id_youtube_playlist'] : '';

$youtube_playlist_id = get_youtube_playlist_id($post_id, $category, $youtube_acf_field, $youtube_playlist);

if (!empty($youtube_playlist_id)) :  // Fixed incorrect variable reference
    $title = !empty($student_testimonials['title']) ? $student_testimonials['title'] : __('Dlaczego warto słowami naszych studentów', 'akademiata');
    ?>
    <section class="section_student_testimonials left_space pb-md-5 mb-5">
        <div class="container">
            <h2 class="sub_title primary_color py-3 mb-5">
                <?php echo esc_html($title); ?>
            </h2>
            <?php
            // Pass YouTube playlist ID to template
            set_query_var('data_youtube_playlist', esc_attr($youtube_playlist_id));

            // Load YouTube Slider Template
            get_template_part('template-parts/youtube_slider');
            ?>
        </div>
    </section>
<?php endif; ?>