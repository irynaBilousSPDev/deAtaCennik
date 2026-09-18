<?php
/**
 * Archive: Szkolenia.
 *
 * @package akademiata
 */
get_header();

$current_lang = apply_filters('wpml_current_language', null);
$base_page = get_page_by_path('szkolenia');
$page_id = $base_page ? apply_filters('wpml_object_id', $base_page->ID, 'page', true, $current_lang) : 0;
$acf_page = $page_id ? get_post($page_id) : null;
$acf_content = $acf_page ? apply_filters('the_content', $acf_page->post_content) : '';
$acf_title = $acf_page ? get_the_title($acf_page->ID) : __('Szkolenia', 'akademiata');
?>

<section class="section_szkolenia">
	<div class="container py-5">
		<div class="section_header text-center mb-4">
			<h1><?php echo esc_html($acf_title); ?></h1>
			<?php if (!empty($acf_content)) : ?>
				<div class="page-description"><?php echo $acf_content; ?></div>
			<?php endif; ?>
		</div>

		<?php if (have_posts()) : ?>
			<div class="row">
				<?php while (have_posts()) : the_post(); ?>
					<div class="col-md-6 col-lg-4 mb-4">
						<a class="d-block" href="<?php the_permalink(); ?>">
							<?php if (has_post_thumbnail()) : ?>
								<?php the_post_thumbnail('medium_large', array( 'class' => 'img-fluid mb-2' )); ?>
							<?php endif; ?>
							<h2 class="h5"><?php the_title(); ?></h2>
						</a>
					</div>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e('Nie znaleziono szkoleń.', 'akademiata'); ?></p>
		<?php endif; ?>
	</div>
</section>

<?php get_footer(); ?>
