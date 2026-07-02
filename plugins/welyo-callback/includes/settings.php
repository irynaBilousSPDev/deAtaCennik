<?php
/**
 * Ustawienia wtyczki Welyo Callback (globalne + per język WPML).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WELYO_OPTION_KEY' ) ) {
	define( 'WELYO_OPTION_KEY', 'welyo_callback_settings' );
}

/** Obsługiwane języki (kod WPML → etykieta). */
function welyo_supported_languages() {
	$langs = array(
		'pl' => 'Polski',
		'en' => 'English',
		'uk' => 'Українська',
		'ru' => 'Русский',
	);
	return apply_filters( 'welyo_callback_supported_languages', $langs );
}

/** Klucze ustawień globalnych (wspólne dla wszystkich języków). */
function welyo_global_setting_keys() {
	return array(
		'base_url', 'login', 'api_key', 'hash_method', 'auto_footer', 'enabled_languages', 'settings_version',
		'forminator_integration_enabled',
		'color_brand', 'color_brand_hover', 'color_brand_dark', 'color_accent', 'color_accent_hover',
		'color_text', 'color_text_muted', 'color_border', 'color_panel_bg', 'color_launcher_text',
		'color_status_open', 'color_status_closed', 'color_success', 'color_footer_text',
		'color_input_bg', 'color_disabled',
	);
}

/** Klucze ustawień per język. */
function welyo_lang_setting_keys() {
	return array(
		'campaign_id', 'classifier_id', 'campaign_name', 'classifier_name',
		'forminator_enabled', 'forminator_form_id',
		'forminator_quiz_campaign_id', 'forminator_quiz_campaign_name',
		'forminator_quiz_classifier_id', 'forminator_quiz_classifier_name',
		'forminator_field_name', 'forminator_field_phone', 'forminator_field_email',
		'forminator_field_consent', 'forminator_field_quiz_result',
		'phone_dial', 'phone_pretty', 'default_prefix', 'privacy_url',
		'open_hour', 'close_hour', 'workdays',
		'text_status_open', 'text_status_closed', 'text_title_open', 'text_title_closed',
		'text_sub_open', 'text_sub_closed', 'text_launch_open', 'text_launch_closed',
		'text_call_btn', 'text_name_label', 'text_name_placeholder', 'text_phone_label',
		'text_phone_placeholder', 'text_consent', 'text_submit', 'text_done_title',
		'text_done_scheduled', 'text_done_immediate', 'text_footer', 'text_hours_prefix',
		'text_error_phone', 'text_error_consent', 'text_error_auth', 'text_error_campaign',
		'text_error_welyo', 'text_error_rate', 'text_error_nonce', 'text_error_generic', 'text_sending',
	);
}

/** Domyślne ustawienia globalne. */
function welyo_default_global_settings() {
	return array(
		'base_url'            => 'https://ataedu.welyo.pl/external-api',
		'login'               => 'login@ataedu',
		'api_key'             => '',
		'hash_method'         => 'md5',
		'auto_footer'         => 1,
		'enabled_languages'   => array( 'pl' ),
		'settings_version'    => 3,
		'forminator_integration_enabled' => 1,
		'color_brand'         => '#2a3a86',
		'color_brand_hover'   => '#3650c8',
		'color_brand_dark'    => '#1a2766',
		'color_accent'        => '#ff5a3c',
		'color_accent_hover'  => '#e8421f',
		'color_text'          => '#1b2347',
		'color_text_muted'    => '#5b6385',
		'color_border'        => '#e6e9f2',
		'color_panel_bg'      => '#ffffff',
		'color_launcher_text' => '#ffffff',
		'color_status_open'   => '#46e08a',
		'color_status_closed' => '#ffc24b',
		'color_success'       => '#1f9d63',
		'color_footer_text'   => '#9aa1ba',
		'color_input_bg'      => '#fbfcfe',
		'color_disabled'      => '#c7ccdd',
	);
}

/** Domyślne ustawienia per język (teksty, telefon, kampania). */
function welyo_default_lang_settings( $lang ) {
	$lang = strtolower( (string) $lang );
	$base = array(
		'campaign_id'     => '',
		'classifier_id'   => '',
		'campaign_name'   => '',
		'classifier_name' => '',
		'forminator_enabled'                  => 0,
		'forminator_form_id'                  => '',
		'forminator_quiz_campaign_id'         => '',
		'forminator_quiz_campaign_name'       => '',
		'forminator_quiz_classifier_id'       => '',
		'forminator_quiz_classifier_name'     => '',
		'forminator_field_name'               => '',
		'forminator_field_phone'              => '',
		'forminator_field_email'              => '',
		'forminator_field_consent'            => '',
		'forminator_field_quiz_result'        => '',
		'open_hour'       => 8,
		'close_hour'      => 18,
		'workdays'        => '1,2,3,4,5',
		'default_prefix'  => '+48',
	);

	$packs = array(
		'pl' => array(
			'phone_dial'             => '+48228258034',
			'phone_pretty'           => '+48 22 825 80 34',
			'privacy_url'            => '/polityka-prywatnosci/',
			'campaign_name'          => 'Rekrutacja - formularz WWW (callback)',
			'forminator_quiz_campaign_name' => 'Rekrutacja - quiz Forminator WWW',
			'text_status_open'       => 'Jesteśmy teraz dostępni',
			'text_status_closed'     => 'Jesteśmy już po godzinach',
			'text_title_open'        => 'Masz pytanie?',
			'text_title_closed'      => 'Zostaw numer',
			'text_sub_open'          => 'Zadzwoń do działu rekrutacji — pomożemy dokończyć zgłoszenie.',
			'text_sub_closed'        => 'Jesteśmy już po godzinach. Zostaw numer — oddzwonimy najszybciej, jak to możliwe.',
			'text_launch_open'       => 'Masz pytanie? Zadzwoń',
			'text_launch_closed'     => 'Masz pytanie? Oddzwonimy',
			'text_call_btn'          => 'Zadzwoń teraz',
			'text_name_label'        => 'Imię',
			'text_name_placeholder'  => 'Jak się do Ciebie zwracać?',
			'text_phone_label'       => 'Numer telefonu',
			'text_phone_placeholder' => 'np. 600 100 200',
			'text_consent'           => 'Wyrażam zgodę na kontakt telefoniczny w sprawie mojej rekrutacji. Rozmowa może być nagrywana w celach jakościowych. <a href="{privacy_url}" target="_blank" rel="noopener">Informacja o przetwarzaniu danych</a>.',
			'text_submit'            => 'Oddzwońcie do mnie',
			'text_done_title'        => 'Dziękujemy!',
			'text_done_scheduled'    => 'Mamy Twój numer. Oddzwonimy najszybciej, jak to możliwe.',
			'text_done_immediate'    => 'Mamy Twój numer. Oddzwaniamy teraz — odbierz proszę połączenie.',
			'text_footer'            => 'Dział Rekrutacji',
			'text_hours_prefix'      => 'Pon–Pt, ',
			'text_error_phone'       => 'Podaj poprawny numer telefonu.',
			'text_error_consent'     => 'Potrzebujemy zgody na kontakt telefoniczny.',
			'text_error_auth'        => 'Błąd logowania do Welyo. Administrator: sprawdź login i klucz API.',
			'text_error_campaign'    => 'Nie znaleziono kampanii Welyo. Administrator: sprawdź nazwę lub ID kampanii.',
			'text_error_welyo'       => 'Welyo odrzuciło zgłoszenie. Spróbuj później lub zadzwoń do nas.',
			'text_error_rate'        => 'Zbyt wiele prób. Odczekaj kilka minut i spróbuj ponownie.',
			'text_error_nonce'       => 'Sesja wygasła. Odśwież stronę i spróbuj ponownie.',
			'text_error_generic'     => 'Coś poszło nie tak. Spróbuj ponownie lub zadzwoń do nas.',
			'text_sending'           => 'Wysyłanie…',
		),
		'en' => array(
			'phone_dial'             => '+48713331107',
			'phone_pretty'           => '+48 71 333 11 07',
			'privacy_url'            => '/en/privacy-policy/',
			'campaign_name'          => 'Recruitment - website form (callback)',
			'forminator_quiz_campaign_name' => 'Recruitment - Forminator quiz WWW',
			'text_status_open'       => 'We are available now',
			'text_status_closed'     => 'We are closed for today',
			'text_title_open'        => 'Have a question?',
			'text_title_closed'      => 'Leave your number',
			'text_sub_open'          => 'Call our admissions team — we will help you complete your application.',
			'text_sub_closed'        => 'We are closed. Leave your number and we will call you back as soon as possible.',
			'text_launch_open'       => 'Have a question? Call us',
			'text_launch_closed'     => 'Have a question? We will call back',
			'text_call_btn'          => 'Call now',
			'text_name_label'        => 'First name',
			'text_name_placeholder'  => 'How should we address you?',
			'text_phone_label'       => 'Phone number',
			'text_phone_placeholder' => 'e.g. 600 100 200',
			'text_consent'           => 'I agree to be contacted by phone regarding my application. Calls may be recorded for quality purposes. <a href="{privacy_url}" target="_blank" rel="noopener">Privacy information</a>.',
			'text_submit'            => 'Call me back',
			'text_done_title'        => 'Thank you!',
			'text_done_scheduled'    => 'We have your number. We will call you back as soon as possible.',
			'text_done_immediate'    => 'We have your number. We are calling now — please answer.',
			'text_footer'            => 'Admissions Office',
			'text_hours_prefix'      => 'Mon–Fri, ',
			'text_error_phone'       => 'Please enter a valid phone number.',
			'text_error_consent'     => 'We need your consent to contact you by phone.',
			'text_error_auth'        => 'Welyo login error. Administrator: check login and API key.',
			'text_error_campaign'    => 'Welyo campaign not found. Administrator: check campaign name or ID.',
			'text_error_welyo'       => 'Welyo rejected the request. Please try again later or call us.',
			'text_error_rate'        => 'Too many attempts. Please wait a few minutes and try again.',
			'text_error_nonce'       => 'Session expired. Refresh the page and try again.',
			'text_error_generic'     => 'Something went wrong. Please try again or call us.',
			'text_sending'           => 'Sending…',
		),
		'uk' => array(
			'phone_dial'             => '+48713331118',
			'phone_pretty'           => '+48 71 333 11 18',
			'privacy_url'            => '/uk/polityka-konfidentsijnosti/',
			'campaign_name'          => 'Рекрутація - форма WWW (callback)',
			'forminator_quiz_campaign_name' => 'Рекрутація - quiz Forminator WWW',
			'text_status_open'       => 'Ми зараз на зв\'язку',
			'text_status_closed'     => 'Ми вже поза робочими годинами',
			'text_title_open'        => 'Маєте запитання?',
			'text_title_closed'      => 'Залиште номер',
			'text_sub_open'          => 'Зателефонуйте до відділу рекрутації — допоможемо завершити подання.',
			'text_sub_closed'        => 'Ми поза робочими годинами. Залиште номер — передзвонимо якнайшвидше.',
			'text_launch_open'       => 'Маєте запитання? Зателефонуйте',
			'text_launch_closed'     => 'Маєте запитання? Передзвонимо',
			'text_call_btn'          => 'Зателефонувати зараз',
			'text_name_label'        => 'Ім\'я',
			'text_name_placeholder'  => 'Як до вас звертатися?',
			'text_phone_label'       => 'Номер телефону',
			'text_phone_placeholder' => 'напр. 600 100 200',
			'text_consent'           => 'Я погоджуюся на телефонний контакт щодо моєї рекрутації. Розмову може бути записано для контролю якості. <a href="{privacy_url}" target="_blank" rel="noopener">Інформація про обробку даних</a>.',
			'text_submit'            => 'Передзвоніть мені',
			'text_done_title'        => 'Дякуємо!',
			'text_done_scheduled'    => 'Ми отримали ваш номер. Передзвонимо якнайшвидше.',
			'text_done_immediate'    => 'Ми отримали ваш номер. Телефонуємо зараз — будь ласка, відповідайте.',
			'text_footer'            => 'Відділ рекрутації',
			'text_hours_prefix'      => 'Пн–Пт, ',
			'text_error_phone'       => 'Введіть правильний номер телефону.',
			'text_error_consent'     => 'Потрібна згода на телефонний контакт.',
			'text_error_auth'        => 'Помилка входу до Welyo. Адміністратор: перевірте логін і ключ API.',
			'text_error_campaign'    => 'Кампанію Welyo не знайдено. Адміністратор: перевірте назву або ID.',
			'text_error_welyo'       => 'Welyo відхилило заявку. Спробуйте пізніше або зателефонуйте.',
			'text_error_rate'        => 'Забагато спроб. Зачекайте кілька хвилин і спробуйте знову.',
			'text_error_nonce'       => 'Сесія закінчилася. Оновіть сторінку і спробуйте знову.',
			'text_error_generic'     => 'Щось пішло не так. Спробуйте знову або зателефонуйте.',
			'text_sending'           => 'Надсилання…',
		),
		'ru' => array(
			'phone_dial'             => '+48713331118',
			'phone_pretty'           => '+48 71 333 11 18',
			'privacy_url'            => '/ru/politika-konfidentsialnosti/',
			'campaign_name'          => 'Рекрутинг - форма WWW (callback)',
			'forminator_quiz_campaign_name' => 'Рекрутинг - quiz Forminator WWW',
			'text_status_open'       => 'Мы сейчас на связи',
			'text_status_closed'     => 'Мы уже вне рабочих часов',
			'text_title_open'        => 'Есть вопрос?',
			'text_title_closed'      => 'Оставьте номер',
			'text_sub_open'          => 'Позвоните в отдел рекрутинга — поможем завершить подачу документов.',
			'text_sub_closed'        => 'Мы вне рабочих часов. Оставьте номер — перезвоним как можно скорее.',
			'text_launch_open'       => 'Есть вопрос? Позвоните',
			'text_launch_closed'     => 'Есть вопрос? Перезвоним',
			'text_call_btn'          => 'Позвонить сейчас',
			'text_name_label'        => 'Имя',
			'text_name_placeholder'  => 'Как к вам обращаться?',
			'text_phone_label'       => 'Номер телефона',
			'text_phone_placeholder' => 'напр. 600 100 200',
			'text_consent'           => 'Я соглашаюсь на телефонный контакт по поводу моей рекрутации. Разговор может быть записан для контроля качества. <a href="{privacy_url}" target="_blank" rel="noopener">Информация об обработке данных</a>.',
			'text_submit'            => 'Перезвоните мне',
			'text_done_title'        => 'Спасибо!',
			'text_done_scheduled'    => 'Мы получили ваш номер. Перезвоним как можно скорее.',
			'text_done_immediate'    => 'Мы получили ваш номер. Звоним сейчас — пожалуйста, ответьте.',
			'text_footer'            => 'Отдел рекрутинга',
			'text_hours_prefix'      => 'Пн–Пт, ',
			'text_error_phone'       => 'Введите правильный номер телефона.',
			'text_error_consent'     => 'Нужно согласие на телефонный контакт.',
			'text_error_auth'        => 'Ошибка входа в Welyo. Администратор: проверьте логин и ключ API.',
			'text_error_campaign'    => 'Кампания Welyo не найдена. Администратор: проверьте название или ID.',
			'text_error_welyo'       => 'Welyo отклонило заявку. Попробуйте позже или позвоните нам.',
			'text_error_rate'        => 'Слишком много попыток. Подождите несколько минут и попробуйте снова.',
			'text_error_nonce'       => 'Сессия истекла. Обновите страницу и попробуйте снова.',
			'text_error_generic'     => 'Что-то пошло не так. Попробуйте снова или позвоните нам.',
			'text_sending'           => 'Отправка…',
		),
	);

	$pack = isset( $packs[ $lang ] ) ? $packs[ $lang ] : $packs['pl'];
	return array_merge( $base, $pack );
}

/** @deprecated Użyj welyo_default_global_settings() + welyo_default_lang_settings(). */
function welyo_default_settings() {
	$out = welyo_default_global_settings();
	foreach ( welyo_default_lang_settings( 'pl' ) as $key => $val ) {
		$out[ $key ] = $val;
	}
	return $out;
}

/** Pola kolorów widgetu (klucz opcji → etykieta). */
function welyo_color_fields() {
	return array(
		'color_brand'         => array(
			'label' => __( 'Kolor główny (marka)', 'akademiata' ),
			'desc'  => __( 'Przycisk pływający, nagłówek panelu', 'akademiata' ),
		),
		'color_brand_hover'   => array(
			'label' => __( 'Marka — hover / linki', 'akademiata' ),
			'desc'  => __( 'Najazd na przycisk, linki w zgodzie', 'akademiata' ),
		),
		'color_brand_dark'    => array(
			'label' => __( 'Marka — ciemny (gradient)', 'akademiata' ),
			'desc'  => __( 'Drugi kolor tła nagłówka panelu', 'akademiata' ),
		),
		'color_accent'        => array(
			'label' => __( 'Kolor akcentu (CTA)', 'akademiata' ),
			'desc'  => __( '„Zadzwoń”, „Oddzwońcie do mnie”', 'akademiata' ),
		),
		'color_accent_hover'  => array(
			'label' => __( 'Akcent — hover / błędy', 'akademiata' ),
			'desc'  => __( 'Najazd na CTA, komunikaty błędów', 'akademiata' ),
		),
		'color_text'          => array(
			'label' => __( 'Tekst główny', 'akademiata' ),
		),
		'color_text_muted'    => array(
			'label' => __( 'Tekst drugorzędny', 'akademiata' ),
			'desc'  => __( 'Podtytuły, zgoda, godziny', 'akademiata' ),
		),
		'color_border'        => array(
			'label' => __( 'Obramowania', 'akademiata' ),
		),
		'color_panel_bg'      => array(
			'label' => __( 'Tło panelu', 'akademiata' ),
		),
		'color_launcher_text' => array(
			'label' => __( 'Tekst na przycisku pływającym', 'akademiata' ),
		),
		'color_status_open'   => array(
			'label' => __( 'Status: otwarte', 'akademiata' ),
			'desc'  => __( 'Kropka „jesteśmy dostępni”', 'akademiata' ),
		),
		'color_status_closed' => array(
			'label' => __( 'Status: zamknięte', 'akademiata' ),
			'desc'  => __( 'Kropka po godzinach', 'akademiata' ),
		),
		'color_success'       => array(
			'label' => __( 'Sukces (ptaszek)', 'akademiata' ),
		),
		'color_footer_text'   => array(
			'label' => __( 'Stopka panelu', 'akademiata' ),
		),
		'color_input_bg'      => array(
			'label' => __( 'Tło pól formularza', 'akademiata' ),
		),
		'color_disabled'      => array(
			'label' => __( 'Przycisk wyłączony', 'akademiata' ),
		),
	);
}

function welyo_sanitize_color( $value, $fallback ) {
	$color = sanitize_hex_color( is_string( $value ) ? $value : '' );
	return $color ? strtolower( $color ) : $fallback;
}

function welyo_normalize_hex( $hex ) {
	$hex = welyo_sanitize_color( $hex, '' );
	if ( $hex === '' ) {
		return '';
	}
	if ( strlen( $hex ) === 4 ) {
		$hex = '#' . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
	}
	return $hex;
}

function welyo_hex_rgb( $hex ) {
	$hex = welyo_normalize_hex( $hex );
	if ( $hex === '' ) {
		return '0,0,0';
	}
	return hexdec( substr( $hex, 1, 2 ) ) . ',' . hexdec( substr( $hex, 3, 2 ) ) . ',' . hexdec( substr( $hex, 5, 2 ) );
}

function welyo_widget_colors() {
	$defaults = welyo_default_global_settings();
	$out      = array();
	foreach ( array_keys( welyo_color_fields() ) as $key ) {
		$out[ $key ] = welyo_sanitize_color( welyo_cfg( $key ), $defaults[ $key ] );
	}
	return $out;
}

function welyo_widget_color_style_attr() {
	$c           = welyo_widget_colors();
	$open_rgb    = welyo_hex_rgb( $c['color_status_open'] );
	$closed_rgb  = welyo_hex_rgb( $c['color_status_closed'] );
	$success_rgb = welyo_hex_rgb( $c['color_success'] );
	$brand_rgb   = welyo_hex_rgb( $c['color_brand'] );
	$hover_rgb   = welyo_hex_rgb( $c['color_brand_hover'] );

	$vars = array(
		'--b'               => $c['color_brand'],
		'--b2'              => $c['color_brand_hover'],
		'--bd'              => $c['color_brand_dark'],
		'--a'               => $c['color_accent'],
		'--ad'              => $c['color_accent_hover'],
		'--ink'             => $c['color_text'],
		'--soft'            => $c['color_text_muted'],
		'--line'            => $c['color_border'],
		'--panel-bg'        => $c['color_panel_bg'],
		'--launcher-text'   => $c['color_launcher_text'],
		'--dot-open'        => $c['color_status_open'],
		'--dot-closed'      => $c['color_status_closed'],
		'--success'         => $c['color_success'],
		'--foot-text'       => $c['color_footer_text'],
		'--input-bg'        => $c['color_input_bg'],
		'--disabled'        => $c['color_disabled'],
		'--shadow'          => 'rgba(' . $brand_rgb . ',0.34)',
		'--dot-open-glow'   => 'rgba(' . $open_rgb . ',0.25)',
		'--dot-closed-glow' => 'rgba(' . $closed_rgb . ',0.22)',
		'--success-bg'      => 'rgba(' . $success_rgb . ',0.12)',
		'--focus-ring'      => 'rgba(' . $hover_rgb . ',0.14)',
	);

	$parts = array();
	foreach ( $vars as $var => $val ) {
		$parts[] = $var . ':' . $val;
	}

	return esc_attr( implode( ';', $parts ) );
}

function welyo_get_current_language() {
	if ( function_exists( 'apply_filters' ) ) {
		$lang = apply_filters( 'wpml_current_language', null );
		if ( is_string( $lang ) && $lang !== '' ) {
			return strtolower( $lang );
		}
	}
	if ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
		return strtolower( ICL_LANGUAGE_CODE );
	}
	return 'pl';
}

/** Kontekst języka dla welyo_cfg() (front / REST). */
function welyo_lang_context( $set = null ) {
	static $ctx = null;
	if ( $set !== null ) {
		$ctx = strtolower( (string) $set );
	}
	if ( $ctx === null || $ctx === '' ) {
		$ctx = welyo_get_current_language();
	}
	if ( ! isset( welyo_supported_languages()[ $ctx ] ) ) {
		$ctx = 'pl';
	}
	return $ctx;
}

function welyo_get_enabled_languages() {
	$settings = welyo_get_settings();
	$enabled  = isset( $settings['enabled_languages'] ) && is_array( $settings['enabled_languages'] )
		? array_map( 'strtolower', $settings['enabled_languages'] )
		: array( 'pl' );
	$supported = array_keys( welyo_supported_languages() );
	return array_values( array_intersect( $enabled, $supported ) );
}

function welyo_is_language_enabled( $lang ) {
	$lang = strtolower( (string) $lang );
	return in_array( $lang, welyo_get_enabled_languages(), true );
}

function welyo_should_show_widget() {
	$lang = welyo_get_current_language();
	$show = welyo_is_language_enabled( $lang );
	return (bool) apply_filters( 'welyo_callback_show_for_language', $show, $lang, welyo_get_enabled_languages() );
}

function welyo_constant_map() {
	return array(
		'base_url'        => 'WELYO_BASE_URL',
		'login'           => 'WELYO_LOGIN',
		'api_key'         => 'WELYO_API_KEY',
		'campaign_id'     => 'WELYO_CAMPAIGN_ID',
		'classifier_id'   => 'WELYO_CLASSIFIER_ID',
		'campaign_name'   => 'WELYO_CAMPAIGN_NAME',
		'classifier_name' => 'WELYO_CLASSIFIER_NAME',
		'hash_method'     => 'WELYO_HASH_METHOD',
		'default_prefix'  => 'WELYO_DEFAULT_PREFIX',
		'phone_dial'      => 'WELYO_PHONE_DIAL',
		'phone_pretty'    => 'WELYO_PHONE_PRETTY',
		'open_hour'       => 'WELYO_OPEN_HOUR',
		'close_hour'      => 'WELYO_CLOSE_HOUR',
		'workdays'        => 'WELYO_WORKDAYS',
		'privacy_url'     => 'WELYO_PRIVACY_URL',
	);
}

function welyo_get_saved_settings() {
	$saved = get_option( WELYO_OPTION_KEY, array() );
	return is_array( $saved ) ? $saved : array();
}

/** Migracja płaskiej struktury (v1) → global + languages (v2). */
function welyo_migrate_settings( $saved ) {
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	if ( ! empty( $saved['settings_version'] ) && (int) $saved['settings_version'] >= 2 && ! empty( $saved['languages'] ) ) {
		return $saved;
	}

	$global   = welyo_default_global_settings();
	$lang_keys = welyo_lang_setting_keys();
	$pl       = welyo_default_lang_settings( 'pl' );

	foreach ( welyo_global_setting_keys() as $key ) {
		if ( $key === 'settings_version' || $key === 'enabled_languages' ) {
			continue;
		}
		if ( isset( $saved[ $key ] ) && $saved[ $key ] !== '' ) {
			$global[ $key ] = $saved[ $key ];
		}
	}

	foreach ( $lang_keys as $key ) {
		if ( isset( $saved[ $key ] ) && $saved[ $key ] !== '' ) {
			$pl[ $key ] = $saved[ $key ];
		}
	}

	$global['enabled_languages'] = array( 'pl' );
	$global['settings_version']  = 2;

	$languages = array();
	foreach ( welyo_supported_languages() as $code => $label ) {
		$languages[ $code ] = ( $code === 'pl' ) ? $pl : welyo_default_lang_settings( $code );
		if ( $code !== 'pl' && ! empty( $saved['languages'][ $code ] ) && is_array( $saved['languages'][ $code ] ) ) {
			$languages[ $code ] = array_merge( $languages[ $code ], $saved['languages'][ $code ] );
		}
	}

	return array_merge( $global, array( 'languages' => $languages ) );
}

function welyo_get_settings() {
	global $welyo_settings_cache;
	if ( ! isset( $welyo_settings_cache ) ) {
		$saved  = welyo_migrate_settings( welyo_get_saved_settings() );
		$global = welyo_default_global_settings();
		foreach ( welyo_global_setting_keys() as $key ) {
			if ( isset( $saved[ $key ] ) ) {
				$global[ $key ] = $saved[ $key ];
			}
		}
		$languages = array();
		foreach ( welyo_supported_languages() as $code => $label ) {
			$lang_saved = ( ! empty( $saved['languages'][ $code ] ) && is_array( $saved['languages'][ $code ] ) )
				? $saved['languages'][ $code ]
				: array();
			$languages[ $code ] = array_merge( welyo_default_lang_settings( $code ), $lang_saved );
		}
		$welyo_settings_cache = array_merge( $global, array( 'languages' => $languages ) );
	}
	return $welyo_settings_cache;
}

function welyo_get_lang_settings( $lang = null ) {
	if ( $lang === null ) {
		$lang = welyo_lang_context();
	}
	$settings = welyo_get_settings();
	$lang     = strtolower( (string) $lang );
	if ( ! isset( $settings['languages'][ $lang ] ) ) {
		$lang = 'pl';
	}
	return $settings['languages'][ $lang ];
}

function welyo_flush_settings_cache() {
	global $welyo_settings_cache;
	unset( $welyo_settings_cache );
}

function welyo_is_global_key( $key ) {
	return in_array( $key, welyo_global_setting_keys(), true );
}

/** Wartość: stała PHP → panel WP → domyślna (z kontekstem języka dla pól per-lang). */
function welyo_cfg( $key, $lang = null ) {
	if ( $lang === null ) {
		$lang = welyo_lang_context();
	}

	$map = welyo_constant_map();
	if ( isset( $map[ $key ] ) && defined( $map[ $key ] ) ) {
		$from_const = constant( $map[ $key ] );
		if ( is_int( $from_const ) || ( is_string( $from_const ) && $from_const !== '' ) ) {
			return $from_const;
		}
	}

	$settings = welyo_get_settings();

	if ( welyo_is_global_key( $key ) ) {
		if ( isset( $settings[ $key ] ) && $settings[ $key ] !== '' ) {
			$value = $settings[ $key ];
			if ( $key === 'api_key' ) {
				$value = welyo_decrypt_secret( $value );
			}
			return $value;
		}
		$defaults = welyo_default_global_settings();
		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	}

	$lang_settings = welyo_get_lang_settings( $lang );
	if ( isset( $lang_settings[ $key ] ) && $lang_settings[ $key ] !== '' ) {
		return $lang_settings[ $key ];
	}
	$defaults = welyo_default_lang_settings( $lang );
	return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
}

function welyo_has_stored_secret( $key ) {
	$settings = welyo_get_settings();
	return isset( $settings[ $key ] ) && (string) $settings[ $key ] !== '';
}

function welyo_is_encrypted_secret( $value ) {
	return is_string( $value ) && strpos( $value, 'enc:' ) === 0;
}

function welyo_encrypt_secret( $plaintext ) {
	$plaintext = (string) $plaintext;
	if ( $plaintext === '' || ! function_exists( 'openssl_encrypt' ) ) {
		return $plaintext;
	}

	$key    = hash( 'sha256', wp_salt( 'auth' ), true );
	$iv     = function_exists( 'random_bytes' ) ? random_bytes( 16 ) : openssl_random_pseudo_bytes( 16 );
	$cipher = openssl_encrypt( $plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
	if ( $cipher === false ) {
		return $plaintext;
	}

	return 'enc:' . base64_encode( $iv . $cipher );
}

function welyo_decrypt_secret( $stored ) {
	$stored = (string) $stored;
	if ( $stored === '' || ! welyo_is_encrypted_secret( $stored ) || ! function_exists( 'openssl_decrypt' ) ) {
		return $stored;
	}

	$raw = base64_decode( substr( $stored, 4 ), true );
	if ( $raw === false || strlen( $raw ) < 17 ) {
		return '';
	}

	$key   = hash( 'sha256', wp_salt( 'auth' ), true );
	$plain = openssl_decrypt( substr( $raw, 16 ), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, substr( $raw, 0, 16 ) );

	return $plain !== false ? $plain : '';
}

function welyo_cfg_int( $key, $lang = null ) {
	return (int) welyo_cfg( $key, $lang );
}

function welyo_workdays_array( $lang = null ) {
	return array_map(
		'intval',
		array_filter(
			array_map( 'trim', explode( ',', (string) welyo_cfg( 'workdays', $lang ) ) ),
			'strlen'
		)
	);
}

function welyo_widget_texts( $lang = null ) {
	if ( $lang === null ) {
		$lang = welyo_lang_context();
	}
	$lang_settings = welyo_get_lang_settings( $lang );
	$defaults      = welyo_default_lang_settings( $lang );
	$out           = array();
	foreach ( $defaults as $key => $default ) {
		if ( strpos( $key, 'text_' ) !== 0 ) {
			continue;
		}
		$out[ $key ] = isset( $lang_settings[ $key ] ) ? (string) $lang_settings[ $key ] : (string) $default;
	}
	return $out;
}

function welyo_load_config_files() {
	$path = WP_CONTENT_DIR . '/welyo-config.php';
	if ( is_readable( $path ) ) {
		require_once $path;
	}
}

function welyo_build_config_php( $settings = null ) {
	if ( $settings === null ) {
		$settings = welyo_get_settings();
	}

	$lines = array(
		'<?php',
		'/** Wygenerowano z panelu Welyo Callback. */',
		"if ( ! defined( 'ABSPATH' ) ) { exit; }",
		'',
	);

	$flat = array_merge(
		array_intersect_key( $settings, array_flip( welyo_global_setting_keys() ) ),
		welyo_get_lang_settings( 'pl' )
	);

	foreach ( welyo_constant_map() as $key => $const ) {
		if ( ! isset( $flat[ $key ] ) || $flat[ $key ] === '' ) {
			continue;
		}
		$val = $flat[ $key ];
		if ( $key === 'api_key' ) {
			$val = welyo_decrypt_secret( $val );
		}
		if ( in_array( $key, array( 'open_hour', 'close_hour' ), true ) ) {
			$php_val = (int) $val;
		} elseif ( is_numeric( $val ) && $key !== 'phone_dial' && $key !== 'phone_pretty' && $key !== 'default_prefix' ) {
			$php_val = (int) $val;
		} else {
			$php_val = "'" . str_replace( array( '\\', "'" ), array( '\\\\', "\\'" ), (string) $val ) . "'";
		}
		$lines[] = "if ( ! defined( '{$const}' ) ) define( '{$const}', {$php_val} );";
	}

	$lines[] = '';
	return implode( "\n", $lines );
}

function welyo_write_config_file( $settings = null ) {
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	$content = welyo_build_config_php( $settings );
	$path    = WP_CONTENT_DIR . '/welyo-config.php';

	if ( WP_Filesystem() ) {
		global $wp_filesystem;
		return (bool) $wp_filesystem->put_contents( $path, $content, FS_CHMOD_FILE );
	}

	return (bool) file_put_contents( $path, $content );
}

function welyo_sanitize_lang_settings( $input, $lang, $current_lang ) {
	$defaults = welyo_default_lang_settings( $lang );
	$out      = array();

	$string_keys = array_diff( welyo_lang_setting_keys(), array( 'open_hour', 'close_hour', 'forminator_form_id', 'forminator_enabled' ) );

	foreach ( $string_keys as $key ) {
		if ( ! isset( $input[ $key ] ) ) {
			continue;
		}
		if ( $key === 'text_consent' ) {
			$out[ $key ] = wp_kses_post( wp_unslash( $input[ $key ] ) );
		} else {
			$out[ $key ] = sanitize_text_field( wp_unslash( $input[ $key ] ) );
		}
	}

	$out['open_hour']  = isset( $input['open_hour'] ) ? max( 0, min( 23, (int) $input['open_hour'] ) ) : (int) $current_lang['open_hour'];
	$out['close_hour'] = isset( $input['close_hour'] ) ? max( 1, min( 24, (int) $input['close_hour'] ) ) : (int) $current_lang['close_hour'];
	$out['forminator_enabled'] = ! empty( $input['forminator_enabled'] ) ? 1 : 0;
	if ( isset( $input['forminator_form_id'] ) ) {
		$out['forminator_form_id'] = max( 0, (int) $input['forminator_form_id'] );
	}

	return array_merge( $defaults, $current_lang, $out );
}

function welyo_sanitize_settings( $input ) {
	$defaults = welyo_default_global_settings();
	$current  = welyo_get_settings();
	$out      = array();

	$global_strings = array( 'base_url', 'login', 'hash_method' );
	foreach ( $global_strings as $key ) {
		if ( isset( $input[ $key ] ) ) {
			$out[ $key ] = sanitize_text_field( wp_unslash( $input[ $key ] ) );
		}
	}

	if ( ! empty( $input['api_key_clear'] ) ) {
		$out['api_key'] = '';
	} elseif ( isset( $input['api_key'] ) ) {
		$api_key = sanitize_text_field( wp_unslash( $input['api_key'] ) );
		if ( $api_key !== '' ) {
			$out['api_key'] = welyo_encrypt_secret( $api_key );
		} else {
			$out['api_key'] = $current['api_key'];
			if ( $out['api_key'] !== '' && ! welyo_is_encrypted_secret( $out['api_key'] ) ) {
				$out['api_key'] = welyo_encrypt_secret( welyo_decrypt_secret( $out['api_key'] ) );
			}
		}
	}

	$out['auto_footer'] = ! empty( $input['auto_footer'] ) ? 1 : 0;
	$out['forminator_integration_enabled'] = ! empty( $input['forminator_integration_enabled'] ) ? 1 : 0;

	if ( isset( $input['hash_method'] ) && in_array( $input['hash_method'], array( 'md5', 'sha1' ), true ) ) {
		$out['hash_method'] = $input['hash_method'];
	}

	$enabled = array();
	if ( ! empty( $input['enabled_languages'] ) && is_array( $input['enabled_languages'] ) ) {
		foreach ( $input['enabled_languages'] as $code ) {
			$code = strtolower( sanitize_key( $code ) );
			if ( isset( welyo_supported_languages()[ $code ] ) ) {
				$enabled[] = $code;
			}
		}
	}
	$out['enabled_languages'] = $enabled;

	foreach ( array_keys( welyo_color_fields() ) as $color_key ) {
		if ( ! isset( $input[ $color_key ] ) ) {
			continue;
		}
		$out[ $color_key ] = welyo_sanitize_color( wp_unslash( $input[ $color_key ] ), $defaults[ $color_key ] );
	}

	$out['settings_version'] = 3;
	$out['languages']        = array();
	foreach ( welyo_supported_languages() as $code => $label ) {
		$lang_in    = ( ! empty( $input['languages'][ $code ] ) && is_array( $input['languages'][ $code ] ) )
			? $input['languages'][ $code ]
			: array();
		$lang_cur   = isset( $current['languages'][ $code ] ) ? $current['languages'][ $code ] : welyo_default_lang_settings( $code );
		$out['languages'][ $code ] = welyo_sanitize_lang_settings( $lang_in, $code, $lang_cur );
	}

	$merged = $current;
	foreach ( $out as $key => $val ) {
		if ( $key !== 'languages' ) {
			$merged[ $key ] = $val;
		}
	}
	$merged['languages'] = $out['languages'];

	return $merged;
}

function welyo_clear_lang_transients() {
	delete_transient( 'welyo_auth_mode' );
	foreach ( array_keys( welyo_supported_languages() ) as $lang ) {
		delete_transient( 'welyo_campaign_id_' . $lang );
		delete_transient( 'welyo_classifier_id_' . $lang );
		delete_transient( 'welyo_forminator_campaign_id_' . $lang );
		delete_transient( 'welyo_forminator_classifier_id_' . $lang );
	}
	delete_transient( 'welyo_campaign_id' );
	delete_transient( 'welyo_classifier_id' );
}

function welyo_bootstrap_config() {
	welyo_load_config_files();
}
