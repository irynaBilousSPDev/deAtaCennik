<?php
/**
 * Prices calculator UI copy (WPML UI lang: pl / en / uk / ru).
 * Study language (sheet RAW) stays separate — this is display only.
 */

/**
 * @param string               $lang
 * @param array<string,string> $map pl|en|uk|ru
 * @return string
 */
function akademiata_prices_ui_pick($lang, array $map) {
	$lang = function_exists('akademiata_normalize_theme_lang_code')
		? akademiata_normalize_theme_lang_code($lang)
		: 'pl';
	if (isset($map[ $lang ]) && $map[ $lang ] !== '') {
		return (string) $map[ $lang ];
	}
	return (string) ( $map['pl'] ?? '' );
}

/**
 * @return array<string, array<string, string>>
 */
function akademiata_prices_calculator_ui_strings() {
	return array(
		'loading'           => array(
			'pl' => 'Ładowanie aktualnych cen...',
			'en' => 'Loading current prices...',
			'uk' => 'Завантаження актуальних цін...',
			'ru' => 'Загрузка актуальных цен...',
		),
		'city'              => array(
			'pl' => 'Miasto',
			'en' => 'City',
			'uk' => 'Місто',
			'ru' => 'Город',
		),
		'studyLang'         => array(
			'pl' => 'Język studiów',
			'en' => 'Study language',
			'uk' => 'Мова навчання',
			'ru' => 'Язык обучения',
		),
		'polishShort'       => array(
			'pl' => 'Polski',
			'en' => 'Polish',
			'uk' => 'Польська',
			'ru' => 'Польский',
		),
		'polishLong'        => array(
			'pl' => 'Studia w języku polskim',
			'en' => 'Studies in Polish',
			'uk' => 'Навчання польською мовою',
			'ru' => 'Обучение на польском языке',
		),
		'englishShort'      => array(
			'pl' => 'English',
			'en' => 'English',
			'uk' => 'Англійська',
			'ru' => 'Английский',
		),
		'englishLong'       => array(
			'pl' => 'Studia w języku angielskim',
			'en' => 'Studies in English',
			'uk' => 'Навчання англійською мовою',
			'ru' => 'Обучение на английском языке',
		),
		'uabyLabel'         => array(
			'pl' => 'Jestem obywatelem Ukrainy lub Białorusi',
			'en' => 'I am a citizen of Ukraine or Belarus',
			'uk' => 'Я громадянин України або Білорусі',
			'ru' => 'Я гражданин Украины или Беларуси',
		),
		'selectedProgram'   => array(
			'pl' => 'Wybrany kierunek',
			'en' => 'Selected program',
			'uk' => 'Обраний напрям',
			'ru' => 'Выбранное направление',
		),
		'program'           => array(
			'pl' => 'Program',
			'en' => 'Program',
			'uk' => 'Програма',
			'ru' => 'Программа',
		),
		'options'           => array(
			'pl' => 'opcji',
			'en' => 'options',
			'uk' => 'варіантів',
			'ru' => 'вариантов',
		),
		'chooseProgram'     => array(
			'pl' => 'wybierz swój program',
			'en' => 'choose your program',
			'uk' => 'оберіть свою програму',
			'ru' => 'выберите свою программу',
		),
		'studyMode'         => array(
			'pl' => 'Forma studiów',
			'en' => 'Study mode',
			'uk' => 'Форма навчання',
			'ru' => 'Форма обучения',
		),
		'countryGroup'      => array(
			'pl' => 'Country group',
			'en' => 'Country group',
			'uk' => 'Група країн',
			'ru' => 'Группа стран',
		),
		'paymentOption'     => array(
			'pl' => 'Wariant płatności',
			'en' => 'Payment option',
			'uk' => 'Варіант оплати',
			'ru' => 'Вариант оплаты',
		),
		'terms'             => array(
			'pl' => 'Regulamin',
			'en' => 'Terms',
			'uk' => 'Регламент',
			'ru' => 'Регламент',
		),
		'swipeRight'        => array(
			'pl' => 'Przesuń →',
			'en' => 'Swipe →',
			'uk' => 'Гортайте →',
			'ru' => 'Листайте →',
		),
		'swipeLeft'         => array(
			'pl' => '← Przesuń',
			'en' => '← Swipe',
			'uk' => '← Гортайте',
			'ru' => '← Листайте',
		),
		'mostPopular'       => array(
			'pl' => 'Najczęściej wybierany',
			'en' => 'Most popular',
			'uk' => 'Найчастіше обирають',
			'ru' => 'Чаще всего выбирают',
		),
		'discounts'         => array(
			'pl' => 'Zniżki i promocje',
			'en' => 'Discounts and promotions',
			'uk' => 'Знижки та акції',
			'ru' => 'Скидки и акции',
		),
		'expand'            => array(
			'pl' => 'Rozwiń',
			'en' => 'Expand',
			'uk' => 'Розгорнути',
			'ru' => 'Развернуть',
		),
		'rekrBadge'         => array(
			'pl' => 'na opłatę rekrutacyjną',
			'en' => 'on the recruitment fee',
			'uk' => 'на вступний внесок',
			'ru' => 'на вступительный взнос',
		),
		'oneTimeFees'       => array(
			'pl' => 'Opłaty jednorazowe przy zapisie',
			'en' => 'One-time fees on enrollment',
			'uk' => 'Одноразові платежі при записі',
			'ru' => 'Разовые платежи при записи',
		),
		'feeAdmission'      => array(
			'pl' => 'Opłata rekrutacyjna',
			'en' => 'Recruitment fee',
			'uk' => 'Вступний внесок',
			'ru' => 'Вступительный взнос',
		),
		'feeApplication'    => array(
			'pl' => 'Opłata aplikacyjna',
			'en' => 'Application fee',
			'uk' => 'Аплікаційний внесок',
			'ru' => 'Аппликационный взнос',
		),
		'feeEntry'          => array(
			'pl' => 'Wpisowe',
			'en' => 'Enrollment fee',
			'uk' => 'Вписове',
			'ru' => 'Вступительный сбор',
		),
		'feeTotal'          => array(
			'pl' => 'Razem przy zapisie',
			'en' => 'Total on enrollment',
			'uk' => 'Разом при записі',
			'ru' => 'Итого при записи',
		),
		'ctaMore'           => array(
			'pl' => 'Więcej o programie →',
			'en' => 'More about the program →',
			'uk' => 'Більше про програму →',
			'ru' => 'Подробнее о программе →',
		),
		'ctaApply'          => array(
			'pl' => 'Zapisz się →',
			'en' => 'Apply now →',
			'uk' => 'Записатися →',
			'ru' => 'Записаться →',
		),
		'modeFullTime'      => array(
			'pl' => 'Stacjonarne',
			'en' => 'Full-time',
			'uk' => 'Стаціонарне',
			'ru' => 'Очное',
		),
		'modePartTime'      => array(
			'pl' => 'Niestacjonarne',
			'en' => 'Part-time',
			'uk' => 'Заочне',
			'ru' => 'Заочное',
		),
		'savePrefix'        => array(
			'pl' => 'oszczędzasz',
			'en' => 'You save',
			'uk' => 'економите',
			'ru' => 'экономите',
		),
		'savePerYearSuffix' => array(
			'pl' => '/rok',
			'en' => '/year',
			'uk' => '/рік',
			'ru' => '/год',
		),
		'insteadOfPrefix'   => array(
			'pl' => 'zamiast',
			'en' => 'instead of',
			'uk' => 'замість',
			'ru' => 'вместо',
		),
		'andSaveText'       => array(
			'pl' => '— oszczędzasz',
			'en' => '— you save',
			'uk' => '— економите',
			'ru' => '— экономите',
		),
		'emptyTitle'        => array(
			'pl' => 'Cennik w przygotowaniu',
			'en' => 'Pricing coming soon',
			'uk' => 'Прайс у підготовці',
			'ru' => 'Прайс в подготовке',
		),
		'emptyText'         => array(
			'pl' => 'Wkrótce udostępnimy aktualny cennik dla tego programu. Jeśli chcesz, skontaktuj się z nami — chętnie pomożemy.',
			'en' => 'We will publish the updated pricing for this program soon. If you need help, contact us — we’ll be happy to assist.',
			'uk' => 'Незабаром опублікуємо актуальний прайс для цієї програми. Якщо потрібна допомога — зв’яжіться з нами.',
			'ru' => 'Скоро опубликуем актуальный прайс для этой программы. Если нужна помощь — свяжитесь с нами.',
		),
		'deg1'              => array(
			'pl' => 'Studia I stopnia',
			'en' => 'Bachelor studies',
			'uk' => 'Навчання I ступеня',
			'ru' => 'Обучение I ступени',
		),
		'deg2'              => array(
			'pl' => 'Studia II stopnia',
			'en' => 'Master studies',
			'uk' => 'Навчання II ступеня',
			'ru' => 'Обучение II ступени',
		),
		'deg1Opt'           => array(
			'pl' => 'Studia I stopnia',
			'en' => 'Bachelor / BSc',
			'uk' => 'Навчання I ступеня',
			'ru' => 'Обучение I ступени',
		),
		'deg2Opt'           => array(
			'pl' => 'Studia II stopnia',
			'en' => 'Master / MA',
			'uk' => 'Навчання II ступеня',
			'ru' => 'Обучение II ступени',
		),
		'planR12'           => array(
			'pl' => '12 rat miesięcznych',
			'en' => '12 monthly instalments',
			'uk' => '12 щомісячних платежів',
			'ru' => '12 ежемесячных платежей',
		),
		'planR10'           => array(
			'pl' => '10 rat miesięcznych',
			'en' => '10 monthly instalments',
			'uk' => '10 щомісячних платежів',
			'ru' => '10 ежемесячных платежей',
		),
		'planSem'           => array(
			'pl' => 'Semestr z góry',
			'en' => 'Pay per semester',
			'uk' => 'Семестр наперед',
			'ru' => 'Семестр вперёд',
		),
		'planRok'           => array(
			'pl' => 'Rok z góry',
			'en' => 'Pay annually',
			'uk' => 'Рік наперед',
			'ru' => 'Год вперёд',
		),
		'fromPrefix'        => array(
			'pl' => 'już od',
			'en' => 'from',
			'uk' => 'від',
			'ru' => 'от',
		),
		'conditionsTitle'   => array(
			'pl' => 'Warunki skorzystania z promocji:',
			'en' => 'Promotion terms:',
			'uk' => 'Умови акції:',
			'ru' => 'Условия акции:',
		),
		'standardFeeLabel'  => array(
			'pl' => 'standardowo',
			'en' => 'normally',
			'uk' => 'стандартно',
			'ru' => 'стандартно',
		),
		'rekrNote'          => array(
			'pl' => 'Obie promocje nie łączą się ze sobą nawzajem. Kandydat wybiera jedną. Wyboru dokonuje najpóźniej w dniu zawarcia umowy z uczelnią.',
			'en' => 'The two promotions cannot be combined. The candidate chooses one. The choice must be made no later than on the day of signing the contract with the university.',
			'uk' => 'Дві акції не поєднуються. Кандидат обирає одну. Вибір — не пізніше дня укладення договору з університетом.',
			'ru' => 'Две акции не суммируются. Кандидат выбирает одну. Выбор — не позднее дня заключения договора с университетом.',
		),
		'rekrAbsName'       => array(
			'pl' => '0 zł opłaty rekrutacyjnej — Absolwent ATA / WAB / WSEiZ / WSH',
			'en' => 'PLN 0 recruitment fee — ATA / WAB / WSEiZ / WSH graduate',
			'uk' => '0 zł вступного внеску — випускник ATA / WAB / WSEiZ / WSH',
			'ru' => '0 zł вступительного взноса — выпускник ATA / WAB / WSEiZ / WSH',
		),
		'rekrAbsTag'        => array(
			'pl' => '−{amount} zł',
			'en' => '−{amount} PLN',
			'uk' => '−{amount} zł',
			'ru' => '−{amount} zł',
		),
		'rekrAbsShort'      => array(
			'pl' => 'Rejestracja do {regShort} · umowa do {contract} · oszczędzasz {amount} zł',
			'en' => 'Registration by {regShort} · contract by {contract} · you save {amount} PLN',
			'uk' => 'Реєстрація до {regShort} · договір до {contract} · економите {amount} zł',
			'ru' => 'Регистрация до {regShort} · договор до {contract} · экономите {amount} zł',
		),
		'rekrAbsFull'       => array(
			'pl' => "Jesteś absolwentem Wrocławskiej Akademii Biznesu (WAB), Wyższej Szkoły Handlowej (WSH), WSEiZ lub ATA, który/a ukończył/a studia I stopnia i aplikujesz na I semestr studiów II stopnia w języku polskim.\nZarejestrujesz się w systemie rekrutacyjnym do {reg}.\nPodpiszesz umowę o warunkach pobierania opłat do {contract}.",
			'en' => "You are a graduate of Wrocław Business Academy (WAB), Wrocław University of Business (WSH), University of Ecology and Management (WSEiZ) or Academy of Fine Arts and Technology (ATA) who completed first-cycle studies and are applying for the 1st semester of second-cycle studies in Polish.\nYou will register in the recruitment system by {reg}.\nYou will sign the fee agreement by {contract}.",
			'uk' => "Ви випускник Wrocławskiej Akademii Biznesu (WAB), Wyższej Szkoły Handlowej (WSH), WSEiZ або ATA, який/яка закінчив/ла навчання I ступеня і подаєтеся на I семестр навчання II ступеня польською мовою.\nЗареєструєтеся в системі рекрутації до {reg}.\nПідпишете договір про умови оплати до {contract}.",
			'ru' => "Вы выпускник Wrocławskiej Akademii Biznesu (WAB), Wyższej Szkoły Handlowej (WSH), WSEiZ или ATA, окончивший обучение I ступени и поступающий на I семестр обучения II ступени на польском языке.\nЗарегистрируетесь в системе рекрутации до {reg}.\nПодпишете договор об условиях оплаты до {contract}.",
		),
		'rekrKursName'      => array(
			'pl' => '0 zł opłaty rekrutacyjnej — Absolwent kursu językowego ATA',
			'en' => 'PLN 0 recruitment fee — ATA language course graduate',
			'uk' => '0 zł вступного внеску — випускник мовного курсу ATA',
			'ru' => '0 zł вступительного взноса — выпускник языкового курса ATA',
		),
		'rekrKursShort'     => array(
			'pl' => 'Rejestracja do {regShort} · umowa do {contract}',
			'en' => 'Registration by {regShort} · contract by {contract}',
			'uk' => 'Реєстрація до {regShort} · договір до {contract}',
			'ru' => 'Регистрация до {regShort} · договор до {contract}',
		),
		'rekrKursFull'      => array(
			'pl' => "Ukończyłeś/aś przygotowawczy kurs językowy prowadzony w ATA (opłata rekrutacyjna za kurs musi być wcześniej uiszczona).\nRejestrujesz się na I semestr studiów I lub II stopnia (j. polski lub angielski) na rok ak. 2026/2027 do {reg}.\nPodpiszesz umowę o warunkach pobierania opłat do {contract}.",
			'en' => "You have completed a preparatory language course at ATA (the course recruitment fee must already have been paid).\nYou are registering for the 1st semester of first- or second-cycle studies (Polish or English) for the 2026/2027 academic year by {reg}.\nYou will sign the fee agreement by {contract}.",
			'uk' => "Ви закінчили підготовчий мовний курс в ATA (вступний внесок за курс уже сплачено).\nРеєструєтеся на I семестр навчання I або II ступеня (польська або англійська) на 2026/2027 н.р. до {reg}.\nПідпишете договір про умови оплати до {contract}.",
			'ru' => "Вы окончили подготовительный языковой курс в ATA (вступительный взнос за курс уже оплачен).\nРегистрируетесь на I семестр обучения I или II ступени (польский или английский) на 2026/2027 уч. г. до {reg}.\nПодпишете договор об условиях оплаты до {contract}.",
		),
	);
}

/**
 * @param string $key
 * @param string $lang
 * @return string
 */
function akademiata_prices_ui_t($key, $lang) {
	$all = akademiata_prices_calculator_ui_strings();
	if (!isset($all[ $key ])) {
		return '';
	}
	return akademiata_prices_ui_pick($lang, $all[ $key ]);
}

/**
 * Sheet promo card overrides for calculator (EN/UK/RU UI).
 *
 * @return array<string, array<string, array<string, mixed>>>
 */
function akademiata_prices_calculator_promo_overrides_by_lang() {
	$promo_i18n = get_template_directory() . '/configure/front-page-defaults/home-promos/promo-i18n.php';
	if (!function_exists('akademiata_home_promos_promo_i18n_catalog') && is_readable($promo_i18n)) {
		require_once $promo_i18n;
	}
	$home = function_exists('akademiata_home_promos_promo_i18n_catalog')
		? akademiata_home_promos_promo_i18n_catalog()
		: array();

	$full = array(
		'en' => array(
			'jednorazowo'  => 'Deadline: 10 September (winter / full year) or 10 March (summer). Cannot be combined with "Transfer to ATA" and "Graduate continues with discount (PL)".',
			'szybki'       => 'Register by 30.09.2026 and sign the contract by 30.10.2026. Discount is split proportionally across both semesters. Can be combined with "Cheaper in a group" and upfront payment discount.',
			'grupie'       => '2–4 people = 200 PLN, 5+ people = 400 PLN. Documents must be submitted on the same day.',
			'techart'      => 'The profile must be clearly indicated by the school name or track on the diploma/certificate.',
			'przejscie'    => 'Cannot be combined with any other promotion. Not available to candidates previously removed from ATA/WSEiZ.',
			'absolwent_pl' => 'Grade 5.0 (Wrocław) = 30%. Cannot be combined with other promotions.',
		),
		'uk' => array(
			'jednorazowo'  => 'Термін: 10 вересня (зима / рік) або 10 березня (літо). Не поєднується з «Перехід до ATA» та «Абітурієнт продовжує зі знижкою».',
			'szybki'       => 'Реєстрація до 30.09.2026 і підписання договору до 30.10.2026. Знижка пропорційно на обидва семестри. Можна поєднати з «В групі дешевше» та оплатою наперед.',
			'grupie'       => '2–4 особи = 200 zł, 5+ = 400 zł. Документи подають того самого дня.',
			'techart'      => 'Профіль має бути чітко вказаний у назві школи або на дипломі/атестаті.',
			'przejscie'    => 'Не поєднується з іншими акціями. Не для кандидатів, раніше відрахованих з ATA/WSEiZ.',
			'absolwent_pl' => 'Оцінка 5.0 (Вроцлав) = 30%. Не поєднується з іншими акціями.',
		),
		'ru' => array(
			'jednorazowo'  => 'Срок: 10 сентября (зима / год) или 10 марта (лето). Не суммируется с «Переход в ATA» и «Абитуриент продолжает со скидкой».',
			'szybki'       => 'Регистрация до 30.09.2026 и подписание договора до 30.10.2026. Скидка пропорционально на оба семестра. Можно совместить с «В группе дешевле» и оплатой вперёд.',
			'grupie'       => '2–4 человека = 200 zł, 5+ = 400 zł. Документы подают в один день.',
			'techart'      => 'Профиль должен быть ясно указан в названии школы или в дипломе/аттестате.',
			'przejscie'    => 'Не суммируется с другими акциями. Не для кандидатов, ранее отчисленных из ATA/WSEiZ.',
			'absolwent_pl' => 'Оценка 5.0 (Вроцлав) = 30%. Не суммируется с другими акциями.',
		),
	);

	$so = array(
		'en' => array(
			'grupie'       => array(
				array( 'v' => 200, 'l' => '2–4 people (−200 PLN)' ),
				array( 'v' => 400, 'l' => '5+ people (−400 PLN)' ),
			),
			'absolwent_pl' => array(
				array( 'v' => 0.2, 'l' => 'Standard result (−20%)' ),
				array( 'v' => 0.3, 'l' => 'Grade 5.0 / Wrocław (−30%)' ),
			),
		),
		'uk' => array(
			'grupie'       => array(
				array( 'v' => 200, 'l' => '2–4 особи (−200 zł)' ),
				array( 'v' => 400, 'l' => '5+ осіб (−400 zł)' ),
			),
			'absolwent_pl' => array(
				array( 'v' => 0.2, 'l' => 'Стандартний результат (−20%)' ),
				array( 'v' => 0.3, 'l' => 'Оцінка 5.0 / Вроцлав (−30%)' ),
			),
		),
		'ru' => array(
			'grupie'       => array(
				array( 'v' => 200, 'l' => '2–4 человека (−200 zł)' ),
				array( 'v' => 400, 'l' => '5+ человек (−400 zł)' ),
			),
			'absolwent_pl' => array(
				array( 'v' => 0.2, 'l' => 'Стандартный результат (−20%)' ),
				array( 'v' => 0.3, 'l' => 'Оценка 5.0 / Вроцлав (−30%)' ),
			),
		),
	);

	$out = array();
	foreach ( array( 'en', 'uk', 'ru' ) as $lang ) {
		$out[ $lang ] = array();
		$ids          = array( 'jednorazowo', 'szybki', 'grupie', 'techart', 'przejscie', 'absolwent_pl' );
		foreach ( $ids as $id ) {
			$base = isset($home[ $lang ][ $id ]) && is_array($home[ $lang ][ $id ])
				? $home[ $lang ][ $id ]
				: array();
			$row  = array(
				'name'  => (string) ( $base['name'] ?? '' ),
				'tag'   => (string) ( $base['tag'] ?? '' ),
				'short' => (string) ( $base['short'] ?? '' ),
				'full'  => (string) ( $full[ $lang ][ $id ] ?? '' ),
			);
			if (!empty($so[ $lang ][ $id ])) {
				$row['so'] = $so[ $lang ][ $id ];
			}
			$out[ $lang ][ $id ] = $row;
		}
	}

	return $out;
}

/**
 * JSON payload for #prices-i18n (current UI language resolved where needed).
 *
 * @param string $ui_lang
 * @return array<string, mixed>
 */
function akademiata_prices_calculator_i18n_payload($ui_lang) {
	$t = static function ($key) use ($ui_lang) {
		return akademiata_prices_ui_t($key, $ui_lang);
	};

	$strings = akademiata_prices_calculator_ui_strings();
	$by_lang = static function ($key) use ($strings) {
		return $strings[ $key ] ?? array();
	};

	return array(
		'ctaMore'           => $t('ctaMore'),
		'ctaApply'          => $t('ctaApply'),
		'feeAdmission'      => $t('feeAdmission'),
		'feeApplication'    => $t('feeApplication'),
		'feeEntry'          => $t('feeEntry'),
		'feeTotal'          => $t('feeTotal'),
		'modeFullTime'      => $t('modeFullTime'),
		'modePartTime'      => $t('modePartTime'),
		'mostPopular'       => $t('mostPopular'),
		'savePrefix'        => $t('savePrefix'),
		'savePerYearSuffix' => $t('savePerYearSuffix'),
		'insteadOfPrefix'   => $t('insteadOfPrefix'),
		'andSaveText'       => $t('andSaveText'),
		'deg1'              => $t('deg1'),
		'deg2'              => $t('deg2'),
		'deg1Opt'           => $t('deg1Opt'),
		'deg2Opt'           => $t('deg2Opt'),
		'planR12'           => $t('planR12'),
		'planR10'           => $t('planR10'),
		'planSem'           => $t('planSem'),
		'planRok'           => $t('planRok'),
		'fromPrefix'        => $t('fromPrefix'),
		'conditionsTitle'   => $t('conditionsTitle'),
		'standardFeeLabel'  => $t('standardFeeLabel'),
		'rekrPromo'         => array(
			'sectionTitle'     => $t('discounts'),
			'sectionBadge'     => $t('rekrBadge'),
			'conditionsTitle'  => $t('conditionsTitle'),
			'note'             => $t('rekrNote'),
			'standardFeeLabel' => $t('standardFeeLabel'),
			'absolwent'        => array(
				'name'  => $t('rekrAbsName'),
				'tag'   => $t('rekrAbsTag'),
				'short' => $t('rekrAbsShort'),
				'full'  => $t('rekrAbsFull'),
			),
			'kurs'             => array(
				'name'  => $t('rekrKursName'),
				'tag'   => $t('rekrAbsTag'),
				'short' => $t('rekrKursShort'),
				'full'  => $t('rekrKursFull'),
			),
		),
		'promoOverridesByLang' => akademiata_prices_calculator_promo_overrides_by_lang(),
		// Back-compat for older JS that only reads EN map.
		'promoOverrides'    => akademiata_prices_calculator_promo_overrides_by_lang()['en'] ?? array(),
		'emptyTitle'        => $t('emptyTitle'),
		'emptyText'         => $t('emptyText'),
		'emptyTitleByLang'  => $by_lang('emptyTitle'),
		'emptyTextByLang'   => $by_lang('emptyText'),
	);
}
