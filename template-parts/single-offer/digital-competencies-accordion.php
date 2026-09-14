<?php
$title = __('Kompetencje cyfrowe wymagane od kandydatów', 'akademiata');
$arrow_src = get_template_directory_uri() . '/static/img/arrow_down_closed_accordion.png';
$skills = array(
	__('obsługa przeglądarek internetowych,', 'akademiata'),
	__('obsługa poczty elektronicznej, edytorów tekstu, np. Microsoft Word oraz arkuszy kalkulacyjnych, np. Microsoft Excel,', 'akademiata'),
	__('korzystanie z urządzeń peryferyjnych umożliwiających m.in. wydruk dokumentów,', 'akademiata'),
	__('podstawowa obsługa oprogramowania graficznego umożliwiającego przygotowanie zdjęcia cyfrowego,', 'akademiata'),
	__('korzystanie z narzędzi do wideokonferencji, np. Zoom,', 'akademiata'),
	__('korzystanie z platform i narzędzi wykorzystywanych w kształceniu na odległość i pracy zespołowej, np. Microsoft Teams, Moodle.', 'akademiata'),
);
?>
<section class="section_digital_competencies">
<div class="container">
	<div class="accordion_universal digital-competencies-accordion">
		<div class="accordion_item">
			<div class="accordion_header">
				<span class="accordion_title_wrap">
					<span class="accordion_title small_title"><?php echo esc_html($title); ?></span>
				</span>
				<span class="accordion_arrow">
					<img src="<?php echo esc_url($arrow_src); ?>" alt="<?php echo esc_attr__('Arrow', 'akademiata'); ?>">
				</span>
			</div>
			<div class="accordion_content">
				<div class="default_content">
					<p><?php echo esc_html__('Kandydaci ubiegający się o przyjęcie na studia w ATA powinni posiadać kompetencje cyfrowe umożliwiające przejście procesu rekrutacyjnego w uczelnianym internetowym systemie rekrutacyjnym, a następnie rozpoczęcie kształcenia na wybranym kierunku studiów.', 'akademiata'); ?></p>
					<p><?php echo esc_html__('Wymagana jest podstawowa umiejętność korzystania z komputera, w szczególności:', 'akademiata'); ?></p>
					<ul>
						<?php foreach ($skills as $skill) : ?>
							<li><?php echo esc_html($skill); ?></li>
						<?php endforeach; ?>
					</ul>
					<p><?php echo esc_html__('Do udziału w kształceniu prowadzonym z wykorzystaniem metod i technik kształcenia na odległość niezbędny jest komputer wyposażony w kamerę i mikrofon oraz dostęp do Internetu.', 'akademiata'); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>
</section>
