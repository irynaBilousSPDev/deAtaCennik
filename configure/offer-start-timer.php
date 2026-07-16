<?php

if (!defined('ABSPATH')) {
	exit;
}

const AKADEMIATA_OFFER_START_TIMER_OPTION = 'akademiata_offer_start_timer_settings';
const AKADEMIATA_OFFER_START_TIMER_WPML_CONTEXT = 'akademiata-offer-start-timer';

/**
 * @return array{
 *   enabled: bool,
 *   countdown_target: string,
 *   active_from: string,
 *   active_until: string,
 *   pairs: array<int, array{top: string, bottom: string}>
 * }
 */
function akademiata_offer_start_timer_default_settings() {
	return array(
		'enabled'          => true,
		'countdown_target' => '2026-10-01',
		'active_from'      => '',
		'active_until'     => '2026-10-01',
		'pairs'            => array(
			array('top' => 'start', 'bottom' => 'studiów'),
			array('top' => 'pierwszego', 'bottom' => 'października'),
			array('top' => 'zarezerwuj', 'bottom' => 'swoje miejsce'),
			array('top' => 'liczba miejsc', 'bottom' => 'ograniczona'),
		),
	);
}

/**
 * @param string $ymd
 * @return string
 */
function akademiata_offer_start_timer_sanitize_ymd($ymd) {
	$ymd = sanitize_text_field((string) $ymd);
	if ($ymd === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
		return '';
	}

	return $ymd;
}

/**
 * @param array<string, mixed> $input
 * @return array{
 *   enabled: bool,
 *   countdown_target: string,
 *   active_from: string,
 *   active_until: string,
 *   pairs: array<int, array{top: string, bottom: string}>
 * }
 */
function akademiata_offer_start_timer_sanitize_settings($input) {
	$defaults = akademiata_offer_start_timer_default_settings();
	$input    = is_array($input) ? $input : array();

	$pairs_in = isset($input['pairs']) && is_array($input['pairs']) ? $input['pairs'] : array();
	$pairs    = array();

	for ($i = 0; $i < 4; $i++) {
		$row = isset($pairs_in[ $i ]) && is_array($pairs_in[ $i ]) ? $pairs_in[ $i ] : array();
		$top = isset($row['top']) ? sanitize_text_field((string) $row['top']) : '';
		$bottom = isset($row['bottom']) ? sanitize_text_field((string) $row['bottom']) : '';
		if ($top === '' && isset($defaults['pairs'][ $i ]['top'])) {
			$top = $defaults['pairs'][ $i ]['top'];
		}
		if ($bottom === '' && isset($defaults['pairs'][ $i ]['bottom'])) {
			$bottom = $defaults['pairs'][ $i ]['bottom'];
		}
		$pairs[] = array(
			'top'    => $top,
			'bottom' => $bottom,
		);
	}

	$countdown_target = akademiata_offer_start_timer_sanitize_ymd($input['countdown_target'] ?? '');
	if ($countdown_target === '') {
		$countdown_target = $defaults['countdown_target'];
	}

	return array(
		'enabled'          => !empty($input['enabled']),
		'countdown_target' => $countdown_target,
		'active_from'      => akademiata_offer_start_timer_sanitize_ymd($input['active_from'] ?? ''),
		'active_until'     => akademiata_offer_start_timer_sanitize_ymd($input['active_until'] ?? ''),
		'pairs'            => $pairs,
	);
}

/**
 * @return array{
 *   enabled: bool,
 *   countdown_target: string,
 *   active_from: string,
 *   active_until: string,
 *   pairs: array<int, array{top: string, bottom: string}>
 * }
 */
function akademiata_offer_start_timer_get_settings() {
	$saved = get_option(AKADEMIATA_OFFER_START_TIMER_OPTION, null);
	if ($saved === null) {
		return akademiata_offer_start_timer_default_settings();
	}

	return akademiata_offer_start_timer_sanitize_settings(is_array($saved) ? $saved : array());
}

/**
 * Register phrase strings for WPML String Translation.
 *
 * @param array<int, array{top: string, bottom: string}> $pairs
 */
function akademiata_offer_start_timer_register_wpml_strings(array $pairs) {
	foreach ($pairs as $i => $pair) {
		$n = $i + 1;
		do_action(
			'wpml_register_single_string',
			AKADEMIATA_OFFER_START_TIMER_WPML_CONTEXT,
			'pair_' . $n . '_top',
			(string) ($pair['top'] ?? '')
		);
		do_action(
			'wpml_register_single_string',
			AKADEMIATA_OFFER_START_TIMER_WPML_CONTEXT,
			'pair_' . $n . '_bottom',
			(string) ($pair['bottom'] ?? '')
		);
	}
}

/**
 * @param string $value
 * @param string $name
 * @return string
 */
function akademiata_offer_start_timer_translate_string($value, $name) {
	$value = (string) $value;
	if ($value === '') {
		return '';
	}

	$translated = apply_filters(
		'wpml_translate_single_string',
		$value,
		AKADEMIATA_OFFER_START_TIMER_WPML_CONTEXT,
		$name
	);

	return is_string($translated) && $translated !== '' ? $translated : $value;
}

/**
 * Resolved phrase pairs for current language (WPML + theme fallbacks).
 *
 * @return array<int, array{0: string, 1: string}>
 */
function akademiata_offer_start_timer_get_pairs_for_display() {
	$settings = akademiata_offer_start_timer_get_settings();
	$pairs    = $settings['pairs'];
	$out      = array();

	foreach ($pairs as $i => $pair) {
		$n      = $i + 1;
		$top    = akademiata_offer_start_timer_translate_string((string) ($pair['top'] ?? ''), 'pair_' . $n . '_top');
		$bottom = akademiata_offer_start_timer_translate_string((string) ($pair['bottom'] ?? ''), 'pair_' . $n . '_bottom');

		// Fallback to built-in theme strings when WPML has no translation yet.
			$lang = function_exists('akademiata_normalize_theme_lang_code')
			? akademiata_normalize_theme_lang_code((string) apply_filters('wpml_current_language', null))
			: 'pl';

		if ($lang !== 'pl' && $top === (string) ($pair['top'] ?? '') && function_exists('akademiata_get_theme_lang_string')) {
			$fallback = akademiata_get_theme_lang_string('offer_start_timer_pair_' . $n . '_top');
			if ($fallback !== '') {
				$top = $fallback;
			}
		}
		if ($lang !== 'pl' && $bottom === (string) ($pair['bottom'] ?? '') && function_exists('akademiata_get_theme_lang_string')) {
			$fallback = akademiata_get_theme_lang_string('offer_start_timer_pair_' . $n . '_bottom');
			if ($fallback !== '') {
				$bottom = $fallback;
			}
		}

		if ($top === '' && $bottom === '') {
			continue;
		}

		$out[] = array($top, $bottom);
	}

	return $out;
}

/**
 * @return bool
 */
function akademiata_offer_start_timer_should_show() {
	if (!is_singular(array('bachelor', 'master'))) {
		return false;
	}

	$settings = akademiata_offer_start_timer_get_settings();
	if (empty($settings['enabled'])) {
		return false;
	}

	$today = wp_date('Y-m-d');
	$from  = $settings['active_from'];
	$until = $settings['active_until'];
	$target = $settings['countdown_target'];

	if ($from !== '' && $today < $from) {
		return false;
	}

	if ($until !== '' && $today > $until) {
		return false;
	}

	// After countdown target day, hide (unless active_until extends visibility — still hide if target passed and until empty).
	if ($target !== '' && $today > $target) {
		return false;
	}

	$pairs = akademiata_offer_start_timer_get_pairs_for_display();

	return !empty($pairs);
}

/**
 * Unix timestamp of countdown target (local TZ midnight).
 *
 * @return int
 */
function akademiata_offer_start_timer_countdown_ts() {
	$settings = akademiata_offer_start_timer_get_settings();
	$target   = $settings['countdown_target'];
	if ($target === '') {
		return 0;
	}

	$timezone = wp_timezone();
	$dt       = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $target . ' 00:00:00', $timezone);

	return $dt ? $dt->getTimestamp() : 0;
}

function akademiata_offer_start_timer_register_admin_menu() {
	add_submenu_page(
		'theme-general-settings',
		__('Timer startu studiów', 'akademiata'),
		__('Timer startu studiów', 'akademiata'),
		'manage_options',
		'akademiata-offer-start-timer',
		'akademiata_offer_start_timer_render_admin_page'
	);
}

function akademiata_offer_start_timer_persist_settings_errors() {
	$errors = get_settings_errors();
	if ($errors) {
		set_transient('settings_errors', $errors, 30);
	}
}

function akademiata_offer_start_timer_handle_save_settings() {
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('Brak uprawnień.', 'akademiata'));
	}

	check_admin_referer('akademiata_offer_start_timer_save_settings', 'akademiata_offer_start_timer_nonce');

	$raw_input = isset($_POST[ AKADEMIATA_OFFER_START_TIMER_OPTION ]) && is_array($_POST[ AKADEMIATA_OFFER_START_TIMER_OPTION ])
		? wp_unslash($_POST[ AKADEMIATA_OFFER_START_TIMER_OPTION ])
		: array();

	$sanitized = akademiata_offer_start_timer_sanitize_settings($raw_input);
	update_option(AKADEMIATA_OFFER_START_TIMER_OPTION, $sanitized);
	akademiata_offer_start_timer_register_wpml_strings($sanitized['pairs']);

	add_settings_error(
		AKADEMIATA_OFFER_START_TIMER_OPTION,
		'akademiata_offer_start_timer_saved',
		__('Ustawienia timera zostały zapisane. Teksty zarejestrowano w WPML String Translation (kontekst: akademiata-offer-start-timer).', 'akademiata'),
		'success'
	);

	akademiata_offer_start_timer_persist_settings_errors();
	wp_safe_redirect(admin_url('admin.php?page=akademiata-offer-start-timer&settings-updated=true'));
	exit;
}

/**
 * @param string $ymd
 * @return string
 */
function akademiata_offer_start_timer_format_admin_date($ymd) {
	$ymd = trim($ymd);
	if ($ymd === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
		return $ymd;
	}

	$timestamp = strtotime($ymd . ' 00:00:00');

	return $timestamp ? wp_date('j.m.Y', $timestamp) : $ymd;
}

function akademiata_offer_start_timer_admin_status_label() {
	$settings = akademiata_offer_start_timer_get_settings();

	if (empty($settings['enabled'])) {
		return __('Wyłączony. Timer jest ukryty na ofertach.', 'akademiata');
	}

	$today = wp_date('Y-m-d');
	$from  = $settings['active_from'];
	$until = $settings['active_until'];
	$target = $settings['countdown_target'];

	if ($from !== '' && $today < $from) {
		return sprintf(
			__('Włączony. Widoczny od %s.', 'akademiata'),
			akademiata_offer_start_timer_format_admin_date($from)
		);
	}

	if ($until !== '' && $today > $until) {
		return sprintf(
			__('Włączony, ale data „Pokaż do” (%s) już minęła — timer ukryty.', 'akademiata'),
			akademiata_offer_start_timer_format_admin_date($until)
		);
	}

	if ($target !== '' && $today > $target) {
		return sprintf(
			__('Włączony, ale data odliczania (%s) już minęła — timer ukryty.', 'akademiata'),
			akademiata_offer_start_timer_format_admin_date($target)
		);
	}

	return sprintf(
		__('Działa na bachelor/master. Odliczanie do %s.', 'akademiata'),
		akademiata_offer_start_timer_format_admin_date($target)
	);
}

function akademiata_offer_start_timer_render_admin_page() {
	if (!current_user_can('manage_options')) {
		return;
	}

	$settings = akademiata_offer_start_timer_get_settings();
	$option   = AKADEMIATA_OFFER_START_TIMER_OPTION;
	?>
	<div class="wrap">
		<h1><?php esc_html_e('Timer startu studiów — oferty', 'akademiata'); ?></h1>
		<p>
			<?php esc_html_e('Kompaktowy timer na stronach bachelor/master. Teksty PL edytujesz tutaj; tłumaczenia EN/UK/RU — WPML → String Translation (kontekst akademiata-offer-start-timer) albo domyślne stringi motywu.', 'akademiata'); ?>
		</p>

		<?php settings_errors($option); ?>

		<div class="notice notice-info inline" style="margin: 1em 0 1.5em; padding: 12px;">
			<p><strong><?php esc_html_e('Status teraz:', 'akademiata'); ?></strong> <?php echo esc_html(akademiata_offer_start_timer_admin_status_label()); ?></p>
		</div>

		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<?php wp_nonce_field('akademiata_offer_start_timer_save_settings', 'akademiata_offer_start_timer_nonce'); ?>
			<input type="hidden" name="action" value="akademiata_offer_start_timer_save_settings">

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e('Timer na stronie', 'akademiata'); ?></th>
					<td>
						<label>
							<input
								type="checkbox"
								name="<?php echo esc_attr($option); ?>[enabled]"
								value="1"
								<?php checked(!empty($settings['enabled'])); ?>>
							<?php esc_html_e('Włącz timer na specjalnościach (bachelor/master)', 'akademiata'); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="akademiata_ost_countdown_target">
							<?php esc_html_e('Data odliczania (cel)', 'akademiata'); ?>
						</label>
					</th>
					<td>
						<input
							type="date"
							class="regular-text"
							id="akademiata_ost_countdown_target"
							name="<?php echo esc_attr($option); ?>[countdown_target]"
							value="<?php echo esc_attr($settings['countdown_target']); ?>"
							required>
						<p class="description">
							<?php esc_html_e('Do tej daty (00:00) liczy się countdown. Po jej upływie timer znika.', 'akademiata'); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="akademiata_ost_active_from">
							<?php esc_html_e('Pokaż od dnia', 'akademiata'); ?>
						</label>
					</th>
					<td>
						<input
							type="date"
							class="regular-text"
							id="akademiata_ost_active_from"
							name="<?php echo esc_attr($option); ?>[active_from]"
							value="<?php echo esc_attr($settings['active_from']); ?>">
						<p class="description">
							<?php esc_html_e('Opcjonalnie. Puste — pokazuje od razu po włączeniu.', 'akademiata'); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="akademiata_ost_active_until">
							<?php esc_html_e('Pokaż do dnia', 'akademiata'); ?>
						</label>
					</th>
					<td>
						<input
							type="date"
							class="regular-text"
							id="akademiata_ost_active_until"
							name="<?php echo esc_attr($option); ?>[active_until]"
							value="<?php echo esc_attr($settings['active_until']); ?>">
						<p class="description">
							<?php esc_html_e('Opcjonalnie. Po tej dacie timer się nie pokazuje (nawet jeśli countdown jeszcze trwa).', 'akademiata'); ?>
						</p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e('Teksty (2 linie × 4 frazy w pętli)', 'akademiata'); ?></h2>
			<p class="description" style="margin-bottom: 1em;">
				<?php esc_html_e('Góra / dół wokół czarnego pilla. Po zapisie stringi idą do WPML String Translation.', 'akademiata'); ?>
			</p>

			<table class="widefat striped" style="max-width: 720px;">
				<thead>
					<tr>
						<th style="width: 3rem;">#</th>
						<th><?php esc_html_e('Linia górna', 'akademiata'); ?></th>
						<th><?php esc_html_e('Linia dolna', 'akademiata'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($settings['pairs'] as $i => $pair) : ?>
						<tr>
							<td><?php echo esc_html((string) ($i + 1)); ?></td>
							<td>
								<input
									type="text"
									class="regular-text"
									name="<?php echo esc_attr($option); ?>[pairs][<?php echo esc_attr((string) $i); ?>][top]"
									value="<?php echo esc_attr($pair['top']); ?>">
							</td>
							<td>
								<input
									type="text"
									class="regular-text"
									name="<?php echo esc_attr($option); ?>[pairs][<?php echo esc_attr((string) $i); ?>][bottom]"
									value="<?php echo esc_attr($pair['bottom']); ?>">
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php submit_button(__('Zapisz ustawienia', 'akademiata')); ?>
		</form>
	</div>
	<?php
}

/**
 * Ensure WPML sees current option strings.
 */
function akademiata_offer_start_timer_boot_wpml_strings() {
	if (!is_admin() && !did_action('wpml_loaded') && !has_action('wpml_register_single_string')) {
		return;
	}
	$settings = akademiata_offer_start_timer_get_settings();
	akademiata_offer_start_timer_register_wpml_strings($settings['pairs']);
}

add_action('admin_menu', 'akademiata_offer_start_timer_register_admin_menu', 100);
add_action('admin_post_akademiata_offer_start_timer_save_settings', 'akademiata_offer_start_timer_handle_save_settings');
add_action('init', 'akademiata_offer_start_timer_boot_wpml_strings', 20);
