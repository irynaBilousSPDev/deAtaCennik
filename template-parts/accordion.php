<?php
$accordion = get_query_var('subjects_study_accordion', []);
if (!empty($accordion)): ?>
    <div class="accordion_container">
        <?php foreach ($accordion as $accordion_item) :
            $title = $accordion_item['title'] ?? '';
            $content = $accordion_item['content'] ?? '';
            ?>
            <div class="accordion-item">
                <div class="accordion-header">
                    <h3 class="small_title"><?php echo $title; ?></h3>
                    <span class="accordion-arrow"></span>
                </div>
                <div class="accordion-content">
                    <?php echo wp_kses_post($content); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>