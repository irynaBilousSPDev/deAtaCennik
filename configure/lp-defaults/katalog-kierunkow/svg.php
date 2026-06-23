<?php

/**
 * @param string $key
 */
function akademiata_katalog_kierunkow_course_svg($key): void {
    switch ($key) {
        case 'arch_2':
            ?>
            <svg viewBox="0 0 420 138"><path d="M70 106l54-62 55 62z" fill="#fff"></path><rect x="210" y="32" width="102" height="76" rx="8" fill="#fff"></rect><path d="M38 116h344" stroke="#ff5a28" stroke-width="6" stroke-linecap="round"></path></svg>
            <?php
            break;
        case 'interior_1':
            ?>
            <svg viewBox="0 0 420 138"><rect x="62" y="40" width="120" height="64" rx="10" fill="#fff"></rect><rect x="214" y="30" width="112" height="76" rx="10" fill="#fff"></rect><path d="M75 104h250" stroke="#20253d" stroke-width="4"></path><circle cx="324" cy="70" r="14" fill="#74b983" opacity=".7"></circle></svg>
            <?php
            break;
        case 'interior_2':
            ?>
            <svg viewBox="0 0 420 138"><rect x="70" y="34" width="94" height="76" rx="10" fill="#fff"></rect><rect x="184" y="50" width="86" height="60" rx="10" fill="#fff"></rect><rect x="288" y="28" width="66" height="82" rx="10" fill="#fff"></rect><path d="M52 116h318" stroke="#ff5a28" stroke-width="6" stroke-linecap="round"></path></svg>
            <?php
            break;
        case 'landscape_1':
            ?>
            <svg viewBox="0 0 420 138"><circle cx="110" cy="66" r="38" fill="#74b983" opacity=".48"></circle><circle cx="172" cy="58" r="30" fill="#74b983" opacity=".36"></circle><rect x="236" y="42" width="92" height="54" rx="10" fill="#fff"></rect><path d="M60 108c80-42 160-42 280 0" stroke="#20253d" stroke-width="5" fill="none"></path></svg>
            <?php
            break;
        case 'landscape_2':
            ?>
            <svg viewBox="0 0 420 138"><path d="M48 102c62-52 126-54 190 0" stroke="#74b983" stroke-width="13" fill="none"></path><path d="M190 102c54-48 110-48 180 0" stroke="#20253d" stroke-width="5" fill="none"></path><circle cx="320" cy="46" r="20" fill="#fff"></circle></svg>
            <?php
            break;
        case 'landscape_3':
            ?>
            <svg viewBox="0 0 420 138"><rect x="70" y="40" width="82" height="58" rx="10" fill="#fff"></rect><circle cx="210" cy="68" r="36" fill="#74b983" opacity=".52"></circle><rect x="270" y="32" width="76" height="72" rx="10" fill="#fff"></rect><path d="M50 112h320" stroke="#ff5a28" stroke-width="6" stroke-linecap="round"></path></svg>
            <?php
            break;
        case 'build_1':
            ?>
            <svg viewBox="0 0 420 138"><rect x="70" y="30" width="74" height="82" rx="8" fill="#fff"></rect><rect x="166" y="44" width="74" height="68" rx="8" fill="#fff"></rect><rect x="262" y="22" width="78" height="90" rx="8" fill="#fff"></rect><path d="M50 116h320" stroke="#20253d" stroke-width="6" stroke-linecap="round"></path></svg>
            <?php
            break;
        case 'build_2':
            ?>
            <svg viewBox="0 0 420 138"><path d="M78 108l70-70 68 70z" fill="#fff"></path><rect x="242" y="38" width="88" height="70" rx="10" fill="#fff"></rect><path d="M48 116h324" stroke="#ff5a28" stroke-width="6" stroke-linecap="round"></path></svg>
            <?php
            break;
        case 'build_3':
            ?>
            <svg viewBox="0 0 420 138"><rect x="68" y="42" width="106" height="58" rx="10" fill="#fff"></rect><path d="M220 96l38-54 40 54z" fill="#fff"></path><circle cx="332" cy="52" r="20" fill="#74b983" opacity=".55"></circle><path d="M52 116h318" stroke="#ff5a28" stroke-width="6" stroke-linecap="round"></path></svg>
            <?php
            break;
        case 'env_1':
            ?>
            <svg viewBox="0 0 420 138"><circle cx="118" cy="62" r="38" fill="#74b983" opacity=".55"></circle><path d="M118 102c44-58 90-74 152-42" stroke="#20253d" stroke-width="6" fill="none"></path><rect x="276" y="46" width="68" height="56" rx="10" fill="#fff"></rect></svg>
            <?php
            break;
        case 'env_2':
            ?>
            <svg viewBox="0 0 420 138"><path d="M64 104c58-62 118-62 180 0" stroke="#74b983" stroke-width="14" fill="none"></path><circle cx="292" cy="54" r="28" fill="#fff"></circle><path d="M48 116h324" stroke="#ff5a28" stroke-width="6" stroke-linecap="round"></path></svg>
            <?php
            break;
        case 'arch_1':
        default:
            ?>
            <svg viewBox="0 0 420 138"><rect x="55" y="34" width="82" height="72" rx="8" fill="#fff"></rect><rect x="160" y="20" width="96" height="90" rx="8" fill="#fff"></rect><rect x="280" y="46" width="76" height="58" rx="8" fill="#fff"></rect><path d="M34 116h350" stroke="#ff5a28" stroke-width="6" stroke-linecap="round"></path></svg>
            <?php
            break;
    }
}
