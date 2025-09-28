<?php
/**
 * Plugin Name: Masjid Appeals – 3 Cards (ACF)
 * Description: ACF block that renders three preset appeal cards exactly like the example: image header, white card, 16px radius, soft shadow, equal heights, divider and hadith footer. Editors can override text, links and images.
 * Version: 1.1.0
 * Author: Ibraheem Mustafa
 */

if ( ! function_exists( 'acf_register_block_type' ) ) {
	add_action( 'admin_notices', function(){
		if ( current_user_can( 'activate_plugins' ) ) {
			printf('<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'Masjid Appeals – Exact 3 Cards requires ACF Pro to be installed and active.', 'masjid-appeals-exact' ));
		}
	});
	return;
}

add_action( 'acf/init', function(){

	acf_register_block_type( [
		'name'            => 'masjid-appeals-exact',
		'title'           => __( 'Masjid Appeals – Exact 3 Cards', 'masjid-appeals-exact' ),
		'description'     => __( 'Exact replica of the HTML demo: 3 cards with image header and hadith footer (+ animation, hover).', 'masjid-appeals-exact' ),
		'category'        => 'widgets',
		'icon'            => 'columns',
		'keywords'        => [ 'masjid', 'appeals', 'donate', 'cards' ],
		'mode'            => 'edit',
		'render_callback' => 'mt_exact_render_block',
		'supports'        => [
			'align'   => false,
			'anchor'  => true,
			'spacing' => [ 'margin' ],
			'jsx'     => true,
		],
	] );

	if ( function_exists( 'acf_add_local_field_group' ) ) {
		acf_add_local_field_group( [
			'key'      => 'group_masjid_appeals_exact',
			'title'    => 'Masjid Appeals – Exact 3 Cards',
			'fields'   => [
				[ 'key' => 'field_mte_wrap', 'label' => 'Include Outer Wrapper', 'name' => 'include_wrap', 'type' => 'true_false', 'ui' => 1, 'default_value' => 0 ],
				[ 'key' => 'field_mte_cards', 'label' => 'Cards', 'name' => 'cards', 'type' => 'repeater', 'min' => 3, 'max' => 3, 'layout' => 'row',
					'sub_fields' => [
						[ 'key' => 'field_mte_preset', 'label' => 'Preset (auto-fills content)', 'name' => 'preset', 'type' => 'select',
						  'choices' => [ 'support' => 'Support The Mosque', 'expansion' => 'Masjid Expansion (URGENT)', 'jummah' => 'Jummah Weekly' ], 'ui' => 1 ],
						[ 'key' => 'field_mte_img', 'label' => 'Image', 'name' => 'image', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'medium' ],
						[ 'key' => 'field_mte_title', 'label' => 'Title', 'name' => 'title', 'type' => 'text' ],
						[ 'key' => 'field_mte_body', 'label' => 'Body', 'name' => 'body', 'type' => 'textarea', 'rows' => 3 ],
						[ 'key' => 'field_mte_btn_text', 'label' => 'Button Text', 'name' => 'button_text', 'type' => 'text', 'default_value' => 'CLICK HERE' ],
						[ 'key' => 'field_mte_btn_url', 'label' => 'Button URL', 'name' => 'button_url', 'type' => 'url' ],
						[ 'key' => 'field_mte_div', 'label' => 'Show Divider', 'name' => 'show_divider', 'type' => 'true_false', 'ui' => 1, 'default_value' => 1 ],
						[ 'key' => 'field_mte_htext', 'label' => 'Hadith Text (optional override)', 'name' => 'hadith_text', 'type' => 'textarea', 'rows' => 2 ],
						[ 'key' => 'field_mte_hsrc', 'label' => 'Hadith Source', 'name' => 'hadith_source', 'type' => 'text', 'placeholder' => 'Bukhari & Muslim' ],
					]
				],
			],
			'location' => [ [ [ 'param' => 'block', 'operator' => '==', 'value' => 'acf/masjid-appeals-exact' ] ] ],
		] );
	}
} );

/** CSS identical to the HTML demo, plus hover + progressive enhancement animation */
function mt_exact_css_once(){
	static $printed = false;
	if ( $printed ) return;
	$printed = true;

	echo '<style id="mt-appeals-exact-css">
	:root{ --brand: var(--ast-global-color-0, #23cf7f); --brand-dark:#19b56e; --ink:#1c1d1f; --muted:#5f6368; --card:#ffffff; --line:#e9ecef; }
	.mtwrap{max-width:1180px;margin:auto;padding:28px}
	.mtgrid{display:grid;gap:22px}
	@media(min-width:900px){.mtgrid{grid-template-columns:repeat(3,1fr)}}
	/* Visible by default. JS adds .mt-prepare, then .is-inview for animation */
	.mtcard{background:var(--card);border-radius:16px;overflow:hidden;box-shadow:0 6px 16px rgba(0,0,0,.06);display:flex;flex-direction:column;transition:opacity .5s ease, transform .5s ease, box-shadow .2s ease;opacity:1;transform:none}
	.mtcard.mt-prepare{opacity:0;transform:translateY(12px)}
	.mtcard.is-inview{opacity:1;transform:none}
	.mtcard:hover{transform:translateY(-4px);box-shadow:0 12px 28px rgba(0,0,0,.10)}
	.mtthumb{height:210px;background:linear-gradient(135deg,#f3f3f3,#e6e7e9)}
	.mtpad{padding:20px}
	.mtpad h3{margin:8px 0 10px;font-size:22px}
	.mtpad p{margin:0 0 14px}
	.mtbtn{display:inline-block;text-decoration:none;background:var(--brand);color:#fff;font-weight:600;padding:12px 18px;border-radius:12px;transition:.15s}
	.mtbtn:focus,.mtbtn:hover{background:var(--brand-dark)}
	.mthadith{margin-top:auto;font-style:italic;font-size:14.5px;color:var(--muted);border-top:1px solid var(--line);padding:12px 20px}
	.mthadith .src{font-style:normal;opacity:.75}
	@media (prefers-reduced-motion: reduce){ .mtcard, .mtcard.mt-prepare, .mtcard.is-inview { transition:none !important; opacity:1 !important; transform:none !important; } }
	</style>';
}

/** Defaults that mirror the demo exactly */
function mt_exact_defaults(){
	return [
		[ 'preset' => 'support',
		  'image'  => 'https://images.unsplash.com/photo-1524231757912-21f4fe3a7200?q=80&w=1200&auto=format&fit=crop',
		  'title'  => 'Support The Mosque',
		  'body'   => 'Donate to support your local masjid and get rewarded for every person who prays in there.',
		  'button_text' => 'CLICK HERE', 'button_url' => '#', 'show_divider' => 1,
		  'hadith_text' => '“Whoever builds a mosque for Allah, Allah will build for him a house in Paradise.”',
		  'hadith_source' => 'Bukhari & Muslim' ],
		[ 'preset' => 'expansion',
		  'image'  => 'https://images.unsplash.com/photo-1519681393784-d120267933ba?q=80&w=1200&auto=format&fit=crop',
		  'title'  => 'Masjid Expansion (URGENT)',
		  'body'   => 'Our masjid is running at full capacity and requires modifications to support our community.',
		  'button_text' => 'CLICK HERE', 'button_url' => '#', 'show_divider' => 1,
		  'hadith_text' => '“Whoever builds a mosque for Allah, seeking His pleasure, Allah will build for him a house in Paradise.”',
		  'hadith_source' => 'Bukhari & Muslim' ],
		[ 'preset' => 'jummah',
		  'image'  => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?q=80&w=1200&auto=format&fit=crop',
		  'title'  => 'Jummah Weekly',
		  'body'   => 'Schedule your sadaqah so you never miss out on the reward.',
		  'button_text' => 'CLICK HERE', 'button_url' => '#', 'show_divider' => 1,
		  'hadith_text' => '“The most beloved deeds to Allah are those done regularly, even if small.”',
		  'hadith_source' => 'Bukhari & Muslim' ],
	];
}

/** Render callback */
function mt_exact_render_block( $block, $content = '', $is_preview = false ){
	mt_exact_css_once();

	$include_wrap = (bool) get_field( 'include_wrap' );
	$cards = get_field( 'cards' );

	// Build cards from ACF or defaults
	$data = [];
	$index_presets = [ 'support', 'expansion', 'jummah' ];
	if ( is_array( $cards ) && count( $cards ) === 3 ) {
		foreach ( $cards as $i => $row ) {
			$preset = ! empty( $row['preset'] ) ? $row['preset'] : $index_presets[ $i % 3 ];
			$img = ( ! empty( $row['image']['url'] ) ) ? $row['image']['url'] : '';
			$data[] = [
				'preset' => $preset,
				'image'  => $img,
				'title'  => $row['title'] ?? '',
				'body'   => $row['body'] ?? '',
				'button_text' => !empty($row['button_text']) ? $row['button_text'] : 'CLICK HERE',
				'button_url'  => !empty($row['button_url']) ? $row['button_url'] : '#',
				'show_divider'=> isset($row['show_divider']) ? (bool) $row['show_divider'] : true,
				'hadith_text' => $row['hadith_text'] ?? '',
				'hadith_source' => $row['hadith_source'] ?? '',
			];
		}
	} else {
		$data = mt_exact_defaults();
	}

	// Apply preset defaults where empty
	foreach ( $data as $i => &$c ) {
		if ( $c['preset'] === 'support' ) {
			$c['title']  = $c['title']  ?: 'Support The Mosque';
			$c['body']   = $c['body']   ?: 'Donate to support your local masjid and get rewarded for every person who prays in there.';
			$c['hadith_text']  = $c['hadith_text']  ?: '“Whoever builds a mosque for Allah, Allah will build for him a house in Paradise.”';
			$c['hadith_source']= $c['hadith_source'] ?: 'Bukhari & Muslim';
			$c['image']  = $c['image'] ?: 'https://images.unsplash.com/photo-1524231757912-21f4fe3a7200?q=80&w=1200&auto=format&fit=crop';
		} elseif ( $c['preset'] === 'expansion' ) {
			$c['title']  = $c['title']  ?: 'Masjid Expansion (URGENT)';
			$c['body']   = $c['body']   ?: 'Our masjid is running at full capacity and requires modifications to support our community.';
			$c['hadith_text']  = $c['hadith_text']  ?: '“Whoever builds a mosque for Allah, seeking His pleasure, Allah will build for him a house in Paradise.”';
			$c['hadith_source']= $c['hadith_source'] ?: 'Bukhari & Muslim';
			$c['image']  = $c['image'] ?: 'https://images.unsplash.com/photo-1519681393784-d120267933ba?q=80&w=1200&auto=format&fit=crop';
		} else {
			$c['title']  = $c['title']  ?: 'Jummah Weekly';
			$c['body']   = $c['body']   ?: 'Schedule your sadaqah so you never miss out on the reward.';
			$c['hadith_text']  = $c['hadith_text']  ?: '“The most beloved deeds to Allah are those done regularly, even if small.”';
			$c['hadith_source']= $c['hadith_source'] ?: 'Bukhari & Muslim';
			$c['image']  = $c['image'] ?: 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?q=80&w=1200&auto=format&fit=crop';
		}
	}
	unset($c);

	$wrap_id = 'mt-exact-' . esc_attr( $block['id'] );
	$open_wrap  = $include_wrap ? '<div class="mtwrap" id="' . $wrap_id . '">' : '<div id="' . $wrap_id . '">';
	$close_wrap = '</div>';

	echo $open_wrap;
	echo '<div class="mtgrid">';

	foreach ( $data as $idx => $c ) {
		$thumb_style = 'background:linear-gradient(135deg,#f3f3f3,#e6e7e9)';
		if ( $c['image'] ) {
			$thumb_style = "background:url('" . esc_url( $c['image'] ) . "') center/cover no-repeat";
		}
		$delay = 0.06 * $idx; // stagger

		echo '<article class="mtcard" style="transition-delay:'. esc_attr( $delay ) .'s">';
		echo '<div class="mtthumb" style="' . esc_attr( $thumb_style ) . '" role="img" aria-label="appeal image"></div>';
		echo '<div class="mtpad">';
		echo '<h3>' . esc_html( $c['title'] ) . '</h3>';
		echo '<p>' . wp_kses_post( nl2br( $c['body'] ) ) . '</p>';
		echo '<a href="' . esc_url( $c['button_url'] ) . '" class="mtbtn">'
		     . esc_html( $c['button_text'] ) . '</a>';
		echo '</div>'; // pad
		if ( $c['hadith_text'] ) {
			$src = $c['hadith_source'] ? ' <span class="src">' . esc_html( $c['hadith_source'] ) . '</span>' : '';
			echo '<p class="mthadith">' . wp_kses_post( $c['hadith_text'] ) . $src . '</p>';
		}
		echo '</article>';
	}

	echo '</div>';
	echo $close_wrap;

	// Progressive enhancement: add .mt-prepare first, then observe, else show immediately
	echo '<script>(function(){var root=document.getElementById("'. $wrap_id .'");if(!root) return;var cards=root.querySelectorAll(".mtcard");cards.forEach(function(c){c.classList.add("mt-prepare")});\n'
		. 'function showAll(){cards.forEach(function(c){c.classList.add("is-inview")})}\n'
		. 'try{if("IntersectionObserver" in window){var io=new IntersectionObserver(function(entries){entries.forEach(function(e){if(e.isIntersecting){e.target.classList.add("is-inview");io.unobserve(e.target);}})},{threshold:0.2});cards.forEach(function(c){io.observe(c)});}else{showAll();}}\n'
		. 'catch(e){showAll();}\n'
		. '})();</script>';
}
