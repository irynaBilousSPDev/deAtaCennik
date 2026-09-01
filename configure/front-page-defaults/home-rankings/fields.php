<?php

require_once dirname(__DIR__, 2) . '/lp-defaults/merge.php';
require_once dirname(__DIR__, 2) . '/lp-defaults/rankingi/fields.php';

/**
 * @return array<string, mixed>
 */
function akademiata_home_rankings_defaults_pl(): array {
    return require __DIR__ . '/content.php';
}

/**
 * @return array<string, mixed>
 */
function akademiata_home_rankings_defaults_en(): array {
    return require __DIR__ . '/content-en.php';
}

/**
 * @return array<string, mixed>
 */
function akademiata_home_rankings_defaults_uk(): array {
    return require __DIR__ . '/content-uk.php';
}

/**
 * @return array<string, mixed>
 */
function akademiata_home_rankings_defaults_ru(): array {
    return require __DIR__ . '/content-ru.php';
}

/**
 * @return list<string>
 */
function akademiata_home_rankings_localized_langs(): array {
    return [ 'en', 'uk', 'ru' ];
}

/**
 * @return array<string, mixed>
 */
function akademiata_home_rankings_defaults(): array {
    $lang = function_exists('akademiata_normalize_theme_lang_code')
        ? akademiata_normalize_theme_lang_code(apply_filters('wpml_current_language', 'pl'))
        : 'pl';

    $map = [
        'en' => 'akademiata_home_rankings_defaults_en',
        'uk' => 'akademiata_home_rankings_defaults_uk',
        'ru' => 'akademiata_home_rankings_defaults_ru',
    ];

    if (isset($map[ $lang ]) && is_callable($map[ $lang ])) {
        return $map[ $lang ]();
    }

    return akademiata_home_rankings_defaults_pl();
}

/**
 * Replace values still equal to Polish defaults with the target-language defaults
 * (covers WPML “copy from original” on translated front pages).
 *
 * @param array<string, mixed> $merged
 * @param array<string, mixed> $pl
 * @param array<string, mixed> $localized
 * @return array<string, mixed>
 */
function akademiata_home_rankings_replace_pl_copies(array $merged, array $pl, array $localized): array {
    foreach ($localized as $key => $localized_val) {
        if (!array_key_exists($key, $merged)) {
            continue;
        }

        $pl_val  = $pl[ $key ] ?? null;
        $current = $merged[ $key ];

        if (is_array($localized_val) && function_exists('array_is_list') && array_is_list($localized_val)) {
            if (!is_array($current)) {
                continue;
            }
            foreach ($localized_val as $i => $item_en) {
                if (!isset($current[ $i ]) || !is_array($item_en)) {
                    continue;
                }
                $current[ $i ] = akademiata_home_rankings_replace_pl_copies(
                    $current[ $i ],
                    is_array($pl_val[ $i ] ?? null) ? $pl_val[ $i ] : array(),
                    $item_en
                );
            }
            $merged[ $key ] = $current;
            continue;
        }

        if (is_array($localized_val)) {
            $merged[ $key ] = akademiata_home_rankings_replace_pl_copies(
                is_array($current) ? $current : array(),
                is_array($pl_val) ? $pl_val : array(),
                $localized_val
            );
            continue;
        }

        if ($current === $pl_val || $current === '' || $current === null) {
            $merged[ $key ] = $localized_val;
        }
    }

    return $merged;
}

/**
 * @param array<int, mixed> $stats
 * @return array<int, mixed>
 */
function akademiata_home_rankings_swap_first_third_stats(array $stats): array {
    $stats = array_values($stats);
    if (count($stats) < 3) {
        return $stats;
    }

    [$stats[0], $stats[2]] = [$stats[2], $stats[0]];

    return $stats;
}

/**
 * @param array<string, mixed>|false|null $acf_group
 * @return array<string, mixed>
 */
function akademiata_home_rankings_fields($acf_group): array {
    $defaults_pl = akademiata_home_rankings_defaults_pl();
    $defaults    = akademiata_home_rankings_defaults();
    $acf_group   = is_array($acf_group) ? $acf_group : [];

    $merged = akademiata_lp_merge_defaults($defaults, $acf_group);

    $lang = function_exists('akademiata_normalize_theme_lang_code')
        ? akademiata_normalize_theme_lang_code(apply_filters('wpml_current_language', 'pl'))
        : 'pl';

    $is_localized = in_array($lang, akademiata_home_rankings_localized_langs(), true);

    if ($is_localized && $defaults !== $defaults_pl) {
        $merged = akademiata_home_rankings_replace_pl_copies($merged, $defaults_pl, $defaults);
    }

    foreach (['perspektywy', 'ela'] as $block_key) {
        if (!empty($merged[ $block_key ]['stats']) && is_array($merged[ $block_key ]['stats'])) {
            $default_stats = $defaults[ $block_key ]['stats'] ?? [];
            $pl_stats      = $defaults_pl[ $block_key ]['stats'] ?? [];
            foreach ($merged[ $block_key ]['stats'] as $i => $stat) {
                $merged[ $block_key ]['stats'][ $i ] = akademiata_lp_merge_defaults(
                    $default_stats[ $i ] ?? [],
                    is_array($stat) ? $stat : null
                );
                if ($is_localized) {
                    $merged[ $block_key ]['stats'][ $i ] = akademiata_home_rankings_replace_pl_copies(
                        $merged[ $block_key ]['stats'][ $i ],
                        is_array($pl_stats[ $i ] ?? null) ? $pl_stats[ $i ] : [],
                        is_array($default_stats[ $i ] ?? null) ? $default_stats[ $i ] : []
                    );
                }
            }
            if ($block_key === 'perspektywy') {
                $merged[ $block_key ]['stats'] = akademiata_home_rankings_swap_first_third_stats(
                    $merged[ $block_key ]['stats']
                );
            }
        }
    }

    if (!empty($merged['film']) && is_array($merged['film'])) {
        $merged['film'] = akademiata_lp_merge_defaults($defaults['film'], $merged['film']);
        if ($is_localized) {
            $merged['film'] = akademiata_home_rankings_replace_pl_copies(
                $merged['film'],
                $defaults_pl['film'] ?? [],
                $defaults['film'] ?? []
            );
        }
    }

    return $merged;
}

/**
 * @return array<string, array<string, bool>>
 */
function akademiata_home_rankings_value_allowed_tags(): array {
    return [
        'sup' => [],
    ];
}

/**
 * @param string|null $value
 */
function akademiata_home_rankings_value_html($value): string {
    if ($value === '' || $value === null) {
        return '';
    }

    return wp_kses((string) $value, akademiata_home_rankings_value_allowed_tags());
}
