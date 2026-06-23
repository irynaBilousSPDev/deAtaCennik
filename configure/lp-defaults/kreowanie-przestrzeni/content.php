<?php

/**
 * Default LP content (LP 03.1) — used when ACF fields / repeaters are empty.
 *
 * @return array<string, array<string, mixed>>
 */
return [
    'kreo_hero_section' => [
        'watermark' => 'kreowanie przestrzeni',
        'eyebrow' => 'Kreowanie przestrzeni / studia po maturze',
        'title' => 'Projektuj przestrzeń, w której ludzie żyją, pracują i odpoczywają.',
        'lead' => 'Wybierz obszar, w którym ATA jest wyjątkowo mocna. Architektura, architektura wnętrz lub krajobrazu, budownictwo i ochrona środowiska to kierunki dla osób, które chcą mieć realny wpływ na wygląd miast, budynków, mieszkań, parków i przestrzeni publicznych.',
        'cta_primary_text' => 'Zobacz kierunki',
        'cta_primary_url' => '',
        'cta_secondary_text' => 'Dopasuj studia do siebie',
        'cta_secondary_url' => '',
        'chips' => [
            ['text' => 'Wrocław', 'is_orange' => 1],
            ['text' => 'Architektura', 'is_orange' => 0],
            ['text' => 'Architektura wnętrz', 'is_orange' => 0],
            ['text' => 'Architektura krajobrazu', 'is_orange' => 0],
            ['text' => 'Budownictwo', 'is_orange' => 0],
            ['text' => 'Ochrona środowiska', 'is_orange' => 0],
        ],
        'floating_title' => 'Przestrzeń ma znaczenie',
        'floating_text' => 'Tu uczysz się projektować nie tylko ładnie, ale też funkcjonalnie, odpowiedzialnie i z myślą o użytkowniku.',
        'city_tag' => 'Wrocław',
        'city_window_image' => null,
    ],
    'kreo_cards_section' => [
        'watermark' => 'kierunki',
        'eyebrow' => 'Oferta w obszarze kreowania przestrzeni',
        'title' => 'Kreowanie przestrzeni - wybierz kierunek odpowiedni dla siebie!',
        'intro' => 'Ten obszar łączy projektowanie, technikę, estetykę, środowisko i myślenie o człowieku. Możesz wybrać kierunki związane z architekturą, architekturą wnętrz lub krajobrazu, budownictwem lub ochroną środowiska.',
        'items' => [
            [
                'title' => 'Architektura',
                'text' => 'Projektowanie budynków, miast i przestrzeni, w których codziennie funkcjonują ludzie. Kierunek dla osób, które chcą łączyć kreatywność z techniką i odpowiedzialnością projektową.',
                'meta' => [
                    ['label' => 'I stopnia'],
                    ['label' => 'inżynier architekt'],
                    ['label' => '4 lata'],
                    ['label' => 'PL / EN'],
                ],
            ],
            [
                'title' => 'Architektura wnętrz',
                'text' => 'Kierunek dla osób, które chcą projektować mieszkania, biura, hotele, lokale usługowe, przestrzenie wystawiennicze i scenografie. Studia I stopnia dostępne są również we Wrocławiu.',
                'meta' => [
                    ['label' => 'Warszawa'],
                    ['label' => 'Wrocław'],
                    ['label' => 'licencjat'],
                    ['label' => '3,5 roku'],
                    ['label' => 'praktyka 63%'],
                ],
            ],
            [
                'title' => 'Architektura krajobrazu',
                'text' => 'Projektowanie zieleni, parków, ogrodów, terenów rekreacyjnych i przestrzeni publicznych. Dla osób, które chcą łączyć projektowanie z naturą i jakością życia.',
                'meta' => [
                    ['label' => 'I stopnia'],
                    ['label' => 'inżynier'],
                    ['label' => 'kreowanie przestrzeni'],
                ],
            ],
            [
                'title' => 'Budownictwo',
                'text' => 'Dla kandydatów, którzy chcą rozumieć, jak powstają budynki i infrastruktura. To techniczna ścieżka dla osób zainteresowanych inwestycjami, realizacją i procesem budowlanym.',
                'meta' => [
                    ['label' => 'I stopnia'],
                    ['label' => 'inżynier'],
                    ['label' => '3,5 roku'],
                ],
            ],
            [
                'title' => 'Ochrona środowiska',
                'text' => 'Kierunek dla osób, które chcą projektować przyszłość odpowiedzialnie. Łączy wiedzę o środowisku, gospodarce wodnej, odpadach, zieleni i zrównoważonym rozwoju.',
                'meta' => [
                    ['label' => 'I stopnia'],
                    ['label' => 'inżynier'],
                    ['label' => 'środowisko'],
                ],
            ],
            [
                'title' => 'Nie wiesz, co wybrać?',
                'text' => 'Rozwiąż quiz ATA i sprawdź, które studia najlepiej pasują do Twojej osobowości, zainteresowań i sposobu działania.',
                'meta' => [
                    ['label' => 'quiz'],
                    ['label' => 'rekomendacje'],
                    ['label' => 'dla niezdecydowanych'],
                ],
            ],
        ],
    ],
    'kreo_split_section' => [
        'watermark' => 'Wrocław',
        'dark_watermark' => 'Wrocław',
        'dark_title' => 'Wrocław to najlepsze miejsce, aby wybrać studia związane z kreowaniem przestrzeni.',
        'dark_text' => 'To miasto architektury, nowych inwestycji, designu, uczelni, kultury i intensywnego życia studenckiego. Dla kandydatów na studia związane z kreowaniem przestrzeni Wrocław jest nie tylko świetną lokalizacją, ale też codziennym źródłem inspiracji.',
        'dark_cta_text' => 'Zobacz kierunki we Wrocławiu',
        'dark_cta_url' => '',
        'list_title' => 'Dlaczego warto studiować na kierunkach związanych z kreowaniem przestrzeni we Wrocławiu?',
        'list_items' => [
            ['text' => 'We Wrocławiu dostępna jest między innymi Architektura wnętrz na studiach I stopnia, z tytułem licencjata (czas trwania - 3,5 roku).'],
            ['text' => 'Kandydaci uczą się projektowania wnętrz od koncepcji po projekt wykonawczy, pracy 2D i 3D oraz komunikacji z klientem.'],
            ['text' => 'Na naszej uczelni będziesz pracować w nowoczesnych pracowniach, z narzędziami komputerowymi, tabletami graficznymi i manipulatorami 3D. Posiadamy nawet laboratorium symulacji odczuć osób z niepełnosprawnościami.'],
            ['text' => 'Wrocław to miasto różnorodnej architektury, nowych inwestycji, zielonych przestrzeni i miejsc kultury, które mogą stać się naturalną inspiracją dla studentów projektujących przestrzeń.'],
        ],
    ],
    'kreo_proof_section' => [
        'watermark' => 'lider',
        'eyebrow' => 'Dlaczego warto studiować na kierunkach związanych z kreowaniem przestrzeni na ATA?',
        'title' => 'Studiuj tam, gdzie kierunki projektowe naprawdę wyróżniają się wynikami.',
        'intro' => 'ATA ma bardzo mocne argumenty dla kandydatów zainteresowanych architekturą, wnętrzami, krajobrazem, budownictwem i środowiskiem. To kierunki z wysokimi wynikami ELA, praktycznym podejściem do nauki, nowoczesnymi pracowniami i projektami realizowanymi w kontakcie z prawdziwą przestrzenią.',
        'items' => [
            [
                'number' => '1',
                'title' => 'miejsca w ELA 2025',
                'text' => 'Architektura, Architektura Wnętrz, Architektura Krajobrazu i Ochrona Środowiska znalazły się wśród kierunków ATA z 1. miejscem w Polsce.',
            ],
            [
                'number' => '2',
                'title' => 'miejsce dla Budownictwa I stopnia',
                'text' => 'Budownictwo I stopnia zostało pokazane przez ATA w czołówce rankingu ELA 2025. To mocny argument dla osób, które myślą o technicznej ścieżce rozwoju.',
            ],
            [
                'number' => '30',
                'title' => 'lat doświadczenia',
                'text' => 'Za ATA stoi wieloletnia tradycja kształcenia oraz rozwijania kierunków technicznych, artystycznych i projektowych.',
            ],
            [
                'number' => '2',
                'title' => 'miasta: Wrocław i Warszawa',
                'text' => 'Projektuj przestrzenie w jednym z dwóch dużych miast akademickich. To kierunki, które naprawdę mogą "wykreować" Twoją przyszłość.',
            ],
        ],
    ],
    'kreo_events_section' => [
        'watermark' => 'wydarzenia',
        'eyebrow' => 'Co naprawdę dzieje się na uczelni?',
        'title' => 'Studenci projektują, wystawiają prace i spotykają praktyków.',
        'intro' => 'Na ATA nauka nie kończy się na planie zajęć. Studenci uczestniczą w wykładach praktyków, przygotowują projekty dla konkretnych miejsc, pokazują swoje prace na wystawach i poznają realne wyzwania projektowe.',
        'items' => [
            [
                'tag' => 'Akademia Architektury',
                'title' => 'Spotkania z uznanymi architektami',
                'text' => 'ATA prowadzi cykl Akademia Architektury. W programie pojawili się między innymi Szymon Wojciechowski z APA Wojciechowski, Dorota Szlachcic ze Szlachcic Architekci i Natalia Paszkowska z WWAA.',
            ],
            [
                'tag' => 'wystawy studentów',
                'title' => 'Port rzeczny w Górze Kalwarii',
                'text' => 'Wystawa prac studentów Wydziału Architektury ATA pokazywała autorskie wizje młodych projektantów dla nadwiślańskich terenów Góry Kalwarii.',
            ],
            [
                'tag' => 'projekty dla miasta',
                'title' => 'Gmina Wyszków x ATA',
                'text' => 'W ramach współpracy zaprezentowano blisko 100 plansz z koncepcjami urbanistycznymi, makiety zagospodarowania terenów oraz propozycje identyfikacji wizualnej Gminy Wyszków.',
            ],
            [
                'tag' => 'Projekt Stajnia',
                'title' => 'Architektura jako odpowiedź na realny problem',
                'text' => 'Dwie drużyny z ATA przygotowały koncepcje budynku stajni dworskiej w Wyszkowie i terenu przyległego. To przykład pracy na rzeczywistym kontekście przestrzennym i historycznym.',
            ],
            [
                'tag' => 'AI i projektowanie',
                'title' => 'Projekt od A do Z, porady praktyków',
                'text' => 'Spotkanie o AI w budownictwie i projektowaniu wnętrz pokazało praktyczne zastosowania sztucznej inteligencji w pracy inżynierów i projektantów.',
            ],
            [
                'tag' => 'współpraca z branżą',
                'title' => 'Partnerstwa, wizyty, konkursy',
                'text' => 'Współpraca ATA z ZHMB Mirad obejmuje projekty z architektury, krajobrazu, wnętrz i wzornictwa, a także wykłady, prezentacje, wizyty studyjne, wystawy i konkursy dla studentów.',
            ],
        ],
    ],
    'kreo_quiz_section' => [
        'eyebrow' => 'Nie wiesz, który kierunek wybrać?',
        'title' => 'Dopasuj studia do siebie.',
        'text' => 'Na stronie ATA możesz rozwiązać quiz, który pomaga dobrać kierunek do osobowości i zainteresowań. To dobry krok dla kandydatów, którzy wiedzą, że chcą projektować przestrzeń, ale nie są pewni, czy bliżej im do architektury, architektury wnętrz, krajobrazu, budownictwa, czy ochrony środowiska.',
        'cta_primary_text' => 'Rozwiąż quiz',
        'cta_primary_url' => '',
        'cta_secondary_text' => 'Zobacz kierunki',
        'cta_secondary_url' => '',
        'card_title' => 'Quiz pomoże Ci sprawdzić:',
        'card_points' => [
            ['text' => 'czy wolisz projektować wnętrza, budynki czy krajobrazy,'],
            ['text' => 'czy bardziej kręci Cię estetyka, technika czy środowisko,'],
            ['text' => 'czy chcesz pracować nad detalem czy dużą przestrzenią,'],
            ['text' => 'czy bliżej Ci do kreatywnego projektowania czy praktycznych rozwiązań.'],
        ],
    ],
    'kreo_final_section' => [
        'title' => 'Wrocław, Warszawa - dwa miasta, w których naprawdę możesz "wykreować" swoją przyszłość.',
        'text' => 'Sprawdź kierunki, porównaj możliwości i zobacz, na jakich studiach możesz rozpocząć nową przygodę życia tuż po maturze.',
        'cta_primary_text' => 'Zobacz kierunki',
        'cta_primary_url' => '',
        'cta_secondary_text' => 'Zapisz się',
        'cta_secondary_url' => '',
    ],
];
