<?php
/**
 * Plugin Name: Masjid Card Block (ACF)
 * Description: Editor-friendly "card with hadith footer" ACF block. Drop-in, no theme CSS required.
 * Version: 1.1.0
 * Author: Ibraheem Mustafa
 */

if ( ! function_exists( 'acf_register_block_type' ) ) {
	add_action( 'admin_notices', function(){
		if ( current_user_can( 'activate_plugins' ) ) {
			printf('<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'Masjid Card Block requires ACF Pro to be installed and active.', 'masjid-card' ));
		}
	});
	return;
}

add_action( 'acf/init', function(){

	acf_register_block_type( [
		'name'            => 'masjid-card',
		'title'           => __( 'Masjid Card', 'masjid-card' ),
		'description'     => __( 'Card with image, text, button, and a hadith footer.', 'masjid-card' ),
		'category'        => 'widgets',
		'icon'            => 'index-card',
		'keywords'        => [ 'masjid', 'card', 'hadith', 'donate' ],
		'mode'            => 'edit',
		'render_callback' => 'mt_render_masjid_card_block',
		'supports'        => [
			'align'   => false,
			'anchor'  => true,
			'spacing' => [ 'margin', 'padding' ],
			'jsx'     => true,
		],
	] );

	if ( function_exists( 'acf_add_local_field_group' ) ) {
		acf_add_local_field_group( [
			'key'      => 'group_masjid_card',
			'title'    => 'Masjid Card Fields',
			'fields'   => [
				[
					'key' => 'field_mt_preset',
					'label' => 'Content Preset',
					'name' => 'preset',
					'type' => 'select',
					'choices' => [
						'none'      => 'None',
						'support'   => 'Support The Mosque',
						'expansion' => 'Masjid Expansion (URGENT)',
						'jummah'    => 'Jummah Weekly',
					],
					'default_value' => 'none',
					'ui' => 1,
				],
				[
					'key' => 'field_mt_image',
					'label' => 'Image (optional)',
					'name' => 'image',
					'type' => 'image',
					'return_format' => 'array',
					'preview_size'  => 'large',
				],
				[
					'key' => 'field_mt_title',
					'label' => 'Title',
					'name' => 'title',
					'type' => 'text',
				],
				[
					'key' => 'field_mt_body',
					'label' => 'Body',
					'name' => 'body',
					'type' => 'textarea',
					'rows' => 3,
				],
				[
					'key' => 'field_mt_btn_text',
					'label' => 'Button Text',
					'name' => 'button_text',
					'type' => 'text',
					'default_value' => 'CLICK HERE',
				],
				[
					'key' => 'field_mt_btn_url',
					'label' => 'Button URL',
					'name' => 'button_url',
					'type' => 'url',
				],
				[
					'key' => 'field_mt_divider',
					'label' => 'Show Divider',
					'name'  => 'show_divider',
					'type'  => 'true_false',
					'ui'    => 1,
					'default_value' => 1,
				],
				[
					'key' => 'field_mt_hadith_text',
					'label' => 'Hadith Text',
					'name' => 'hadith_text',
					'type' => 'textarea',
					'rows' => 2,
				],
				[
					'key' => 'field_mt_hadith_source',
					'label' => 'Hadith Source',
					'name' => 'hadith_source',
					'type' => 'text',
					'placeholder' => 'Bukhari & Muslim',
				],
				[
					'key' => 'field_mt_style_heading',
					'label' => 'Style',
					'name'  => 'style_heading',
					'type'  => 'tab',
					'placement' => 'top',
				],
				[
					'key' => 'field_mt_radius',
					'label' => 'Corner Radius (px)',
					'name' => 'radius',
					'type' => 'number',
					'min' => 0,
					'max' => 40,
					'default_value' => 16,
				],
				[
					'key' => 'field_mt_shadow',
					'label' => 'Card Shadow',
					'name' => 'shadow',
					'type' => 'true_false',
					'ui' => 1,
					'default_value' => 1,
				],
				[
					'key' => 'field_mt_pad',
					'label' => 'Padding (px)',
					'name' => 'pad',
					'type' => 'number',
					'min' => 8,
					'max' => 48,
					'default_value' => 20,
				],
			],
			'location' => [ [ [ 'param' => 'block', 'operator' => '==', 'value' => 'acf/masjid-card' ] ] ],
			'hide_on_screen' => [ 'the_content', 'excerpt', 'discussion', 'comments', 'format', 'page_attributes', 'featured_image' ],
		] );
	}
} );

function mt_render_masjid_card_block( $block, $content = '', $is_preview = false ) {
	$img         = get_field( 'image' );
	$title       = get_field( 'title' );
	$body        = get_field( 'body' );
	$btn_text    = get_field( 'button_text' ) ?: 'CLICK HERE';
	$btn_url     = get_field( 'button_url' ) ?: '#';
	$preset      = get_field( 'preset' ) ?: 'none';
	$divider     = (bool) get_field( 'show_divider' );
	$hadith_text = get_field( 'hadith_text' );
	$hadith_src  = get_field( 'hadith_source' );
	$radius      = intval( get_field( 'radius' ) );
	$shadow      = (bool) get_field( 'shadow' );
	$pad         = intval( get_field( 'pad' ) );

	// Theme palette fallbacks (Astra variables if present).
	$brand   = 'var(--ast-global-color-0, #23cf7f)';
	$ink     = 'var(--ast-global-color-3, #1c1d1f)';
	$muted   = 'var(--ast-global-color-5, #5f6368)';
	$line    = 'var(--ast-global-color-7, #e9ecef)';

	// Provide defaults for presets
	switch ( $preset ) {
		case 'support':
			$title = $title ?: __( 'Support The Mosque', 'masjid-card' );
			$body  = $body  ?: __( 'Donate to support your local masjid and get rewarded for every person who prays in there.', 'masjid-card' );
			$hadith_text = $hadith_text ?: '“Whoever builds a mosque for Allah, Allah will build for him a house in Paradise.”';
			$hadith_src  = $hadith_src  ?: 'Bukhari & Muslim';
			break;
		case 'expansion':
			$title = $title ?: __( 'Masjid Expansion (URGENT)', 'masjid-card' );
			$body  = $body  ?: __( 'Our masjid is running at full capacity and requires modifications to support our community.', 'masjid-card' );
			$hadith_text = $hadith_text ?: '“Whoever builds a mosque for Allah, seeking His pleasure, Allah will build for him a house in Paradise.”';
			$hadith_src  = $hadith_src  ?: 'Bukhari & Muslim';
			break;
		case 'jummah':
			$title = $title ?: __( 'Jummah Weekly', 'masjid-card' );
			$body  = $body  ?: __( 'Schedule your sadaqah so you never miss out on the reward.', 'masjid-card' );
			$hadith_text = $hadith_text ?: '“The most beloved deeds to Allah are those done regularly, even if small.”';
			$hadith_src  = $hadith_src  ?: 'Bukhari & Muslim';
			break;
	}

	// Inline styles printed once.
	static $printed_style = false;
	if ( ! $printed_style ) {
		$printed_style = true;
		echo '<style id="mt-masjid-card-css">' .
			'.mt-card{box-sizing:border-box;background:#fff;border:1px solid rgba(0,0,0,.06);border-radius:16px;overflow:hidden;padding:20px;width:100%;display:flex;flex-direction:column;gap:12px;color:' . $ink . ';}' .
			'.mt-card.mt-shadow{box-shadow:0 10px 24px rgba(0,0,0,.08)}' .
			'.mt-thumb{width:100%;aspect-ratio:16/9;background:#f3f4f6;border-radius:12px;overflow:hidden}' .
			'.mt-thumb img{width:100%;height:100%;object-fit:cover;display:block}' .
			'.mt-title{margin:0;font-size:clamp(20px,2.2vw,28px);line-height:1.25;font-weight:700}' .
			'.mt-body{margin:0 0 4px;line-height:1.55}' .
			'.mt-btn{display:inline-block;text-decoration:none;background:' . $brand . ';color:#fff;font-weight:600;padding:12px 18px;border-radius:12px}' .
			'.mt-btn:focus,.mt-btn:hover{filter:brightness(.95)}' .
			'.mt-hadith{margin:8px 0 0;font-style:italic;font-size:clamp(13px,1.55vw,14.5px);color:' . $muted . ';}' .
			'.mt-hadith .src{font-style:normal;opacity:.8}' .
			'.mt-divider{border:0;border-top:1px solid ' . $line . ';margin:clamp(10px,1.2vw,14px) 0 0;height:0}' .
			'</style>';
	}

	$classes = 'mt-card' . ( $shadow ? ' mt-shadow' : '' );
	$style   = 'border-radius:' . max(0,$radius) . 'px;padding:' . max(8,$pad) . 'px;';

	echo '<div class="' . esc_attr( $classes ) . '" style="' . esc_attr( $style ) . '">';

	// Image
	if ( ! empty( $img ) && ! empty( $img['url'] ) ) {
		$alt = isset( $img['alt'] ) ? $img['alt'] : '';
		echo '<div class="mt-thumb">'
			. '<img src="' . esc_url( $img['url'] ) . '" alt="' . esc_attr( $alt ) . '" />'
			. '</div>';
	}

	// Title
	if ( $title ) {
		echo '<h3 class="mt-title">' . esc_html( $title ) . '</h3>';
	}

	// Body
	if ( $body ) {
		echo '<p class="mt-body">' . wp_kses_post( nl2br( $body ) ) . '</p>';
	}

	// Button
	if ( $btn_text && $btn_url ) {
		echo '<p style="margin:0"><a class="mt-btn" href="' . esc_url( $btn_url ) . '">' . esc_html( $btn_text ) . '</a></p>';
	}

	// Hadith footer
	if ( $hadith_text ) {
		if ( $divider ) {
			echo '<hr class="mt-divider" />';
		}
		echo '<p class="mt-hadith">' . wp_kses_post( $hadith_text );
		if ( $hadith_src ) {
			echo ' <span class="src">' . esc_html( $hadith_src ) . '</span>';
		}
		echo '</p>';
	}

	echo '</div>';
}