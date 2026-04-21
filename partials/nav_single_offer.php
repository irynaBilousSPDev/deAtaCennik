 <ul id="menu-offer">
    <li>
        <a href="#why_study"><?php _e('DLACZEGO WARTO', 'akademiata'); ?></a>
    </li>
    <li>
        <a href="#program"><?php _e('PROGRAM', 'akademiata'); ?></a>
    </li>
    <li>
        <a href="#tuition_fees"><?php _e('OPŁATY', 'akademiata'); ?></a>
    </li>
     <?php
     $current_lang = apply_filters('wpml_current_language', null);
     ?>

     <?php if (
             !(
                     is_singular(array('bachelor', 'master'))
                     && $current_lang === 'en'
             )
     ) : ?>
         <li>
             <a href="#discounts"><?php _e('ZNIŻKI', 'akademiata'); ?></a>
         </li>
     <?php endif; ?>
    <li>
        <a href="#recruitment_rules"><?php _e('ZASADY REKRUTACJI', 'akademiata'); ?></a>
    </li>
</ul>

