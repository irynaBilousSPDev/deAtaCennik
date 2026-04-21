<?php
$data_youtube_playlist = get_query_var('data_youtube_playlist', '');
if (!empty($data_youtube_playlist)): ?>
    <div class="youtube-slider" data-youtube-playlist="<?php echo esc_attr($data_youtube_playlist); ?>">
        <!-- Videos will be added dynamically here -->
    </div>
<?php endif; ?>
