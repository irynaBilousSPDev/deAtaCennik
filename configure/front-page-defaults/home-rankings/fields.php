<?php

require_once dirname(__DIR__, 2) . '/lp-defaults/merge.php';
require_once dirname(__DIR__, 2) . '/lp-defaults/rankingi/fields.php';

/**
 * @return array<string, mixed>
 */
function akademiata_home_rankings_defaults(): array {
    return require __DIR__ . '/content.php';
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
    $defaults = akademiata_home_rankings_defaults();
    $acf_group = is_array($acf_group) ? $acf_group : [];

    $merged = akademiata_lp_merge_defaults($defaults, $acf_group);

    foreach (['perspektywy', 'ela'] as $block_key) {
        if (!empty($merged[$block_key]['stats']) && is_array($merged[$block_key]['stats'])) {
            $default_stats = $defaults[$block_key]['stats'] ?? [];
            foreach ($merged[$block_key]['stats'] as $i => $stat) {
                $merged[$block_key]['stats'][$i] = akademiata_lp_merge_defaults(
                    $default_stats[$i] ?? [],
                    is_array($stat) ? $stat : null
                );
            }
            if ($block_key === 'perspektywy') {
                $merged[$block_key]['stats'] = akademiata_home_rankings_swap_first_third_stats(
                    $merged[$block_key]['stats']
                );
            }
        }
    }

    if (!empty($merged['film']) && is_array($merged['film'])) {
        $merged['film'] = akademiata_lp_merge_defaults($defaults['film'], $merged['film']);
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
