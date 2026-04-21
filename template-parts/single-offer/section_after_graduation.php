<?php
$post_id = get_the_ID();
$category = 'program';
$youtube_acf_field = 'id_youtube_playlist_graduation';
$acf_fields = get_query_var('acf_fields', []);
$after_graduation = $acf_fields['after_graduation'] ?? [];
$youtube_playlist = !empty($after_graduation['id_youtube_playlist']) ? $after_graduation['id_youtube_playlist'] : '';

$youtube_playlist_id = get_youtube_playlist_id($post_id, $category, $youtube_acf_field, $youtube_playlist);

// Check if section data exists before rendering
if (!empty($youtube_playlist_id)) :
    $title = $after_graduation['title'];
    $title = $title ? $title : __('Jakie plany po studiach mają nasi studenci', 'akademiata');
    ?>
    <section class="section_after_graduation left_space gray_arrows pb-md-5 mb-5">
        <div class="container">
            <h2 class="sub_title_section mb-5">
                <?php echo $title; ?>
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

