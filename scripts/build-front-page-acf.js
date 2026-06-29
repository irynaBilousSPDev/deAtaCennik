/**
 * Build acf-json/group_front_page.json from export + home_rankings fields.
 * Run: node scripts/build-front-page-acf.js
 */

const fs = require('fs');
const path = require('path');

const exportPath = process.argv[2] || path.join(process.env.USERPROFILE || '', 'Downloads', 'acf-export-2026-06-29.json');
const outPath = path.join(__dirname, '..', 'acf-json', 'group_front_page.json');

const homeRankingsFields = [
    {
        key: 'field_home_rank_tab',
        label: 'Rankingi',
        name: '',
        type: 'tab',
        placement: 'top',
        endpoint: 0,
        wpml_cf_preferences: 3,
    },
    {
        key: 'field_home_rankings',
        label: 'Pozycja w rankingach',
        name: 'home_rankings',
        type: 'group',
        layout: 'block',
        wpml_cf_preferences: 3,
        sub_fields: [
            { key: 'field_home_rank_eyebrow', label: 'Eyebrow', name: 'eyebrow', type: 'text', default_value: 'Pozycja ATA · Rankingi 2024–2026', wpml_cf_preferences: 2 },
            { key: 'field_home_rank_title', label: 'Tytuł', name: 'title', type: 'text', default_value: 'W czołówce polskich uczelni', wpml_cf_preferences: 2 },
            { key: 'field_home_rank_lead', label: 'Lead', name: 'lead', type: 'textarea', rows: 3, new_lines: 'br', default_value: 'Twarde dane, nie hasła — dwa niezależne źródła: ranking jakości kształcenia i realne losy absolwentów na rynku pracy.', wpml_cf_preferences: 2 },
            {
                key: 'field_home_rank_film',
                label: 'Film',
                name: 'film',
                type: 'group',
                layout: 'block',
                sub_fields: [
                    { key: 'field_home_rank_film_eyebrow', label: 'Eyebrow', name: 'eyebrow', type: 'text', default_value: 'Nasi studenci', wpml_cf_preferences: 2 },
                    { key: 'field_home_rank_film_title', label: 'Tytuł pod filmem', name: 'title', type: 'text', default_value: 'ATA Mistrzem Świata', wpml_cf_preferences: 2 },
                    { key: 'field_home_rank_film_subtitle', label: 'Podpis', name: 'subtitle', type: 'text', default_value: 'Sukces, który mówi sam za siebie.', wpml_cf_preferences: 2 },
                    { key: 'field_home_rank_film_poster', label: 'Poster (opcjonalnie)', name: 'poster', type: 'image', return_format: 'array', preview_size: 'medium', wpml_cf_preferences: 3 },
                ],
            },
            {
                key: 'field_home_rank_perspektywy',
                label: 'Perspektywy',
                name: 'perspektywy',
                type: 'group',
                layout: 'block',
                sub_fields: [
                    { key: 'field_home_rank_persp_label', label: 'Etykieta', name: 'label', type: 'text', default_value: 'Perspektywy 2026', wpml_cf_preferences: 2 },
                    { key: 'field_home_rank_persp_label_sub', label: 'Etykieta — dopisek', name: 'label_sub', type: 'text', default_value: 'uczelnie zawodowe', wpml_cf_preferences: 2 },
                    { key: 'field_home_rank_persp_badge', label: 'Badge (opcjonalnie)', name: 'badge', type: 'image', return_format: 'array', preview_size: 'medium', wpml_cf_preferences: 3 },
                    {
                        key: 'field_home_rank_persp_stats',
                        label: 'Statystyki',
                        name: 'stats',
                        type: 'repeater',
                        layout: 'block',
                        min: 0,
                        max: 3,
                        button_label: 'Dodaj statystykę',
                        sub_fields: [
                            { key: 'field_home_rank_persp_stat_value', label: 'Wartość', name: 'value', type: 'text', parent_repeater: 'field_home_rank_persp_stats', wpml_cf_preferences: 2 },
                            { key: 'field_home_rank_persp_stat_suffix', label: 'Sufiks (•, zł, %)', name: 'value_suffix', type: 'text', parent_repeater: 'field_home_rank_persp_stats', wpml_cf_preferences: 2 },
                            { key: 'field_home_rank_persp_stat_label_bold', label: 'Etykieta — pogrubienie', name: 'label_bold', type: 'text', parent_repeater: 'field_home_rank_persp_stats', wpml_cf_preferences: 2 },
                            { key: 'field_home_rank_persp_stat_label', label: 'Etykieta — opis', name: 'label', type: 'text', parent_repeater: 'field_home_rank_persp_stats', wpml_cf_preferences: 2 },
                        ],
                    },
                ],
            },
            {
                key: 'field_home_rank_ela',
                label: 'ELA',
                name: 'ela',
                type: 'group',
                layout: 'block',
                sub_fields: [
                    { key: 'field_home_rank_ela_label', label: 'Etykieta', name: 'label', type: 'text', default_value: 'ELA 2024', wpml_cf_preferences: 2 },
                    { key: 'field_home_rank_ela_label_sub', label: 'Etykieta — dopisek', name: 'label_sub', type: 'text', default_value: 'dane ZUS · rok po studiach', wpml_cf_preferences: 2 },
                    { key: 'field_home_rank_ela_logo', label: 'Logo (opcjonalnie)', name: 'logo', type: 'image', return_format: 'array', preview_size: 'medium', wpml_cf_preferences: 3 },
                    {
                        key: 'field_home_rank_ela_stats',
                        label: 'Statystyki',
                        name: 'stats',
                        type: 'repeater',
                        layout: 'block',
                        min: 0,
                        max: 3,
                        button_label: 'Dodaj statystykę',
                        sub_fields: [
                            { key: 'field_home_rank_ela_stat_value', label: 'Wartość', name: 'value', type: 'text', parent_repeater: 'field_home_rank_ela_stats', wpml_cf_preferences: 2 },
                            { key: 'field_home_rank_ela_stat_suffix', label: 'Sufiks (•, zł, %)', name: 'value_suffix', type: 'text', parent_repeater: 'field_home_rank_ela_stats', wpml_cf_preferences: 2 },
                            { key: 'field_home_rank_ela_stat_label_bold', label: 'Etykieta — pogrubienie', name: 'label_bold', type: 'text', parent_repeater: 'field_home_rank_ela_stats', wpml_cf_preferences: 2 },
                            { key: 'field_home_rank_ela_stat_label', label: 'Etykieta — opis', name: 'label', type: 'text', parent_repeater: 'field_home_rank_ela_stats', wpml_cf_preferences: 2 },
                        ],
                    },
                ],
            },
            { key: 'field_home_rank_sources', label: 'Źródła', name: 'sources', type: 'textarea', rows: 4, new_lines: 'br', wpml_cf_preferences: 2 },
            { key: 'field_home_rank_cta_text', label: 'CTA — tekst', name: 'cta_text', type: 'text', default_value: 'Zobacz kierunki', wpml_cf_preferences: 2 },
            { key: 'field_home_rank_cta_url', label: 'CTA — URL', name: 'cta_url', type: 'url', wpml_cf_preferences: 3 },
        ],
    },
];

if (!fs.existsSync(exportPath)) {
    console.error('Export not found:', exportPath);
    process.exit(1);
}

const data = JSON.parse(fs.readFileSync(exportPath, 'utf8'));
const group = data[0];

group.key = 'group_67b861cb708f2';
group.title = 'Front: Page';

const tabIdx = group.fields.findIndex((f) => f.key === 'field_67fcf0fb48f94');
if (tabIdx === -1) {
    console.error('About Us tab not found in export');
    process.exit(1);
}

group.fields.splice(tabIdx, 2, ...homeRankingsFields);

group.location = [
    [{ param: 'page_type', operator: '==', value: 'front_page' }],
    [{ param: 'page', operator: '==', value: '124' }],
];

fs.writeFileSync(outPath, JSON.stringify(data, null, 4) + '\n');
console.log('Wrote', outPath);
