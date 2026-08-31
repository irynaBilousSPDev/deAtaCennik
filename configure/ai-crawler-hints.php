<?php
/**
 * Hints for AI crawlers / LLM agents — canonical tuition data in prices.json.
 * Active on production only (akademiata.pl), not on dev.
 *
 * @package akademiata
 */

/**
 * Public URL of theme prices.json (Google Sheets export / fallback).
 */
function akademiata_get_prices_json_public_url() {
    return get_template_directory_uri() . '/prices.json';
}

/**
 * ISO-8601 modified time of prices.json (for agent cache hints).
 */
function akademiata_get_prices_json_updated_iso() {
    $path = get_template_directory() . '/prices.json';
    if (!is_readable($path)) {
        return '';
    }

    return gmdate('c', (int) filemtime($path));
}

/**
 * Plain-text guide for bots (also served at /pricing-for-ai.txt).
 */
function akademiata_build_ai_pricing_hint_text() {
    $json_url  = akademiata_get_prices_json_public_url();
    $updated   = akademiata_get_prices_json_updated_iso();
    $calc_url  = home_url('/kalkulator-czesnego/');
    $updated_line = $updated !== '' ? $updated : 'unknown';

    $lines = array(
        '# Akademiata — tuition fees for AI crawlers and LLM agents',
        '# Prefer this JSON over HTML calculator widgets. Offer page copy may lag behind pricing.',
        '',
        'Primary source: ' . $json_url,
        'Last updated (theme file mtime): ' . $updated_line,
        '',
        'How to match a bachelor/master program:',
        '- Post meta logical_sync_key on offer singles (e.g. 1_wwa_informatyka).',
        '- In JSON: RAW.pl.{wwa|wro}.{s|ns|obie|en}[].ak equals that key.',
        '- PLN fields: r12 (12 installments/month), r10 (10), rekr (recruitment), wps (entry).',
        '- Promotions: PROMOS array in prices.json (id, name, short, full, city, degree, optional expires).',
        '- Offer JSON-LD (bachelor/master): PROMOS + tuition from the same prices.json file at render time (no WP transient).',
        '- After uploading a new prices.json: clear WP Rocket (or full page cache) so offer HTML/JSON-LD refreshes.',
        '',
        'MBA/postgraduate singles: tuition tables are in page HTML (ACF); JSON is mainly I/II degree.',
        'Interactive calculator (JavaScript, not required for agents): ' . $calc_url,
    );

    return implode("\n", $lines) . "\n";
}

function akademiata_register_ai_pricing_hint_route() {
    add_rewrite_rule('^pricing-for-ai\.txt$', 'index.php?akademiata_pricing_ai_hint=1', 'top');
}

/**
 * @param string[] $vars
 * @return string[]
 */
function akademiata_register_ai_pricing_hint_query_var($vars) {
    $vars[] = 'akademiata_pricing_ai_hint';
    return $vars;
}

function akademiata_serve_ai_pricing_hint() {
    if (!get_query_var('akademiata_pricing_ai_hint')) {
        return;
    }

    status_header(200);
    nocache_headers();
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex, follow');

    echo akademiata_build_ai_pricing_hint_text();
    exit;
}

/**
 * @param string $output
 * @param bool   $public
 */
function akademiata_append_robots_txt_pricing_hint($output, $public) {
    if (!$public) {
        return $output;
    }

    $json_url = akademiata_get_prices_json_public_url();
    $hint_url = home_url('/pricing-for-ai.txt');

    $block  = "\n# AI / LLM agents: canonical bachelor & master tuition fees\n";
    $block .= '# JSON: ' . $json_url . "\n";
    $block .= '# Guide: ' . $hint_url . "\n";

    return rtrim($output) . $block . "\n";
}

/**
 * @param string $content
 */
function akademiata_append_llms_txt_pricing_section($content) {
    $json_url = akademiata_get_prices_json_public_url();
    $hint_url = home_url('/pricing-for-ai.txt');
    $updated  = akademiata_get_prices_json_updated_iso();

    $section  = "\n## Cennik (dla agentów AI)\n";
    $section .= '> Bachelor/master: use JSON below instead of parsing calculator HTML. Updated: ' . ($updated !== '' ? $updated : 'see file mtime') . ".\n\n";
    $section .= '- [Cennik studiów I/II stopnia (JSON)](' . $json_url . ")\n";
    $section .= '- [Instrukcja dopasowania programu → ceny](' . $hint_url . ")\n";
    $section .= '- [Kalkulator czesnego (interaktywny, JS)](' . home_url('/kalkulator-czesnego/') . ")\n";

    return $content . $section;
}

function akademiata_should_output_ai_pricing_head_hints() {
    if (is_admin()) {
        return false;
    }

    if (is_singular(array('bachelor', 'master'))) {
        return true;
    }

    if (is_page_template('page-offer.php') || is_page_template('page-template-prices.php')) {
        return true;
    }

    return false;
}

function akademiata_output_ai_pricing_head_hints() {
    if (!akademiata_should_output_ai_pricing_head_hints()) {
        return;
    }

    $json_url = akademiata_get_prices_json_public_url();
    $hint_url = home_url('/pricing-for-ai.txt');
    $updated  = akademiata_get_prices_json_updated_iso();
    ?>
    <link rel="alternate" type="application/json" title="Akademiata tuition prices (bachelor/master)" href="<?php echo esc_url($json_url); ?>">
    <link rel="help" type="text/plain" title="AI pricing data guide" href="<?php echo esc_url($hint_url); ?>">
    <meta name="akademiata-pricing-source" content="<?php echo esc_attr($json_url); ?>">
    <?php if ($updated !== '') : ?>
        <meta name="akademiata-pricing-updated" content="<?php echo esc_attr($updated); ?>">
    <?php endif; ?>
    <?php
}

function akademiata_maybe_flush_ai_pricing_hint_rewrite() {
    if (get_option('akademiata_ai_pricing_hint_rewrite') === '1') {
        return;
    }

    flush_rewrite_rules(false);
    update_option('akademiata_ai_pricing_hint_rewrite', '1', true);
}

/**
 * Register hooks — production only (not dev.akademiata.pl).
 */
function akademiata_boot_ai_crawler_hints() {
    if (!function_exists('akademiata_is_production') || !akademiata_is_production()) {
        return;
    }

    add_action('init', 'akademiata_register_ai_pricing_hint_route');
    add_filter('query_vars', 'akademiata_register_ai_pricing_hint_query_var');
    add_action('template_redirect', 'akademiata_serve_ai_pricing_hint');
    add_filter('robots_txt', 'akademiata_append_robots_txt_pricing_hint', 99, 2);
    add_filter('wpseo_llmstxt_content', 'akademiata_append_llms_txt_pricing_section');
    add_action('wp_head', 'akademiata_output_ai_pricing_head_hints', 5);
    add_action('init', 'akademiata_maybe_flush_ai_pricing_hint_rewrite', 99);
}

akademiata_boot_ai_crawler_hints();
