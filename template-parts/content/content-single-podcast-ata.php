<?php
/* Template part for single Podcast ATA (podcast-ata) — static page sections (no header/footer). */

$desktop_image = '';
if (has_post_thumbnail()) {
    $thumbnail_id = get_post_thumbnail_id(get_the_ID());
    $desktop_image = wp_get_attachment_image_src($thumbnail_id, 'program_banner')[0] ?? '';
}

if (empty($desktop_image)) {
    $desktop_image = get_template_directory_uri() . '/static/img/hero-podcast.png';
}

?>

<div class="podcast-ata-page">
    <!-- ===================== HERO ===================== -->
    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-content">
                <div class="tags">
                    <span class="pill pill-outline pill-live">ATA LIVE</span>
                    <span class="pill pill-filled">01.06.2026 · 14:00</span>
                </div>
                <h1>
                    Studia, kariera, pasja — jak wybrać <span class="accent">dobry kierunek</span>?
                </h1>
                <p class="hero-lead">
                    Pierwszy odcinek podcastu live Akademii Techniczno-Artystycznej. Otwarta rozmowa o teczce, rysunku i pierwszym roku studiów projektowych — bez mitów, bez presji.
                </p>
                <a href="#zapisz" class="cta-btn">
                    Zapisz się
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 5l7 7-7 7"/>
                    </svg>
                </a>
                <div class="hero-meta">
                    <span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        Poniedziałek, 14:00
                    </span>
                    <span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22.54 6.42a2.78 2.78 0 00-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 00-1.94 2A29 29 0 001 11.75a29 29 0 00.46 5.33A2.78 2.78 0 003.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 001.94-2 29 29 0 00.46-5.25 29 29 0 00-.46-5.33z"/><path d="M9.75 15.02l5.75-3.27-5.75-3.27v6.54z" fill="currentColor"/></svg>
                        YouTube ATA
                    </span>
                    <span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                        Pytania na live chat
                    </span>
                </div>
            </div>

            <div class="hero-visual" aria-label="Studio podcastowe ATA LIVE">
                <?php if (!empty($desktop_image)) : ?>
                    <img src="<?php echo esc_url($desktop_image); ?>" alt="Gospodynie podcastu ATA LIVE w trakcie rozmowy" class="hero-photo-img">
                <?php endif; ?>
                <div class="hero-sticker" aria-hidden="true">
                    <div class="hero-sticker-avatars">
                        <span></span><span></span><span></span>
                    </div>
                    <div class="hero-sticker-text">
                        312 zapisanych
                        <small>dołącz do dyskusji</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== TOPICS ===================== -->
    <section class="topics" id="o-czym">
        <div class="container">
            <div class="section-head">
                <span class="section-eyebrow">O pierwszym odcinku</span>
                <h2>Teczka, rysunek i pierwszy rok — z perspektywy ludzi, którzy są w środku</h2>
                <p>W premierowym odcinku rozmawiamy o tym, co najbardziej stresuje kandydatów na kierunki projektowe. Chcemy odczarować rekrutację i pokazać, że teczka nie musi być źródłem presji, a rysunek to coś znacznie więcej niż technika.</p>
            </div>

            <div class="topics-grid">
                <article class="topic-tile">
                    <div class="topic-icon" aria-hidden="true">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2"/>
                            <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>
                        </svg>
                    </div>
                    <h3>Teczka bez stresu</h3>
                    <p>Jak pokazać swój potencjał, wrażliwość i sposób patrzenia na świat — zamiast „odhaczać&quot; wymagania.</p>
                </article>

                <article class="topic-tile">
                    <div class="topic-icon" aria-hidden="true">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 19l7-7 3 3-7 7-3-3z"/>
                            <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/>
                            <path d="M2 2l7.586 7.586"/>
                            <circle cx="11" cy="11" r="2"/>
                        </svg>
                    </div>
                    <h3>Rysunek to nie tylko technika</h3>
                    <p>Narzędzie obserwacji, rozwoju i myślenia projektowego. Dlaczego jest tak ważny dla przyszłych studentów architektury.</p>
                </article>

                <article class="topic-tile">
                    <div class="topic-icon" aria-hidden="true">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/>
                            <path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01"/>
                        </svg>
                    </div>
                    <h3>Pierwszy rok od kuchni</h3>
                    <p>Jak naprawdę zaczynają się studia na Architekturze, Architekturze wnętrz i Wzornictwie.</p>
                </article>

                <article class="topic-tile">
                    <div class="topic-icon" aria-hidden="true">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="M2 17l10 5 10-5"/>
                            <path d="M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <h3>Praktyczne wskazówki, nie mity</h3>
                    <p>Konkretne odpowiedzi z perspektywy ludzi, którzy znają tę drogę od środka — wykładowcy i studentek.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- ===================== GUESTS ===================== -->
    <section class="guests" id="goscie">
        <div class="container">
            <div class="guests-head">
                <div>
                    <span class="section-eyebrow">Nasi goście</span>
                    <h2>Trzy perspektywy,<br>jedna rozmowa</h2>
                </div>
                <p>Wykładowca-praktyk i dwie studentki Architektury ATA. Ludzie, którzy mówią z doświadczenia — z pracowni, nie z folderu.</p>
            </div>

            <div class="guests-scroll" role="list">
                <article class="guest-card" role="listitem">
                    <div class="guest-photo">Zdjęcie 1:1</div>
                    <div class="guest-info">
                        <h3 class="guest-name">dr Piotr Pańkowski</h3>
                        <p class="guest-role">Doktor sztuki · artysta malarz · wykładowca ATA</p>
                        <p class="guest-bio">Na co dzień pracuje z rysunkiem, malarstwem i edukacją artystyczną. Opowie o rysunku z perspektywy praktyka: na co zwrócić uwagę przygotowując teczkę, jak pokazać swój potencjał i dlaczego rysunek jest tak ważny dla przyszłych studentów architektury.</p>
                    </div>
                </article>

                <article class="guest-card" role="listitem">
                    <div class="guest-photo">Zdjęcie 1:1</div>
                    <div class="guest-info">
                        <h3 class="guest-name">Anna Rusin</h3>
                        <p class="guest-role">Studentka Architektury ATA · laureatka Złotego Gryfa 2024</p>
                        <p class="guest-bio">Nagrodzona w kategorii Druk artystyczny. Od najmłodszych lat związana ze sztuką, rysunkiem i malarstwem. Opowie o przygotowaniach do studiów z perspektywy osoby, która konsekwentnie rozwija swój talent i pasję.</p>
                    </div>
                </article>

                <article class="guest-card" role="listitem">
                    <div class="guest-photo">Zdjęcie 1:1</div>
                    <div class="guest-info">
                        <h3 class="guest-name">Maja Dębek</h3>
                        <p class="guest-role">Studentka Architektury ATA</p>
                        <p class="guest-bio">Aktywnie rozwija swoje umiejętności artystyczne — m.in. w portrecie i tatuażu. Podzieli się studencką perspektywą na rysunek i kreatywną drogę, która może prowadzić do studiów architektonicznych.</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- ===================== SIGN-UP ===================== -->
    <section class="signup" id="zapisz">
        <div class="container signup-inner">
            <div class="signup-about">
                <span class="section-eyebrow">Dobry kierunek</span>
                <h2>Podcast dla tych, którzy chcą wybrać <span class="accent">świadomie</span></h2>
                <p>„Dobry kierunek&quot; powstał z myślą o kandydatach na studia, którzy stoją przed wyborem swojej ścieżki i chcą podjąć tę decyzję bardziej świadomie.</p>
                <p>W ATA wierzymy, że dobry kierunek to taki, który pomaga odkryć własny potencjał, rozwijać talent i krok po kroku zamieniać pasję w konkretne umiejętności.</p>

                <ul class="signup-perks">
                    <li><span class="check" aria-hidden="true">✓</span>Przypomnimy o wydarzeniu 30 minut przed startem</li>
                    <li><span class="check" aria-hidden="true">✓</span>Możliwość zadawania pytań na żywo na YouTube</li>
                    <li><span class="check" aria-hidden="true">✓</span>Dostęp do nagrania odcinka po wydarzeniu</li>
                </ul>
            </div>

            <div class="signup-form">
                <h3>Zapisz się na ATA LIVE</h3>
                <p class="form-sub">1.06.2026 · 14:00 · YouTube ATA. Link wyślemy mailowo.</p>

                <div class="wysiwyg">
                    <?php echo do_shortcode('[contact-form-7 id="f764c85" title="ATA LIVE"]'); ?>
                </div>
            </div>
        </div>
    </section>
</div>

