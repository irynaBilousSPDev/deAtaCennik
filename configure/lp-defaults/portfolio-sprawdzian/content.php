<?php

/**
 * @return array<string, array<string, mixed>>
 */
return [
    'posp_hero_section' => [
        'watermark' => 'portfolio',
        'eyebrow' => 'LP03 / Kreowanie przestrzeni / portfolio i sprawdzian',
        'title' => 'Portfolio albo egzamin z rysunku? Na spokojnie!',
        'lead' => 'Na kierunkach projektowych rekrutacja może wyglądać trochę inaczej niż na standardowych studiach. Zobacz, gdzie potrzebne jest portfolio, gdzie sprawdzian z rysunku, a gdzie wystarczą dokumenty.',
        'cta_primary_text' => '',
        'cta_primary_url' => '',
        'cta_secondary_text' => 'Sprawdź zasady rekrutacji',
        'cta_secondary_url' => '',
        'panel_title' => 'Najważniejsze informacje w 15 sekund',
        'panel_items' => [
            ['text' => 'Architektura wymaga portfolio z pracami rysunkowymi.'],
            ['text' => 'Architektura wnętrz wymaga sprawdzianu uzdolnień plastycznych.'],
            ['text' => 'Architektura krajobrazu ma prostą ścieżkę rekrutacji bez portfolio.'],
            ['text' => 'Dokumenty i zasady znajdziesz na stronie każdego kierunku.'],
        ],
    ],
    'posp_cards_section' => [
        'watermark' => 'zasady',
        'eyebrow' => 'Jak wygląda rekrutacja na kierunki projektowe?',
        'title' => 'Zobacz, czego potrzebujesz na starcie.',
        'intro' => 'Nie każdy kierunek z obszaru kreowania przestrzeni ma takie same wymagania. Portfolio dotyczy architektury, sprawdzian z rysunku dotyczy architektury wnętrz, a na architekturze krajobrazu przechodzisz standardową ścieżkę z dokumentami.',
        'items' => [
            [
                'label' => 'portfolio',
                'label_is_green' => 0,
                'title' => 'Architektura',
                'text' => 'Podstawą kwalifikacji jest sprawdzian predyspozycji do zawodu w postaci oceny portfolio. Portfolio powinno zawierać od 5 do 10 prac w formacie co najmniej A3, wykonanych ołówkiem.',
                'meta' => [
                    ['label' => 'Warszawa'],
                    ['label' => 'Wrocław'],
                    ['label' => 'portfolio'],
                    ['label' => '5-10 prac'],
                    ['label' => 'format A3'],
                ],
            ],
            [
                'label' => 'egzamin z rysunku',
                'label_is_green' => 0,
                'title' => 'Architektura wnętrz',
                'text' => 'Podstawą przyjęcia jest sprawdzian uzdolnień plastycznych. Egzamin polega na wykonaniu studium martwej natury i sprawdza obserwację, kompozycję, światło, fakturę oraz wrażliwość na przestrzeń.',
                'meta' => [
                    ['label' => 'Warszawa'],
                    ['label' => 'Wrocław'],
                    ['label' => 'rysunek'],
                    ['label' => 'martwa natura'],
                    ['label' => '3 godziny we Wrocławiu'],
                ],
            ],
            [
                'label' => 'bez portfolio',
                'label_is_green' => 1,
                'title' => 'Architektura krajobrazu',
                'text' => 'Rekrutacja jest prostsza: kliknij przycisk zapisu, wypełnij formularz i dostarcz wymagane dokumenty. Ten kierunek nie wymaga portfolio ani egzaminu z rysunku.',
                'meta' => [
                    ['label' => 'Warszawa'],
                    ['label' => 'standardowa rekrutacja'],
                    ['label' => 'dokumenty'],
                ],
            ],
        ],
    ],
    'posp_table_section' => [
        'watermark' => 'porównanie',
        'eyebrow' => 'Portfolio vs sprawdzian vs dokumenty',
        'title' => 'Szybkie porównanie wymagań rekrutacyjnych.',
        'intro' => '',
        'header_col_1' => 'Co sprawdzasz?',
        'header_col_2' => 'Architektura',
        'header_col_3' => 'Architektura wnętrz',
        'header_col_4' => 'Architektura krajobrazu',
        'rows' => [
            [
                'label' => 'Czy jest portfolio?',
                'col_2' => '<span class="lp-pill lp-pill--orange">tak</span> Ocena portfolio jest częścią kwalifikacji.',
                'col_3' => '<span class="lp-pill">nie - jest sprawdzian</span> Jest sprawdzian uzdolnień plastycznych.',
                'col_4' => '<span class="lp-pill lp-pill--green">nie</span> Standardowa ścieżka rekrutacji.',
            ],
            [
                'label' => 'Co trzeba przygotować?',
                'col_2' => '5-10 prac co najmniej A3, wykonanych ołówkiem: minimum 3 rysunki architektury z natury i minimum 2 martwe natury.',
                'col_3' => 'Przygotuj się do wykonania studium martwej natury. We Wrocławiu egzamin odbywa się stacjonarnie na uczelni i trwa 3 godziny.',
                'col_4' => 'Wypełnij formularz i przygotuj dokumenty: kwestionariusz, świadectwo dojrzałości, dowód osobisty, zdjęcie i potwierdzenie opłat.',
            ],
            [
                'label' => 'Co jest oceniane?',
                'col_2' => 'Perspektywa, światłocień, proporcje i detal.',
                'col_3' => 'Obserwacja, kompozycja na płaszczyźnie, wrażliwość na przestrzeń, światło i fakturę.',
                'col_4' => 'Spełnienie wymagań formalnych i komplet dokumentów.',
            ],
            [
                'label' => 'Gdzie składa się prace?',
                'col_2' => 'Online w systemie rekrutacyjnym albo osobiście w Biurze Rekrutacji. We Wrocławiu dopuszczone są formaty PDF i JPG.',
                'col_3' => 'Sprawdzian odbywa się stacjonarnie. Po pozytywnej kwalifikacji kandydat składa dokumenty w Biurze Rekrutacji.',
                'col_4' => 'Dokumenty można złożyć zgodnie z zasadami podanymi na stronie kierunku.',
            ],
            [
                'label' => 'Miasto',
                'col_2' => '<span class="lp-pill">Warszawa</span><span class="lp-pill">Wrocław</span>',
                'col_3' => '<span class="lp-pill">Warszawa</span><span class="lp-pill">Wrocław</span>',
                'col_4' => '<span class="lp-pill">Warszawa</span>',
            ],
            [
                'label' => 'Co warto wiedzieć?',
                'col_2' => 'Nie musisz mieć profesjonalnej teczki. Liczy się umiejętność obserwacji, proporcji i rysowania przestrzeni.',
                'col_3' => 'W Warszawie ATA informuje o kursie rysunku przygotowującym do egzaminu na Architekturę wnętrz i Wzornictwo.',
                'col_4' => 'To dobra opcja dla osób, które chcą projektować zieleń i przestrzenie zewnętrzne bez dodatkowego egzaminu plastycznego.',
            ],
        ],
        'cta_text' => 'Sprawdź kierunki',
        'cta_url' => '',
    ],
    'posp_prep_section' => [
        'watermark' => 'przygotuj się',
        'eyebrow' => 'W jaki sposób przygotować się bez stresu?',
        'title' => 'Chodzi o to, abyś zaprezentował(a) swoje predyspozycje.',
        'intro' => 'Portfolio i sprawdzian mają pokazać, czy kandydat widzi proporcje, przestrzeń, światło i detal. To umiejętności, które można ćwiczyć.',
        'items' => [
            [
                'number' => '1',
                'title' => 'Ćwicz rysunek z natury',
                'text' => 'Rysuj budynki, wnętrza, ulice, proste bryły i przedmioty. Najważniejsze są obserwacja i proporcje.',
            ],
            [
                'number' => '2',
                'title' => 'Zadbaj o martwą naturę',
                'text' => 'W portfolio architektury pojawia się martwa natura, a w architekturze wnętrz jest ona podstawą sprawdzianu.',
            ],
            [
                'number' => '3',
                'title' => 'Nie zostawiaj tego na ostatni tydzień',
                'text' => 'Najlepiej zebrać prace wcześniej, wybrać najlepsze rysunki i sprawdzić wymagany format.',
            ],
            [
                'number' => '4',
                'title' => 'Zapytaj rekrutację',
                'text' => 'Jeśli nie wiesz, czy Twoje prace pasują do wymagań, zadaj pytanie. Lepiej upewnić się przed wysłaniem.',
            ],
        ],
    ],
    'posp_split_section' => [
        'watermark' => 'Warszawa',
        'dark_watermark' => 'portfolio',
        'dark_title' => 'Warszawa i Wrocław mają swoje ścieżki rekrutacji.',
        'dark_text' => 'Architektura i Architektura wnętrz dostępne są w Warszawie i we Wrocławiu, ale szczegóły składania prac, dokumentów i organizacji sprawdzianu warto zawsze sprawdzić na stronie wybranego miasta i kierunku.',
        'dark_cta_text' => 'Sprawdź kierunki',
        'dark_cta_url' => '',
        'tips_title' => 'Co kandydat powinien zapamiętać?',
        'tips_items' => [
            ['text' => 'Na Architekturę przygotowujesz portfolio z rysunkami, w tym architekturą z natury i martwą naturą.'],
            ['text' => 'Na Architekturę wnętrz przygotowujesz się do sprawdzianu z rysunku, czyli studium martwej natury.'],
            ['text' => 'Na Architekturę krajobrazu nie musisz przygotowywać portfolio, ale musisz złożyć wymagane dokumenty.'],
            ['text' => 'Na stronie każdego kierunku znajdziesz zakładkę z zasadami rekrutacji i dokładną listą dokumentów.'],
        ],
    ],
    'posp_faq_section' => [
        'watermark' => 'FAQ',
        'eyebrow' => 'Najczęstsze pytania',
        'title' => 'Portfolio nie musi być straszne.',
        'intro' => 'Proste odpowiedzi dla kandydatów, którzy dopiero zaczynają myśleć o kierunkach projektowych.',
        'items' => [
            [
                'title' => 'Czy muszę mieć profesjonalne portfolio?',
                'text' => 'Nie. Portfolio na Architekturę ma pokazać Twoje predyspozycje: obserwację, proporcje, perspektywę, światłocień i detal. Nie musisz mieć profesjonalnej teczki.',
            ],
            [
                'title' => 'Czy mogę wysłać portfolio online?',
                'text' => 'Tak, na Architekturze prace można przekazać przez system rekrutacyjny. W przypadku Wrocławia podane są formaty PDF i JPG.',
            ],
            [
                'title' => 'Czym różni się portfolio od egzaminu z rysunku?',
                'text' => 'Portfolio to zestaw wcześniej przygotowanych prac. Egzamin z rysunku wykonujesz w określonym czasie, na przykład jako studium martwej natury.',
            ],
            [
                'title' => 'Co jeśli wybieram Architekturę krajobrazu?',
                'text' => 'Ten kierunek ma prostszą ścieżkę rekrutacji. Wypełniasz formularz, dostarczasz dokumenty i nie musisz przygotowywać portfolio.',
            ],
        ],
    ],
    'posp_final_section' => [
        'title' => 'Masz wątpliwości? Zapytaj o portfolio, zanim wyślesz zgłoszenie.',
        'text' => 'Rekrutacja na kierunki projektowe może brzmieć poważnie, ale wszystko da się spokojnie zaplanować. Sprawdź wymagania, przygotuj prace lub dokumenty i wybierz kierunek dla siebie.',
        'cta_primary_text' => 'Sprawdź kierunki',
        'cta_primary_url' => '',
        'cta_secondary_text' => 'Zapisz się',
        'cta_secondary_url' => '',
    ],
];
