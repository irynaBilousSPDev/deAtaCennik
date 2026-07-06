<?php
$data_youtube_playlist = get_query_var('data_youtube_playlist', '');
if (empty($data_youtube_playlist)) {
    return;
}

$youtube_items = akademiata_get_youtube_playlist_items($data_youtube_playlist);
?>
<div class="youtube-slider"
     data-youtube-playlist="<?php echo esc_attr($data_youtube_playlist); ?>"
     <?php echo !empty($youtube_items) ? ' data-ssr="1"' : ''; ?>>
    <?php
    if (!empty($youtube_items)) {
        foreach ($youtube_items as $item) {
            if (is_array($item)) {
                akademiata_render_youtube_slide($item);
            }
        }
    }
    ?>
</div>
