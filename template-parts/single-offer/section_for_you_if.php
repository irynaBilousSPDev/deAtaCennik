<?php
$acf_fields = get_query_var('acf_fields', []);
$program_for_you = !empty($acf_fields['program_for_you']) ? $acf_fields['program_for_you'] : [];
// Check if section data exists before rendering
if (!empty($program_for_you)) :
    $title = $program_for_you['title'];
    $title = $title ? $title : __('Ten program jest dla Ciebie, jeśli:', 'akademiata');
    $cards = $program_for_you['cards'] ?? [];
    ?>
    <section class="section_for_you_if mb-5">
        <div class="container">
            <h2 class="title_section mb-5">
                <?php echo esc_html($title); ?>
            </h2>
            <?php
            //  Pass `image_content_slider` only if it exists
            if (!empty($cards)) {
                set_query_var('cards', $cards);
                get_template_part('./template-parts/cards');
            }
            ?>
        </div>
    </section>
<?php endif; ?>