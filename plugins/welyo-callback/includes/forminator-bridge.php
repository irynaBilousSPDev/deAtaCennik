<?php
/**
 * Forminator quiz → Welyo (osobna kampania CC, pola EMAIL + WYNIK_QUIZU).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'welyo_forminator_register_hooks', 25 );

function welyo_forminator_integration_active() {
	if ( empty( welyo_cfg( 'forminator_integration_enabled' ) ) ) {
		return false;
	}
	return class_exists( 'Forminator_Form_Entry_Model' );
}

function welyo_forminator_register_hooks() {
	if ( ! welyo_forminator_integration_active() ) {
		return;
	}

	add_action( 'forminator_form_after_save_entry', 'welyo_forminator_after_save_entry', 10, 2 );
	add_action( 'forminator_form_after_handle_submit', 'welyo_forminator_after_handle_submit', 10, 2 );
	add_action( 'forminator_quizzes_submit_before_set_fields', 'welyo_forminator_quiz_before_set_fields', 10, 3 );
}

function welyo_forminator_after_save_entry( $form_id, $response ) {
	if ( ! is_array( $response ) || empty( $response['success'] ) ) {
		return;
	}
	$entry_id = isset( $response['entry_id'] ) ? (int) $response['entry_id'] : 0;
	if ( $entry_id <= 0 && class_exists( 'Forminator_Form_Entry_Model' ) ) {
		$entry = Forminator_Form_Entry_Model::get_latest_entry_by_form_id( (int) $form_id );
		if ( $entry && ! empty( $entry->entry_id ) ) {
			$entry_id = (int) $entry->entry_id;
		}
	}
	if ( $entry_id > 0 ) {
		welyo_forminator_process_submission( $entry_id, (int) $form_id );
	}
}

function welyo_forminator_after_handle_submit( $form_id, $response ) {
	welyo_forminator_after_save_entry( $form_id, is_array( $response ) ? $response : array() );
}

/** Quiz z leadami — tylko gdy telefon już jest w danych (inaczej after_save). */
function welyo_forminator_quiz_before_set_fields( $entry, $form_id, $field_data_array ) {
	if ( empty( $entry->entry_id ) ) {
		return;
	}

	$hints = is_array( $field_data_array ) ? $field_data_array : array();
	$lead  = welyo_forminator_resolve_lead_values(
		(int) $entry->entry_id,
		(int) $form_id,
		array(),
		$hints
	);
	$digits = preg_replace( '/\D/', '', $lead['phone'] );
	if ( strlen( $digits ) < 9 ) {
		return;
	}

	welyo_forminator_process_submission( (int) $entry->entry_id, (int) $form_id, $hints );
}

/** ID quizu + ewentualny powiązany formularz leadów. */
function welyo_forminator_related_form_ids( $form_id ) {
	$form_id = (int) $form_id;
	$ids     = array( $form_id );

	if ( class_exists( 'Forminator_API' ) ) {
		$quiz = Forminator_API::get_quiz( $form_id );
		if ( $quiz && ! empty( $quiz->settings ) && is_array( $quiz->settings ) ) {
			foreach ( array( 'lead_id', 'leads_id', 'leads-form' ) as $lead_key ) {
				if ( ! empty( $quiz->settings[ $lead_key ] ) ) {
					$ids[] = (int) $quiz->settings[ $lead_key ];
				}
			}
		}
	}

	$meta = get_post_meta( $form_id, 'forminator_form_meta', true );
	if ( is_array( $meta ) && ! empty( $meta['settings'] ) && is_array( $meta['settings'] ) ) {
		foreach ( array( 'lead_id', 'leads_id', 'leads-form' ) as $lead_key ) {
			if ( ! empty( $meta['settings'][ $lead_key ] ) ) {
				$ids[] = (int) $meta['settings'][ $lead_key ];
			}
		}
	}

	return array_values( array_unique( array_filter( $ids ) ) );
}

/** Mapa pól z definicji formularza / leadów (element_id → type, label). */
function welyo_forminator_collect_field_map( $form_id ) {
	$map = array();
	foreach ( welyo_forminator_related_form_ids( $form_id ) as $related_id ) {
		$meta = get_post_meta( $related_id, 'forminator_form_meta', true );
		if ( ! is_array( $meta ) || empty( $meta['fields'] ) || ! is_array( $meta['fields'] ) ) {
			continue;
		}
		foreach ( $meta['fields'] as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$element_id = '';
			if ( ! empty( $field['element_id'] ) ) {
				$element_id = (string) $field['element_id'];
			} elseif ( ! empty( $field['id'] ) ) {
				$element_id = (string) $field['id'];
			}
			if ( $element_id === '' ) {
				continue;
			}
			$map[ $element_id ] = array(
				'type'  => isset( $field['type'] ) ? strtolower( (string) $field['type'] ) : '',
				'label' => isset( $field['field_label'] ) ? (string) $field['field_label'] : '',
			);
		}
	}
	return $map;
}

/** Spłaszcza meta wpisu do slug => wartość tekstowa. */
function welyo_forminator_flatten_entry_meta( $entry ) {
	$flat = array();
	if ( ! is_object( $entry ) || empty( $entry->meta_data ) || ! is_array( $entry->meta_data ) ) {
		return $flat;
	}
	foreach ( $entry->meta_data as $meta_key => $meta_value ) {
		if ( $meta_key === 'entry' ) {
			continue;
		}
		$flat[ (string) $meta_key ] = welyo_forminator_normalize_field_value( $meta_value );
	}
	return $flat;
}

/** Uzupełnia mapę wartości z tablicy field_data (hook quizu). */
function welyo_forminator_merge_field_hints( $flat, $field_hints ) {
	if ( ! is_array( $field_hints ) ) {
		return $flat;
	}
	foreach ( $field_hints as $hint ) {
		if ( ! is_array( $hint ) || empty( $hint['name'] ) ) {
			continue;
		}
		$value = welyo_forminator_normalize_field_value( $hint );
		if ( $value !== '' ) {
			$flat[ (string) $hint['name'] ] = $value;
		}
	}
	return $flat;
}

/** Rozpoznaje typ pola po definicji Forminator lub po slugu. */
function welyo_forminator_guess_field_kind( $slug, $field_map ) {
	$slug_lc  = strtolower( (string) $slug );
	$type     = isset( $field_map[ $slug ]['type'] ) ? strtolower( (string) $field_map[ $slug ]['type'] ) : '';
	$label_lc = isset( $field_map[ $slug ]['label'] ) ? strtolower( (string) $field_map[ $slug ]['label'] ) : '';

	if ( $type === 'phone' || preg_match( '/^phone-/', $slug_lc ) || preg_match( '/telefon|phone|tel\b/', $label_lc . ' ' . $slug_lc ) ) {
		return 'phone';
	}
	if ( $type === 'email' || preg_match( '/^email-/', $slug_lc ) || preg_match( '/e-?mail|email/', $label_lc . ' ' . $slug_lc ) ) {
		return 'email';
	}
	if ( $type === 'checkbox' || preg_match( '/^checkbox-/', $slug_lc ) || preg_match( '/zgoda|rodo|consent|gdpr|privacy/', $label_lc . ' ' . $slug_lc ) ) {
		return 'consent';
	}
	if ( in_array( $type, array( 'name', 'text' ), true ) || preg_match( '/^name-/', $slug_lc ) || preg_match( '/\bimi[eę]\b|first.?name|name\b/', $label_lc ) ) {
		return 'name';
	}
	return '';
}

/**
 * Telefon, e-mail, zgoda, imię i wynik — slugi opcjonalne (auto z wpisu).
 *
 * @return array{phone:string,name:string,email:string,consent:?bool,quiz_result:string}
 */
function welyo_forminator_resolve_lead_values( $entry_id, $form_id, $slug_overrides = array(), $field_hints = array() ) {
	$out = array(
		'phone'       => '',
		'name'        => '',
		'email'       => '',
		'consent'     => null,
		'quiz_result' => '',
	);

	if ( ! class_exists( 'Forminator_Form_Entry_Model' ) ) {
		return $out;
	}

	$entry     = new Forminator_Form_Entry_Model( (int) $entry_id );
	$flat      = welyo_forminator_merge_field_hints( welyo_forminator_flatten_entry_meta( $entry ), $field_hints );
	$field_map = welyo_forminator_collect_field_map( $form_id );

	$slug_map = array(
		'phone'   => trim( (string) ( $slug_overrides['phone'] ?? '' ) ),
		'name'    => trim( (string) ( $slug_overrides['name'] ?? '' ) ),
		'email'   => trim( (string) ( $slug_overrides['email'] ?? '' ) ),
		'consent' => trim( (string) ( $slug_overrides['consent'] ?? '' ) ),
	);

	foreach ( $slug_map as $key => $slug ) {
		if ( $slug !== '' && isset( $flat[ $slug ] ) ) {
			if ( $key === 'consent' ) {
				$out['consent'] = welyo_forminator_truthy( $flat[ $slug ] );
			} else {
				$out[ $key ] = $flat[ $slug ];
			}
		}
	}

	foreach ( $flat as $slug => $value ) {
		if ( $value === '' ) {
			continue;
		}
		$kind = welyo_forminator_guess_field_kind( $slug, $field_map );

		if ( $out['phone'] === '' && $kind === 'phone' ) {
			$digits = preg_replace( '/\D/', '', $value );
			if ( strlen( $digits ) >= 9 ) {
				$out['phone'] = $value;
			}
		}
		if ( $out['email'] === '' && ( $kind === 'email' || is_email( $value ) ) ) {
			if ( is_email( $value ) ) {
				$out['email'] = $value;
			}
		}
		if ( $out['name'] === '' && $kind === 'name' && ! is_email( $value ) ) {
			$digits = preg_replace( '/\D/', '', $value );
			if ( strlen( $digits ) < 9 ) {
				$out['name'] = $value;
			}
		}
		if ( $out['consent'] === null && $kind === 'consent' ) {
			$out['consent'] = welyo_forminator_truthy( $value );
		}
	}

	if ( $out['phone'] === '' ) {
		foreach ( $flat as $slug => $value ) {
			$digits = preg_replace( '/\D/', '', $value );
			if ( strlen( $digits ) >= 9 && strlen( $digits ) <= 15 ) {
				$out['phone'] = $value;
				break;
			}
		}
	}

	if ( $out['email'] === '' ) {
		foreach ( $flat as $value ) {
			if ( is_email( $value ) ) {
				$out['email'] = $value;
				break;
			}
		}
	}

	$result_slug = trim( (string) ( $slug_overrides['quiz_result'] ?? '' ) );
	$out['quiz_result'] = welyo_forminator_get_quiz_result( $entry_id, $result_slug );

	return $out;
}

/** Konfiguracja quizu dla ID formularza (quiz lub powiązany formularz leadów). */
function welyo_forminator_quiz_config_for_form( $form_id ) {
	$form_id = (int) $form_id;
	if ( $form_id <= 0 ) {
		return null;
	}

	foreach ( welyo_supported_languages() as $code => $label ) {
		if ( empty( welyo_cfg( 'forminator_enabled', $code ) ) ) {
			continue;
		}

		$quiz_id = (int) welyo_cfg( 'forminator_form_id', $code );
		if ( $quiz_id <= 0 ) {
			continue;
		}

		if ( $form_id === $quiz_id ) {
			return array(
				'lang'    => $code,
				'quiz_id' => $quiz_id,
			);
		}

		$related = welyo_forminator_related_form_ids( $quiz_id );
		if ( in_array( $form_id, $related, true ) ) {
			return array(
				'lang'    => $code,
				'quiz_id' => $quiz_id,
			);
		}
	}

	return null;
}

/** @deprecated Użyj welyo_forminator_quiz_config_for_form(). */
function welyo_forminator_lang_for_form( $form_id ) {
	$config = welyo_forminator_quiz_config_for_form( $form_id );
	return $config ? $config['lang'] : null;
}

/** Wpis quizu z wynikiem osobowości (gdy lead jest osobnym formularzem). */
function welyo_forminator_resolve_quiz_result_entry_id( $entry_id, $form_id, $quiz_id ) {
	$entry_id = (int) $entry_id;
	$form_id  = (int) $form_id;
	$quiz_id  = (int) $quiz_id;

	if ( $form_id === $quiz_id ) {
		return $entry_id;
	}

	if ( ! class_exists( 'Forminator_Form_Entry_Model' ) || $quiz_id <= 0 ) {
		return $entry_id;
	}

	$latest = Forminator_Form_Entry_Model::get_latest_entry_by_form_id( $quiz_id );
	if ( $latest && ! empty( $latest->entry_id ) ) {
		return (int) $latest->entry_id;
	}

	return $entry_id;
}

/** Ostatni wpis quizu lub formularza leadów powiązanego z konfiguracją języka. */
function welyo_forminator_find_latest_entry_for_lang( $lang ) {
	if ( ! class_exists( 'Forminator_Form_Entry_Model' ) ) {
		return null;
	}

	welyo_lang_context( $lang );
	$quiz_id = (int) welyo_cfg( 'forminator_form_id', $lang );
	if ( $quiz_id <= 0 ) {
		return null;
	}

	$candidates = welyo_forminator_related_form_ids( $quiz_id );
	$best       = null;
	$best_time  = 0;

	foreach ( $candidates as $candidate_id ) {
		$entry = Forminator_Form_Entry_Model::get_latest_entry_by_form_id( (int) $candidate_id );
		if ( ! $entry || empty( $entry->entry_id ) ) {
			continue;
		}
		$time = isset( $entry->date_created_sql ) ? strtotime( $entry->date_created_sql ) : 0;
		if ( ! $time && isset( $entry->date_created ) ) {
			$time = strtotime( $entry->date_created );
		}
		if ( $time >= $best_time ) {
			$best_time = $time;
			$best      = $entry;
		}
	}

	return $best;
}

/**
 * Diagnostyka ostatniego wpisu quizu — bez wysyłki do Welyo.
 *
 * @return array<int, array{id:string, ok:bool, message:string}>
 */
function welyo_forminator_run_diagnostics( $lang ) {
	$steps = array();
	$lang  = strtolower( sanitize_key( (string) $lang ) );

	if ( ! isset( welyo_supported_languages()[ $lang ] ) ) {
		$steps[] = array(
			'id'      => 'lang',
			'ok'      => false,
			'message' => __( 'Nieobsługiwany kod języka.', 'akademiata' ),
		);
		return $steps;
	}

	welyo_lang_context( $lang );

	if ( ! welyo_forminator_integration_active() ) {
		$steps[] = array(
			'id'      => 'integration',
			'ok'      => false,
			'message' => __( 'Integracja quizu wyłączona lub plugin Forminator nieaktywny.', 'akademiata' ),
		);
		return $steps;
	}

	if ( empty( welyo_cfg( 'forminator_enabled', $lang ) ) ) {
		$steps[] = array(
			'id'      => 'enabled',
			'ok'      => false,
			'message' => __( 'Quiz dla tego języka nie jest włączony w ustawieniach.', 'akademiata' ),
		);
		return $steps;
	}

	$quiz_id = (int) welyo_cfg( 'forminator_form_id', $lang );
	$related = welyo_forminator_related_form_ids( $quiz_id );
	$lead_ids = array_values( array_diff( $related, array( $quiz_id ) ) );

	$steps[] = array(
		'id'      => 'quiz_id',
		'ok'      => $quiz_id > 0,
		'message' => $quiz_id > 0
			? sprintf( __( 'ID quizu w ustawieniach: %d', 'akademiata' ), $quiz_id )
			: __( 'Brak ID quizu — uzupełnij i zapisz ustawienia.', 'akademiata' ),
	);

	if ( ! empty( $lead_ids ) ) {
		$steps[] = array(
			'id'      => 'lead_forms',
			'ok'      => true,
			'message' => sprintf(
				/* translators: %s: comma-separated form IDs */
				__( 'Powiązane formularze leadów Forminator: %s', 'akademiata' ),
				implode( ', ', array_map( 'intval', $lead_ids ) )
			),
		);
	}

	$campaign_id = (string) welyo_cfg( 'forminator_quiz_campaign_id', $lang );
	$steps[] = array(
		'id'      => 'campaign',
		'ok'      => $campaign_id !== '',
		'message' => $campaign_id !== ''
			? sprintf( __( 'Kampania quizu: #%s', 'akademiata' ), $campaign_id )
			: __( 'Nie wybrano kampanii quizu — pobierz listę z API i zapisz.', 'akademiata' ),
	);

	$entry = welyo_forminator_find_latest_entry_for_lang( $lang );
	if ( ! $entry || empty( $entry->entry_id ) ) {
		$steps[] = array(
			'id'      => 'entry',
			'ok'      => false,
			'message' => __( 'Brak wpisów Forminator dla tego quizu — wypełnij quiz na stronie i uruchom test ponownie.', 'akademiata' ),
		);
		return $steps;
	}

	$form_id  = isset( $entry->form_id ) ? (int) $entry->form_id : 0;
	$entry_id = (int) $entry->entry_id;
	$config   = welyo_forminator_quiz_config_for_form( $form_id );

	$steps[] = array(
		'id'      => 'entry',
		'ok'      => true,
		'message' => sprintf(
			/* translators: 1: entry ID, 2: form ID */
			__( 'Ostatni wpis Forminator: #%1$d (formularz #%2$d).', 'akademiata' ),
			$entry_id,
			$form_id
		),
	);

	$steps[] = array(
		'id'      => 'form_match',
		'ok'      => (bool) $config,
		'message' => $config
			? __( 'Formularz rozpoznany przez integrację Welyo.', 'akademiata' )
			: __( 'Formularz NIE pasuje do ID quizu ani formularza leadów — sprawdź ID w ustawieniach.', 'akademiata' ),
	);

	$quiz_id_cfg = $config ? (int) $config['quiz_id'] : $quiz_id;
	$result_entry_id = welyo_forminator_resolve_quiz_result_entry_id( $entry_id, $form_id, $quiz_id_cfg );
	$lead = welyo_forminator_resolve_lead_values(
		$entry_id,
		$form_id,
		array(
			'phone'       => (string) welyo_cfg( 'forminator_field_phone', $lang ),
			'name'        => (string) welyo_cfg( 'forminator_field_name', $lang ),
			'email'       => (string) welyo_cfg( 'forminator_field_email', $lang ),
			'consent'     => (string) welyo_cfg( 'forminator_field_consent', $lang ),
			'quiz_result' => (string) welyo_cfg( 'forminator_field_quiz_result', $lang ),
		)
	);

	if ( $lead['quiz_result'] === '' && $result_entry_id !== $entry_id ) {
		$lead['quiz_result'] = welyo_forminator_get_quiz_result( $result_entry_id );
	}

	$phone_digits = preg_replace( '/\D/', '', $lead['phone'] );
	$steps[] = array(
		'id'      => 'phone',
		'ok'      => strlen( $phone_digits ) >= 9,
		'message' => strlen( $phone_digits ) >= 9
			? __( 'Telefon wykryty w wpisie.', 'akademiata' )
			: __( 'Brak poprawnego telefonu — quiz musi zbierać numer (min. 9 cyfr).', 'akademiata' ),
	);

	$steps[] = array(
		'id'      => 'email',
		'ok'      => $lead['email'] !== '',
		'message' => $lead['email'] !== ''
			? __( 'E-mail wykryty w wpisie.', 'akademiata' )
			: __( 'Brak e-maila w wpisie (pole opcjonalne w Welyo, ale zalecane).', 'akademiata' ),
	);

	if ( $lead['consent'] === false ) {
		$steps[] = array(
			'id'      => 'consent',
			'ok'      => false,
			'message' => __( 'Zgoda RODO niezaznaczona — lead nie zostanie wysłany.', 'akademiata' ),
		);
	} elseif ( $lead['consent'] === true ) {
		$steps[] = array(
			'id'      => 'consent',
			'ok'      => true,
			'message' => __( 'Zgoda RODO: tak.', 'akademiata' ),
		);
	} else {
		$steps[] = array(
			'id'      => 'consent',
			'ok'      => true,
			'message' => __( 'Pole zgody nie wykryte — wysyłka dozwolona (brak jawnej odmowy).', 'akademiata' ),
		);
	}

	$steps[] = array(
		'id'      => 'quiz_result',
		'ok'      => $lead['quiz_result'] !== '',
		'message' => $lead['quiz_result'] !== ''
			? sprintf(
				/* translators: %s: quiz personality result */
				__( 'Wynik quizu: „%s”.', 'akademiata' ),
				$lead['quiz_result']
			)
			: __( 'Brak wyniku quizu w wpisie — przy quizie osobowości powinien pojawić się automatycznie.', 'akademiata' ),
	);

	$jwt = welyo_get_jwt();
	if ( is_wp_error( $jwt ) ) {
		$steps[] = array(
			'id'      => 'jwt',
			'ok'      => false,
			'message' => $jwt->get_error_message(),
		);
		return $steps;
	}

	$resolved_campaign = welyo_resolve_forminator_campaign_id( $jwt, $lang );
	$steps[] = array(
		'id'      => 'campaign_api',
		'ok'      => ! is_wp_error( $resolved_campaign ),
		'message' => ! is_wp_error( $resolved_campaign )
			? sprintf( __( 'Kampania gotowa do wysyłki: #%s.', 'akademiata' ), $resolved_campaign )
			: $resolved_campaign->get_error_message(),
	);

	return $steps;
}

/** Wartość pola z wpisu Forminator (slug pola z kreatora). */
function welyo_forminator_entry_field( $entry_id, $field_slug ) {
	$field_slug = trim( (string) $field_slug );
	if ( $field_slug === '' || ! class_exists( 'Forminator_Form_Entry_Model' ) ) {
		return '';
	}

	$entry = new Forminator_Form_Entry_Model( (int) $entry_id );
	if ( empty( $entry->meta_data ) || ! is_array( $entry->meta_data ) ) {
		return '';
	}

	if ( isset( $entry->meta_data[ $field_slug ] ) ) {
		return welyo_forminator_normalize_field_value( $entry->meta_data[ $field_slug ] );
	}

	foreach ( $entry->meta_data as $meta_key => $meta_value ) {
		if ( (string) $meta_key === $field_slug ) {
			return welyo_forminator_normalize_field_value( $meta_value );
		}
		if ( is_array( $meta_value ) && isset( $meta_value['name'] ) && (string) $meta_value['name'] === $field_slug ) {
			return welyo_forminator_normalize_field_value( $meta_value );
		}
	}

	return '';
}

/** Wynik quizu typu „osobowość” (Lider Zespołu itd.) — Forminator liczy go sam, bez sluga pola. */
function welyo_forminator_get_personality_result( $entry_id ) {
	if ( ! class_exists( 'Forminator_Form_Entry_Model' ) ) {
		return '';
	}

	$entry = new Forminator_Form_Entry_Model( (int) $entry_id );
	if ( empty( $entry->meta_data ) || ! is_array( $entry->meta_data ) ) {
		return '';
	}

	if ( isset( $entry->meta_data['entry'] ) ) {
		$raw_entry = $entry->meta_data['entry'];
		$data      = is_array( $raw_entry ) && isset( $raw_entry['value'] ) ? $raw_entry['value'] : $raw_entry;
		if ( is_string( $data ) ) {
			$decoded = json_decode( $data, true );
			if ( is_array( $decoded ) ) {
				$data = $decoded;
			}
		}
		if ( is_array( $data ) ) {
			if ( ! empty( $data['result']['title'] ) ) {
				return trim( (string) $data['result']['title'] );
			}
			if ( ! empty( $data['result'] ) && is_string( $data['result'] ) ) {
				return trim( $data['result'] );
			}
		}
	}

	foreach ( array( 'quiz_result', 'personality', 'result' ) as $meta_key ) {
		if ( isset( $entry->meta_data[ $meta_key ] ) ) {
			$val = welyo_forminator_normalize_field_value( $entry->meta_data[ $meta_key ] );
			if ( $val !== '' ) {
				return $val;
			}
		}
	}

	return '';
}

function welyo_forminator_get_quiz_result( $entry_id, $result_slug = '' ) {
	$result_slug = trim( (string) $result_slug );
	if ( $result_slug !== '' ) {
		$from_field = welyo_forminator_entry_field( $entry_id, $result_slug );
		if ( $from_field !== '' ) {
			return $from_field;
		}
	}
	return welyo_forminator_get_personality_result( $entry_id );
}

function welyo_forminator_normalize_field_value( $raw ) {
	if ( is_array( $raw ) ) {
		if ( array_key_exists( 'value', $raw ) ) {
			$raw = $raw['value'];
		} else {
			$raw = implode( ', ', array_filter( array_map( 'strval', $raw ) ) );
		}
	}
	if ( is_array( $raw ) ) {
		$raw = implode( ', ', array_filter( array_map( 'strval', $raw ) ) );
	}
	return trim( (string) $raw );
}

function welyo_forminator_truthy( $value ) {
	if ( is_array( $value ) ) {
		$value = implode( '', $value );
	}
	$value = strtolower( trim( (string) $value ) );
	if ( $value === '' || $value === '0' || $value === 'false' || $value === 'no' || $value === 'off' ) {
		return false;
	}
	return true;
}

function welyo_forminator_process_submission( $entry_id, $form_id, $field_hints = array() ) {
	if ( $entry_id <= 0 || $form_id <= 0 ) {
		return;
	}

	$config = welyo_forminator_quiz_config_for_form( $form_id );
	if ( $config === null ) {
		error_log( '[Welyo Forminator] Pominięto formularz #' . $form_id . ' — brak dopasowania do ID quizu lub formularza leadów.' );
		return;
	}

	$lang    = $config['lang'];
	$quiz_id = (int) $config['quiz_id'];

	$dedup_key = 'welyo_fnt_sent_' . $quiz_id . '_' . $entry_id;
	if ( get_transient( $dedup_key ) ) {
		return;
	}

	welyo_lang_context( $lang );

	$lead = welyo_forminator_resolve_lead_values(
		$entry_id,
		$form_id,
		array(
			'phone'       => (string) welyo_cfg( 'forminator_field_phone', $lang ),
			'name'        => (string) welyo_cfg( 'forminator_field_name', $lang ),
			'email'       => (string) welyo_cfg( 'forminator_field_email', $lang ),
			'consent'     => (string) welyo_cfg( 'forminator_field_consent', $lang ),
			'quiz_result' => (string) welyo_cfg( 'forminator_field_quiz_result', $lang ),
		),
		$field_hints
	);

	$phone       = $lead['phone'];
	$name        = $lead['name'];
	$email       = $lead['email'];
	$quiz_result = $lead['quiz_result'];

	if ( $quiz_result === '' ) {
		$result_entry_id = welyo_forminator_resolve_quiz_result_entry_id( $entry_id, $form_id, $quiz_id );
		if ( $result_entry_id !== $entry_id ) {
			$quiz_result = welyo_forminator_get_quiz_result( $result_entry_id );
		}
	}

	if ( $lead['consent'] === false ) {
		error_log( '[Welyo Forminator] Pominięto wpis #' . $entry_id . ' — brak zgody (' . $lang . ').' );
		return;
	}

	$digits = preg_replace( '/\D/', '', $phone );
	if ( strlen( $digits ) < 9 ) {
		error_log( '[Welyo Forminator] Pominięto wpis #' . $entry_id . ' (form #' . $form_id . ') — nieprawidłowy telefon (' . $lang . ').' );
		return;
	}

	$rate = welyo_check_rate_limit();
	if ( is_wp_error( $rate ) ) {
		error_log( '[Welyo Forminator] Limit zapytań dla wpisu #' . $entry_id . '.' );
		return;
	}

	$jwt = welyo_get_jwt();
	if ( is_wp_error( $jwt ) ) {
		error_log( '[Welyo Forminator] JWT: ' . $jwt->get_error_message() );
		return;
	}

	$phone_e164 = welyo_normalize_phone( $phone, $lang );
	$ext_id     = 'forminator-' . $lang . '-' . $quiz_id . '-' . $entry_id;

	$campaign_id = welyo_resolve_forminator_campaign_id( $jwt, $lang );
	if ( is_wp_error( $campaign_id ) ) {
		error_log( '[Welyo Forminator] campaign: ' . $campaign_id->get_error_message() );
		return;
	}

	$extra = array();
	if ( $email !== '' ) {
		$extra['EMAIL'] = sanitize_email( $email );
	}
	if ( $quiz_result !== '' ) {
		$extra['WYNIK_QUIZU'] = sanitize_text_field( $quiz_result );
	}

	$res = welyo_add_record( $jwt, $campaign_id, '', $name, $phone_e164, null, $ext_id, $extra );
	if ( is_wp_error( $res ) ) {
		error_log( '[Welyo Forminator] add-records: ' . $res->get_error_message() );
		return;
	}

	set_transient( $dedup_key, 1, 10 * MINUTE_IN_SECONDS );
	error_log( '[Welyo Forminator] Wysłano lead #' . $entry_id . ' → kampania #' . $campaign_id . ' (' . $lang . ').' );

	do_action( 'welyo_forminator_lead_sent', $entry_id, $form_id, $lang, $ext_id );
}
