<?php

/**
 * @param array<string, mixed> $defaults
 * @param array<string, mixed>|null $acf
 * @return array<string, mixed>
 */
function akademiata_lp_merge_defaults(array $defaults, ?array $acf): array {
    $acf = is_array($acf) ? $acf : [];
    $out = [];

    foreach ($defaults as $key => $default_val) {
        $acf_val = $acf[$key] ?? null;

        if (is_array($default_val) && array_is_list($default_val)) {
            $out[$key] = (is_array($acf_val) && $acf_val !== []) ? $acf_val : $default_val;
            continue;
        }

        if (is_array($default_val)) {
            $out[$key] = akademiata_lp_merge_defaults(
                $default_val,
                is_array($acf_val) ? $acf_val : []
            );
            continue;
        }

        if (is_array($acf_val) && $key === 'city_window_image') {
            $out[$key] = !empty($acf_val['ID']) ? $acf_val : $default_val;
            continue;
        }

        $out[$key] = ($acf_val !== '' && $acf_val !== null && $acf_val !== false) ? $acf_val : $default_val;
    }

    return $out;
}
