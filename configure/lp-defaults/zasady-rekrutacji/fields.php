<?php

require_once dirname(__DIR__) . '/merge.php';

/**
 * @return array<string, array<string, mixed>>
 */
function akademiata_zasady_rekrutacji_defaults(): array {
    return require __DIR__ . '/content.php';
}

/**
 * @param array<string, mixed>|false $acf_fields
 * @return array<string, array<string, mixed>>
 */
function akademiata_zasady_rekrutacji_fields($acf_fields): array {
    $defaults = akademiata_zasady_rekrutacji_defaults();
    $acf_fields = is_array($acf_fields) ? $acf_fields : [];
    $merged = [];

    foreach ($defaults as $section_key => $section_defaults) {
        $merged[$section_key] = akademiata_lp_merge_defaults(
            $section_defaults,
            $acf_fields[$section_key] ?? null
        );
    }

    return $merged;
}

/**
 * @return array<string, string>
 */
function akademiata_zasady_rekrutacji_static_images(): array {
    return [
        'hero'      => 'rekrutacja-hero.png',
        'reassure'  => 'rekrutacja-reassure.png',
    ];
}

/**
 * Hardcoded digital-skills section copy for the current WPML language.
 *
 * @return array{
 *     nav: string,
 *     watermark: string,
 *     eyebrow: string,
 *     title: string,
 *     intro: string,
 *     lead: string,
 *     items: array<int, string>,
 *     note: string,
 *     pdf_title: string,
 *     pdf_text: string,
 *     pdf_cta: string,
 *     pdf_url: string
 * }
 */
function akademiata_zasady_rekrutacji_digital_skills(): array {
    $lang = function_exists('akademiata_normalize_theme_lang_code')
        ? akademiata_normalize_theme_lang_code(apply_filters('wpml_current_language', 'pl'))
        : 'pl';

    $copy = [
        'pl' => [
            'nav'       => 'Kompetencje cyfrowe',
            'watermark' => 'Kompetencje',
            'eyebrow'   => 'Wymagania cyfrowe',
            'title'     => 'Kompetencje cyfrowe wymagane od kandydatów',
            'intro'     => 'Kandydaci ubiegający się o przyjęcie na studia w ATA powinni posiadać kompetencje cyfrowe umożliwiające przejście procesu rekrutacyjnego w uczelnianym internetowym systemie rekrutacyjnym, a następnie rozpoczęcie kształcenia na wybranym kierunku studiów.',
            'lead'      => 'Wymagana jest podstawowa umiejętność korzystania z komputera, w szczególności:',
            'items'     => [
                'obsługa przeglądarek internetowych,',
                'obsługa poczty elektronicznej, edytorów tekstu, np. Microsoft Word oraz arkuszy kalkulacyjnych, np. Microsoft Excel,',
                'korzystanie z urządzeń peryferyjnych umożliwiających m.in. wydruk dokumentów,',
                'podstawowa obsługa oprogramowania graficznego umożliwiającego przygotowanie zdjęcia cyfrowego,',
                'korzystanie z narzędzi do wideokonferencji, np. Zoom,',
                'korzystanie z platform i narzędzi wykorzystywanych w kształceniu na odległość i pracy zespołowej, np. Microsoft Teams, Moodle.',
            ],
            'note'      => 'Do udziału w kształceniu prowadzonym z wykorzystaniem metod i technik kształcenia na odległość niezbędny jest komputer wyposażony w kamerę i mikrofon oraz dostęp do Internetu.',
            'pdf_title' => 'Dokument PDF',
            'pdf_text'  => 'Pełna treść wymagań kompetencji cyfrowych do pobrania.',
            'pdf_cta'   => 'Pobierz PDF',
        ],
        'en' => [
            'nav'       => 'Digital skills',
            'watermark' => 'Skills',
            'eyebrow'   => 'Digital requirements',
            'title'     => 'Digital skills required of applicants',
            'intro'     => 'Applicants seeking admission to studies at ATA should have digital skills that enable them to complete the recruitment process in the university’s online recruitment system and then begin studying on their chosen programme.',
            'lead'      => 'Basic computer skills are required, in particular:',
            'items'     => [
                'using web browsers,',
                'using email, word processors such as Microsoft Word, and spreadsheets such as Microsoft Excel,',
                'using peripheral devices, including those needed to print documents,',
                'basic use of graphics software to prepare a digital photograph,',
                'using videoconferencing tools such as Zoom,',
                'using platforms and tools for distance learning and teamwork, such as Microsoft Teams and Moodle.',
            ],
            'note'      => 'To take part in education delivered with distance-learning methods and techniques, a computer with a camera and a microphone and Internet access are required.',
            'pdf_title' => 'PDF document',
            'pdf_text'  => 'Download the full digital-skills requirements.',
            'pdf_cta'   => 'Download PDF',
        ],
        'uk' => [
            'nav'       => 'Цифрові компетенції',
            'watermark' => 'Компетенції',
            'eyebrow'   => 'Цифрові вимоги',
            'title'     => 'Цифрові компетенції, необхідні кандидатам',
            'intro'     => 'Кандидати, які вступають на навчання в ATA, повинні мати цифрові компетенції, що дають змогу пройти рекрутаційний процес в університетській інтернет-системі набору, а потім розпочати навчання на обраній спеціальності.',
            'lead'      => 'Потрібне базове вміння користуватися комп’ютером, зокрема:',
            'items'     => [
                'робота з інтернет-браузерами,',
                'робота з електронною поштою, текстовими редакторами, напр. Microsoft Word, та електронними таблицями, напр. Microsoft Excel,',
                'користування периферійними пристроями, які дають змогу зокрема друкувати документи,',
                'базове користування графічним програмним забезпеченням для підготовки цифрової світлини,',
                'користування інструментами для відеоконференцій, напр. Zoom,',
                'користування платформами та інструментами дистанційного навчання й командної роботи, напр. Microsoft Teams, Moodle.',
            ],
            'note'      => 'Для участі в навчанні з використанням методів і технік дистанційного навчання необхідний комп’ютер із камерою та мікрофоном, а також доступ до Інтернету.',
            'pdf_title' => 'Документ PDF',
            'pdf_text'  => 'Завантажте повний текст вимог щодо цифрових компетенцій.',
            'pdf_cta'   => 'Завантажити PDF',
        ],
        'ru' => [
            'nav'       => 'Цифровые компетенции',
            'watermark' => 'Компетенции',
            'eyebrow'   => 'Цифровые требования',
            'title'     => 'Цифровые компетенции, требуемые от кандидатов',
            'intro'     => 'Кандидаты, поступающие на учёбу в ATA, должны обладать цифровыми компетенциями, позволяющими пройти рекрутационный процесс в университетской интернет-системе набора, а затем приступить к обучению по выбранному направлению.',
            'lead'      => 'Требуется базовое умение пользоваться компьютером, в частности:',
            'items'     => [
                'работа с интернет-браузерами,',
                'работа с электронной почтой, текстовыми редакторами, напр. Microsoft Word, и электронными таблицами, напр. Microsoft Excel,',
                'использование периферийных устройств, позволяющих в том числе печатать документы,',
                'базовое использование графического программного обеспечения для подготовки цифровой фотографии,',
                'использование инструментов для видеоконференций, напр. Zoom,',
                'использование платформ и инструментов дистанционного обучения и командной работы, напр. Microsoft Teams, Moodle.',
            ],
            'note'      => 'Для участия в обучении с использованием методов и техник дистанционного обучения необходим компьютер с камерой и микрофоном, а также доступ в Интернет.',
            'pdf_title' => 'Документ PDF',
            'pdf_text'  => 'Скачайте полный текст требований к цифровым компетенциям.',
            'pdf_cta'   => 'Скачать PDF',
        ],
    ];

    $out = $copy[$lang] ?? $copy['pl'];
    $out['pdf_url'] = 'https://akademiata.pl/wp-content/uploads/2026/09/Kompetencje-cyftowe-ATA.pdf';

    return $out;
}

/**
 * @param string $key hero|reassure
 */
function akademiata_zasady_rekrutacji_static_image_url(string $key): string {
    $files = akademiata_zasady_rekrutacji_static_images();
    if (!isset($files[$key])) {
        return '';
    }
    $path = get_template_directory() . '/static/img/' . $files[$key];
    if (!is_readable($path)) {
        return '';
    }
    return get_template_directory_uri() . '/static/img/' . $files[$key];
}
