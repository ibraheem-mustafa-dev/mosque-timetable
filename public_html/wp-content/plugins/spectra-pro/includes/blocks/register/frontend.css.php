<?php
/**
 * Frontend CSS & Google Fonts loading File.
 *
 * @since 1.0.0
 *
 * @package spectra-pro
 */

/**
 * Adding this comment to avoid PHPStan errors of undefined variable as these variables are defined else where.
 *
 * @var mixed[] $attr
 * @var int $id
 */

// Add fonts.
SpectraPro\Core\Utils::blocks_register_gfont( $attr );

/**
 * Generating Border.
 */

// form border.
$form_border        = UAGB_Block_Helper::uag_generate_border_css( $attr, 'form' );
$form_border_Tablet = UAGB_Block_Helper::uag_generate_border_css( $attr, 'form', 'tablet' );
$form_border_Mobile = UAGB_Block_Helper::uag_generate_border_css( $attr, 'form', 'mobile' );

// input border.
$input_border        = UAGB_Block_Helper::uag_generate_border_css( $attr, 'field' );
$input_border_Tablet = UAGB_Block_Helper::uag_generate_border_css( $attr, 'field', 'tablet' );
$input_border_Mobile = UAGB_Block_Helper::uag_generate_border_css( $attr, 'field', 'mobile' );

// register border.
$register_btn_border        = UAGB_Block_Helper::uag_generate_border_css( $attr, 'btn' );
$register_btn_border_Tablet = UAGB_Block_Helper::uag_generate_border_css( $attr, 'btn', 'tablet' );
$register_btn_border_Mobile = UAGB_Block_Helper::uag_generate_border_css( $attr, 'btn', 'mobile' );



/**
 * Alignment Control.
 */

// register btn.
$align_register_btn_margin        = 'right' === $attr['alignRegisterBtn'] ? '0 0 0 auto' : ( 'center' === $attr['alignRegisterBtn'] ? '0 auto' : ( 'full' === $attr['alignRegisterBtn'] ? '' : 0 ) );
$align_register_btn_margin_tablet = 'right' === $attr['alignRegisterBtnTablet'] ? '0 0 0 auto' : ( 'center' === $attr['alignRegisterBtnTablet'] ? '0 auto' : ( 'full' === $attr['alignRegisterBtnTablet'] ? '' : 0 ) );
$align_register_btn_margin_mobile = 'right' === $attr['alignRegisterBtnMobile'] ? '0 0 0 auto' : ( 'center' === $attr['alignRegisterBtnMobile'] ? '0 auto' : ( 'full' === $attr['alignRegisterBtnMobile'] ? '' : 0 ) );


/**
 * Stack Control.
 */



$box_shadow_position_css = $attr['boxShadowPosition'];

if ( 'outset' === $attr['boxShadowPosition'] ) {
	$box_shadow_position_css = '';
}

$box_shadow_position_css_hover = $attr['boxShadowPositionHover'];

if ( 'outset' === $attr['boxShadowPositionHover'] ) {
	$box_shadow_position_css_hover = '';
}

$common_gradient_obj = array(
	'gradientValue'     => $attr['gradientValue'],
	'gradientColor1'    => $attr['gradientColor1'],
	'gradientColor2'    => $attr['gradientColor2'],
	'gradientType'      => $attr['gradientType'],
	'gradientLocation1' => $attr['gradientLocation1'],
	'gradientLocation2' => $attr['gradientLocation2'],
	'gradientAngle'     => $attr['gradientAngle'],
	'selectGradient'    => $attr['selectGradient'],
);

$bg_obj_desktop      = array_merge(
	array(
		'backgroundType'           => $attr['backgroundType'],
		'backgroundImage'          => $attr['backgroundImageDesktop'],
		'backgroundColor'          => $attr['backgroundColor'],
		'backgroundRepeat'         => $attr['backgroundRepeatDesktop'],
		'backgroundPosition'       => $attr['backgroundPositionDesktop'],
		'backgroundSize'           => $attr['backgroundSizeDesktop'],
		'backgroundAttachment'     => $attr['backgroundAttachmentDesktop'],
		'backgroundImageColor'     => $attr['backgroundImageColor'],
		'overlayType'              => $attr['overlayType'],
		'backgroundCustomSize'     => $attr['backgroundCustomSizeDesktop'],
		'backgroundCustomSizeType' => $attr['backgroundCustomSizeType'],
		'customPosition'           => $attr['customPosition'],
		'xPosition'                => $attr['xPositionDesktop'],
		'xPositionType'            => $attr['xPositionType'],
		'yPosition'                => $attr['yPositionDesktop'],
		'yPositionType'            => $attr['yPositionType'],
	),
	$common_gradient_obj
);
$form_bg_css_desktop = UAGB_Block_Helper::uag_get_background_obj( $bg_obj_desktop );


$overallFlexAlignment = '';
if ( 'left' === $attr['overallAlignment'] ) {
	$overallFlexAlignment = 'flex-start';
} elseif ( 'right' === $attr['overallAlignment'] ) {
	$overallFlexAlignment = 'flex-end';
} else {
	$overallFlexAlignment = $attr['overallAlignment'];
}


$m_selectors = array();
$t_selectors = array();

$selectors = array(
	// form.
	'.wp-block-spectra-pro-register'                       => array_merge(
		$form_bg_css_desktop,
		array(
			'width'          => UAGB_Helper::get_css_value( $attr['formWidth'], $attr['formWidthType'] ),
			'padding-top'    => UAGB_Helper::get_css_value( $attr['formTopPadding'], $attr['formPaddingUnit'] ),
			'padding-right'  => UAGB_Helper::get_css_value( $attr['formRightPadding'], $attr['formPaddingUnit'] ),
			'padding-bottom' => UAGB_Helper::get_css_value( $attr['formBottomPadding'], $attr['formPaddingUnit'] ),
			'padding-left'   => UAGB_Helper::get_css_value( $attr['formLeftPadding'], $attr['formPaddingUnit'] ),
			'box-shadow'     =>
				UAGB_Helper::get_css_value( $attr['boxShadowHOffset'], 'px' ) .
				' ' .
				UAGB_Helper::get_css_value( $attr['boxShadowVOffset'], 'px' ) .
				' ' .
				UAGB_Helper::get_css_value( $attr['boxShadowBlur'], 'px' ) .
				' ' .
				UAGB_Helper::get_css_value( $attr['boxShadowSpread'], 'px' ) .
				' ' .
				$attr['boxShadowColor'] .
				' ' .
				$box_shadow_position_css,
			'text-align'     => $attr['overallAlignment'],
		),
		$form_border
	),
	'.wp-block-spectra-pro-register:hover'                 => array(
		'border-color' => $attr['formBorderHColor'],
	),
	' .spectra-pro-register-form label, .spectra-pro-register-form input, .spectra-pro-register-form textarea' => array(
		'text-align' => $attr['overallAlignment'],
	),
	' .spectra-pro-register-form .spectra-pro-register-form__terms-wrap' => array(
		'justify-content' => $overallFlexAlignment,
	),

	'.wp-block-spectra-pro-register .spectra-pro-register-form__name' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGap'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__email' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGap'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__password' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGap'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__reenter-password' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGap'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__terms' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGap'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__username' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGap'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__recaptcha' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGap'], $attr['rowGapUnit'] ),
	),
	// input.
	' .spectra-pro-register-form input::placeholder'       => array(
		'font-size' => UAGB_Helper::get_css_value( $attr['inputFontSize'], $attr['inputFontSizeType'] ),
		'color'     => $attr['inputplaceholderColor'],
	),
	' .spectra-pro-register-form input:not([type="checkbox"])' => array_merge(
		array(
			'font-size'        => UAGB_Helper::get_css_value( $attr['inputFontSize'], $attr['inputFontSizeType'] ),
			'padding-top'      => UAGB_Helper::get_css_value( $attr['paddingFieldTop'], $attr['paddingFieldUnit'] ) . ' !important',
			'padding-bottom'   => UAGB_Helper::get_css_value( $attr['paddingFieldBottom'], $attr['paddingFieldUnit'] ) . ' !important',
			'padding-left'     => UAGB_Helper::get_css_value( $attr['paddingFieldLeft'], $attr['paddingFieldUnit'] ) . ' !important',
			'padding-right'    => UAGB_Helper::get_css_value( $attr['paddingFieldRight'], $attr['paddingFieldUnit'] ) . ' !important',
			'color'            => $attr['inputColor'],
			'background-color' => $attr['inputBGColor'],
		),
		$input_border
	),
	' .spectra-pro-register-form input:hover::placeholder' => array(
		'color' => $attr['inputplaceholderHoverColor'] . '!important',
	),
	' .spectra-pro-register-form input:focus::placeholder' => array(
		'color' => $attr['inputplaceholderActiveColor'] . '!important',
	),
	' .spectra-pro-register-form input:hover'              => array(
		'background-color' => $attr['inputBGHoverColor'],
		'border-color'     => $attr['fieldBorderHColor'],
	),
	' .spectra-pro-register-form input:focus'              => array(
		'background-color' => $attr['bgActiveColor'],
	),
	' form.spectra-pro-register-form .spectra-pro-register-form__field-wrapper>svg' => array_merge(
		array(
			'width'  => UAGB_Helper::get_css_value( $attr['fieldIconSize'], $attr['fieldIconSizeType'] ),
			'fill'   => $attr['fieldIconColor'],
			'height' => ( array_key_exists( 'border-top-width', $input_border ) && array_key_exists( 'border-bottom-width', $input_border ) ) ?
						'calc( 100% - ' . $input_border['border-top-width'] . ' - ' . $input_border['border-bottom-width'] . ' )'
						: '',
			'top'    => array_key_exists( 'border-top-width', $input_border ) ? $input_border['border-top-width'] : '',
			'bottom' => array_key_exists( 'border-bottom-width', $input_border ) ? $input_border['border-bottom-width'] : '',
			'left'   => array_key_exists( 'border-left-width', $input_border ) ? $input_border['border-left-width'] : '',
			'right'  => array_key_exists( 'border-right-width', $input_border ) ? $input_border['border-right-width'] : '',
		),
		array(
			'border-width' => UAGB_Helper::get_css_value( $attr['fieldIconBorderRightWidth'], 'px' ),
			'border-color' => $attr['fieldIconBorderColor'],
		)
	),
	' .spectra-pro-register-form .spectra-pro-register-form__field-wrapper:hover > svg' => array(
		'border-color' => $attr['fieldBorderHColor'],
	),
	// checkbox.
	' .spectra-pro-register-form .spectra-pro-register-form__terms-checkbox-checkmark' => array(
		'width'         => UAGB_Helper::get_css_value( $attr['checkboxSize'], 'px' ),
		'height'        => UAGB_Helper::get_css_value( $attr['checkboxSize'], 'px' ),
		'background'    => $attr['checkboxBackgroundColor'],
		'border-width'  => UAGB_Helper::get_css_value( $attr['checkboxBorderWidth'], 'px' ),
		'border-radius' => UAGB_Helper::get_css_value( $attr['checkboxBorderRadius'], 'px' ),
		'border-color'  => $attr['checkboxBorderColor'],
	),
	' .spectra-pro-register-form__terms-checkbox .spectra-pro-register-form__terms-checkbox-checkmark:after' => array(
		'font-size' => UAGB_Helper::get_css_value( $attr['checkboxSize'] / 2, 'px' ),
		'color'     => $attr['checkboxColor'],
	),
	// If the user clicks on the checkbox, light it up with some box shadow to portray some interaction!
	' .spectra-pro-register-form__terms-checkbox input[type="checkbox"]:focus + .spectra-pro-register-form__terms-checkbox-checkmark' => array(
		'box-shadow' => $attr['checkboxGlowEnable'] && $attr['checkboxGlowColor'] ? ( '0 0 0 1px ' . $attr['checkboxGlowColor'] ) : '',
	),

	// Info Link.
	'.wp-block-spectra-pro-register .spectra-pro-register-login-info' => array(
		'color'           => $attr['loginInfoColor'],
		'font-size'       => UAGB_Helper::get_css_value( $attr['loginInfoFontSize'], $attr['loginInfoFontSizeType'] ),
		'font-family'     => $attr['loginInfoFontFamily'],
		'font-style'      => $attr['loginInfoFontStyle'],
		'text-decoration' => $attr['loginInfoDecoration'],
		'text-transform'  => $attr['loginInfoTransform'],
		'font-weight'     => $attr['loginInfoFontWeight'],
		'letter-spacing'  => UAGB_Helper::get_css_value( $attr['loginInfoLetterSpacing'], $attr['loginInfoLetterSpacing'] ),

	),
	'.wp-block-spectra-pro-register .spectra-pro-register-login-info a' => array(
		'color' => $attr['loginInfoLinkColor'],
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-login-info:hover' => array(
		'color' => $attr['loginInfoHoverColor'],
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-login-info a:hover' => array(
		'color' => $attr['loginInfoLinkHoverColor'],
	),

	// label.
	' .spectra-pro-register-form label'                    => array(
		'color'         => $attr['labelColor'],
		'font-size'     => UAGB_Helper::get_css_value( $attr['labelFontSize'], $attr['labelFontSizeType'] ),
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['labelGap'], $attr['labelGapUnit'] ),
	),
	' .spectra-pro-register-form label:hover'              => array(
		'color' => $attr['labelHoverColor'],
	),
	' .spectra-pro-register-form .spectra-pro-register-form__terms-label' => array(
		'color'           => $attr['labelColor'],
		'font-size'       => UAGB_Helper::get_css_value( $attr['labelFontSize'], $attr['labelFontSizeType'] ),
		'line-height'     => UAGB_Helper::get_css_value( $attr['labelLineHeight'], $attr['labelLineHeightType'] ),
		'font-family'     => $attr['labelFontFamily'],
		'font-style'      => $attr['labelFontStyle'],
		'text-transform'  => $attr['labelTransform'],
		'text-decoration' => $attr['labelDecoration'],
		'font-weight'     => $attr['labelFontWeight'],
		'letter-spacing'  => UAGB_Helper::get_css_value( $attr['labelLetterSpacing'], $attr['labelLetterSpacingType'] ),
	),
	' .spectra-pro-register-form .spectra-pro-register-form__terms-label:hover' => array(
		'color' => $attr['labelHoverColor'],
	),

	'.wp-block-spectra-pro-register.wp-block-spectra-pro-register__logged-in-message' => array(
		'font-family'     => $attr['labelFontFamily'],
		'font-style'      => $attr['labelFontStyle'],
		'text-decoration' => $attr['labelDecoration'],
		'text-transform'  => $attr['labelTransform'],
		'font-weight'     => $attr['labelFontWeight'],
		'font-size'       => UAGB_Helper::get_css_value( $attr['labelFontSize'], $attr['labelFontSizeType'] ),
		'line-height'     => UAGB_Helper::get_css_value( $attr['labelLineHeight'], $attr['labelLineHeightType'] ),
		'letter-spacing'  => UAGB_Helper::get_css_value( $attr['labelLetterSpacing'], $attr['labelLetterSpacingType'] ),
		'color'           => $attr['labelColor'],
	),

	'.wp-block-spectra-pro-register.wp-block-spectra-pro-register__logged-in-message a' => array(
		'color' => $attr['loginInfoLinkColor'],
	),

	'.wp-block-spectra-pro-register.wp-block-spectra-pro-register__logged-in-message a:hover' => array(
		'color' => $attr['loginInfoLinkHoverColor'],
	),

	// regisgter button.
	' .spectra-pro-register-form .spectra-pro-register-form__submit.wp-block-button__link' => array_merge(
		array(
			'font-size'        => UAGB_Helper::get_css_value( $attr['registerBtnFontSize'], $attr['registerBtnFontSizeType'] ),
			'color'            => $attr['registerBtnColor'],
			'background-color' => $attr['registerBtnBgColor'],
			'padding-top'      => UAGB_Helper::get_css_value( $attr['registerPaddingBtnTop'], $attr['registerPaddingBtnUnit'] ),
			'padding-bottom'   => UAGB_Helper::get_css_value( $attr['registerPaddingBtnBottom'], $attr['registerPaddingBtnUnit'] ),
			'padding-left'     => UAGB_Helper::get_css_value( $attr['registerPaddingBtnLeft'], $attr['registerPaddingBtnUnit'] ),
			'padding-right'    => UAGB_Helper::get_css_value( $attr['registerPaddingBtnRight'], $attr['registerPaddingBtnUnit'] ),
			// alignment styling.
			'margin'           => $align_register_btn_margin,
			'margin-bottom'    => UAGB_Helper::get_css_value( $attr['rowGap'], $attr['rowGapUnit'] ),
			'column-gap'       => UAGB_Helper::get_css_value( $attr['ctaIconSpace'], $attr['ctaIconSpaceType'] ),
		),
		$register_btn_border
	),

	' .spectra-pro-register-form .spectra-pro-register-form__submit.wp-block-button__link svg' => array(
		'width'  => UAGB_Helper::get_css_value( $attr['registerBtnFontSize'], $attr['registerBtnFontSizeType'] ),
		'height' => UAGB_Helper::get_css_value( $attr['registerBtnFontSize'], $attr['registerBtnFontSizeType'] ),
	),

	' .spectra-pro-register-form .spectra-pro-register-form__submit:hover' => array(
		'color'            => $attr['registerBtnColorHover'],
		'background-color' => $attr['registerBtnBgColorHover'],
		'border-color'     => $attr['btnBorderHColor'],
	),



	// message color control.
	' .spectra-pro-register-form-status__success'          => array(
		'border-left-color' => $attr['successMessageBorderColor'],
		'background-color'  => $attr['successMessageBackground'],
		'color'             => $attr['successMessageColor'],
		'text-align'        => $attr['overallAlignment'],
	),
	' .spectra-pro-register-form-status__error'            => array(
		'border-left-color' => $attr['errorMessageBorderColor'],
		'background-color'  => $attr['errorMessageBackground'],
		'color'             => $attr['errorMessageColor'],
		'text-align'        => $attr['overallAlignment'],
	),
	' .spectra-pro-register-form-status__error-item'       => array(
		'border-left-color' => $attr['errorMessageBorderColor'],
		'background-color'  => $attr['errorMessageBackground'],
		'color'             => $attr['errorMessageColor'],
		'text-align'        => $attr['overallAlignment'],
	),
	' .spectra-pro-register-form__input-error'             => array(
		'border-color' => $attr['errorFieldColor'] . '!important',
	),
	' .spectra-pro-register-form__field-error-message'     => array(
		'color'      => $attr['errorFieldColor'],
		'text-align' => $attr['overallAlignment'],
	),
	' .spectra-pro-register-form__field-success-message'   => array(
		'text-align' => $attr['overallAlignment'],
	),
);

// If hover blur or hover color are set, show the hover shadow.
if ( ( ( '' !== $attr['boxShadowBlurHover'] ) && ( null !== $attr['boxShadowBlurHover'] ) ) || '' !== $attr['boxShadowColorHover'] ) {
	$selectors['.wp-block-spectra-pro-register:hover']['box-shadow'] = UAGB_Helper::get_css_value( $attr['boxShadowHOffsetHover'], 'px' ) . ' ' . UAGB_Helper::get_css_value( $attr['boxShadowVOffsetHover'], 'px' ) . ' ' . UAGB_Helper::get_css_value( $attr['boxShadowBlurHover'], 'px' ) . ' ' . UAGB_Helper::get_css_value( $attr['boxShadowSpreadHover'], 'px' ) . ' ' . $attr['boxShadowColorHover'] . ' ' . $box_shadow_position_css_hover;
}



$bg_obj_tablet      = array_merge(
	array(
		'backgroundType'           => $attr['backgroundType'],
		'backgroundImage'          => $attr['backgroundImageTablet'],
		'backgroundColor'          => $attr['backgroundColor'],
		'backgroundRepeat'         => $attr['backgroundRepeatTablet'],
		'backgroundPosition'       => $attr['backgroundPositionTablet'],
		'backgroundSize'           => $attr['backgroundSizeTablet'],
		'backgroundAttachment'     => $attr['backgroundAttachmentTablet'],
		'backgroundImageColor'     => $attr['backgroundImageColor'],
		'overlayType'              => $attr['overlayType'],
		'backgroundCustomSize'     => $attr['backgroundCustomSizeTablet'],
		'backgroundCustomSizeType' => $attr['backgroundCustomSizeType'],
		'customPosition'           => $attr['customPosition'],
		'xPosition'                => $attr['xPositionTablet'],
		'xPositionType'            => $attr['xPositionTypeTablet'],
		'yPosition'                => $attr['yPositionTablet'],
		'yPositionType'            => $attr['yPositionTypeTablet'],
	),
	$common_gradient_obj
);
$form_bg_css_tablet = UAGB_Block_Helper::uag_get_background_obj( $bg_obj_tablet );

$t_selectors = array(



	// form.
	'.wp-block-spectra-pro-register'                 => array_merge(
		$form_bg_css_tablet,
		array(
			'width'          => UAGB_Helper::get_css_value( $attr['formWidthTablet'], $attr['formWidthTypeTablet'] ),
			'padding-top'    => UAGB_Helper::get_css_value( $attr['formTopPaddingTablet'], $attr['formPaddingUnitTablet'] ),
			'padding-right'  => UAGB_Helper::get_css_value( $attr['formRightPaddingTablet'], $attr['formPaddingUnitTablet'] ),
			'padding-bottom' => UAGB_Helper::get_css_value( $attr['formBottomPaddingTablet'], $attr['formPaddingUnitTablet'] ),
			'padding-left'   => UAGB_Helper::get_css_value( $attr['formLeftPaddingTablet'], $attr['formPaddingUnitTablet'] ),
		),
		$form_border_Tablet
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__name' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGapTablet'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__email' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGapTablet'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__password' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGapTablet'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__reenter-password' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGapTablet'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__terms' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGapTablet'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__username' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGapTablet'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__recaptcha' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGapTablet'], $attr['rowGapUnit'] ),
	),

	// input.
	' .spectra-pro-register-form .spectra-pro-register-form__field-wrapper>svg' => array_merge(
		array(
			'height' => ( array_key_exists( 'border-top-width', $input_border_Tablet ) && array_key_exists( 'border-bottom-width', $input_border_Tablet ) ) ?
						'calc( 100% - ' . $input_border_Tablet['border-top-width'] . ' - ' . $input_border_Tablet['border-bottom-width'] . ' )'
						: '',
			'top'    => array_key_exists( 'border-top-width', $input_border_Tablet ) ? $input_border_Tablet['border-top-width'] : '',
			'bottom' => array_key_exists( 'border-bottom-width', $input_border_Tablet ) ? $input_border_Tablet['border-bottom-width'] : '',
			'left'   => array_key_exists( 'border-left-width', $input_border_Tablet ) ? $input_border_Tablet['border-left-width'] : '',
			'right'  => array_key_exists( 'border-right-width', $input_border_Tablet ) ? $input_border_Tablet['border-right-width'] : '',
		)
	),
	' .spectra-pro-register-form input::placeholder' => array(
		'font-size' => UAGB_Helper::get_css_value( $attr['inputFontSizeTablet'], $attr['inputFontSizeType'] ),
	),
	' .spectra-pro-register-form input:not([type="checkbox"])' => array_merge(
		array(
			'padding-top'    => UAGB_Helper::get_css_value( $attr['paddingFieldTopTablet'], $attr['paddingFieldUnitTablet'] ) . ' !important',
			'padding-bottom' => UAGB_Helper::get_css_value( $attr['paddingFieldBottomTablet'], $attr['paddingFieldUnitTablet'] ) . ' !important',
			'padding-left'   => UAGB_Helper::get_css_value( $attr['paddingFieldLeftTablet'], $attr['paddingFieldUnitTablet'] ) . ' !important',
			'padding-right'  => UAGB_Helper::get_css_value( $attr['paddingFieldRightTablet'], $attr['paddingFieldUnitTablet'] ) . ' !important',
		),
		$input_border_Tablet
	),

	// Login Information.
	'.wp-block-spectra-pro-register .spectra-pro-register-login-info' => array(
		'font-size' => UAGB_Helper::get_css_value( $attr['loginInfoFontSizeTablet'], $attr['loginInfoFontSizeType'] ),
	),

	// label.
	' .spectra-pro-register-form label'              => array(
		'font-size'     => UAGB_Helper::get_css_value( $attr['labelFontSizeTablet'], $attr['labelFontSizeType'] ),
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['labelGapTablet'], $attr['labelGapUnit'] ),

	),

	' .spectra-pro-register-form .spectra-pro-register-form__terms-label' => array(
		'font-size'      => UAGB_Helper::get_css_value( $attr['labelFontSizeTablet'], $attr['labelFontSizeType'] ),
		'line-height'    => UAGB_Helper::get_css_value( $attr['labelLineHeightTablet'], $attr['labelLineHeightType'] ),
		'letter-spacing' => UAGB_Helper::get_css_value( $attr['labelLetterSpacingTablet'], $attr['labelLetterSpacingType'] ),
	),

	// register button.
	' .spectra-pro-register-form .spectra-pro-register-form__submit.wp-block-button__link' => array_merge(
		array(
			'padding-top'    => UAGB_Helper::get_css_value( $attr['registerPaddingBtnTopTablet'], $attr['registerTabletPaddingBtnUnit'] ),
			'padding-bottom' => UAGB_Helper::get_css_value( $attr['registerPaddingBtnBottomTablet'], $attr['registerTabletPaddingBtnUnit'] ),
			'padding-left'   => UAGB_Helper::get_css_value( $attr['registerPaddingBtnLeftTablet'], $attr['registerTabletPaddingBtnUnit'] ),
			'padding-right'  => UAGB_Helper::get_css_value( $attr['registerPaddingBtnRightTablet'], $attr['registerTabletPaddingBtnUnit'] ),
			// alignment styling.
			'margin'         => $align_register_btn_margin_tablet,
			'margin-bottom'  => UAGB_Helper::get_css_value( $attr['rowGapTablet'], $attr['rowGapUnit'] ),
			'column-gap'     => UAGB_Helper::get_css_value( $attr['ctaIconSpaceTablet'], $attr['ctaIconSpaceType'] ),
		),
		$register_btn_border_Tablet
	),

	' .spectra-pro-register-form .spectra-pro-register-form__submit.wp-block-button__link svg' => array(
		'width'  => UAGB_Helper::get_css_value( $attr['registerBtnFontSizeTablet'], $attr['registerBtnFontSizeType'] ),
		'height' => UAGB_Helper::get_css_value( $attr['registerBtnFontSizeTablet'], $attr['registerBtnFontSizeType'] ),
	),


);


$bg_obj_mobile        = array_merge(
	array(
		'backgroundType'           => $attr['backgroundType'],
		'backgroundImage'          => $attr['backgroundImageMobile'],
		'backgroundColor'          => $attr['backgroundColor'],
		'backgroundRepeat'         => $attr['backgroundRepeatMobile'],
		'backgroundPosition'       => $attr['backgroundPositionMobile'],
		'backgroundSize'           => $attr['backgroundSizeMobile'],
		'backgroundAttachment'     => $attr['backgroundAttachmentMobile'],
		'backgroundImageColor'     => $attr['backgroundImageColor'],
		'overlayType'              => $attr['overlayType'],
		'backgroundCustomSize'     => $attr['backgroundCustomSizeMobile'],
		'backgroundCustomSizeType' => $attr['backgroundCustomSizeType'],
		'customPosition'           => $attr['customPosition'],
		'xPosition'                => $attr['xPositionMobile'],
		'xPositionType'            => $attr['xPositionTypeMobile'],
		'yPosition'                => $attr['yPositionMobile'],
		'yPositionType'            => $attr['yPositionTypeMobile'],
	),
	$common_gradient_obj
);
$mobile_bg_css_mobile = UAGB_Block_Helper::uag_get_background_obj( $bg_obj_mobile );

$m_selectors = array(



	// form.
	'.wp-block-spectra-pro-register'                 => array_merge(
		$mobile_bg_css_mobile,
		array(
			'width'          => UAGB_Helper::get_css_value( $attr['formWidthMobile'], $attr['formWidthTypeMobile'] ),
			'padding-top'    => UAGB_Helper::get_css_value(
				$attr['formTopPaddingMobile'],
				$attr['formPaddingUnitMobile']
			),
			'padding-right'  => UAGB_Helper::get_css_value(
				$attr['formRightPaddingMobile'],
				$attr['formPaddingUnitMobile']
			),
			'padding-bottom' => UAGB_Helper::get_css_value(
				$attr['formBottomPaddingMobile'],
				$attr['formPaddingUnitMobile']
			),
			'padding-left'   => UAGB_Helper::get_css_value(
				$attr['formLeftPaddingMobile'],
				$attr['formPaddingUnitMobile']
			),
		),
		$form_border_Mobile
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__name' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGapMobile'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__email' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGapMobile'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__password' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGapMobile'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__reenter-password' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGapMobile'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__username' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGapMobile'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__terms' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGapMobile'], $attr['rowGapUnit'] ),
	),
	'.wp-block-spectra-pro-register .spectra-pro-register-form__recaptcha' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['rowGapMobile'], $attr['rowGapUnit'] ),
	),

	// input.
	' .spectra-pro-register-form .spectra-pro-register-form__field-wrapper>svg' => array_merge(
		array(
			'height' => ( array_key_exists( 'border-top-width', $input_border_Mobile ) && array_key_exists( 'border-bottom-width', $input_border_Mobile ) ) ?
						'calc( 100% - ' . $input_border_Mobile['border-top-width'] . ' - ' . $input_border_Mobile['border-bottom-width'] . ' )'
						: '',
			'top'    => array_key_exists( 'border-top-width', $input_border_Mobile ) ? $input_border_Mobile['border-top-width'] : '',
			'bottom' => array_key_exists( 'border-bottom-width', $input_border_Mobile ) ? $input_border_Mobile['border-bottom-width'] : '',
			'left'   => array_key_exists( 'border-left-width', $input_border_Mobile ) ? $input_border_Mobile['border-left-width'] : '',
			'right'  => array_key_exists( 'border-right-width', $input_border_Mobile ) ? $input_border_Mobile['border-right-width'] : '',
		)
	),
	' .spectra-pro-register-form input::placeholder' => array(
		'font-size' => UAGB_Helper::get_css_value( $attr['inputFontSizeMobile'], $attr['inputFontSizeType'] ),
	),
	' .spectra-pro-register-form input:not([type="checkbox"])' => array_merge(
		array(
			'padding-top'    => UAGB_Helper::get_css_value( $attr['paddingFieldTopMobile'], $attr['paddingFieldUnitmobile'] ) . ' !important',
			'padding-bottom' => UAGB_Helper::get_css_value( $attr['paddingFieldBottomMobile'], $attr['paddingFieldUnitmobile'] ) . ' !important',
			'padding-left'   => UAGB_Helper::get_css_value( $attr['paddingFieldLeftMobile'], $attr['paddingFieldUnitmobile'] ) . ' !important',
			'padding-right'  => UAGB_Helper::get_css_value( $attr['paddingFieldRightMobile'], $attr['paddingFieldUnitmobile'] ) . ' !important',
		),
		$input_border_Tablet
	),

	// Login Information.
	'.wp-block-spectra-pro-register .spectra-pro-register-login-info' => array(
		'font-size' => UAGB_Helper::get_css_value( $attr['loginInfoFontSizeMobile'], $attr['loginInfoFontSizeType'] ),
	),

	// label.
	' .spectra-pro-register-form label'              => array(
		'font-size'     => UAGB_Helper::get_css_value( $attr['labelFontSizeMobile'], $attr['labelFontSizeType'] ),
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['labelGapMobile'], $attr['labelGapUnit'] ),
	),

	' .spectra-pro-register-form .spectra-pro-register-form__terms-label' => array(
		'font-size'      => UAGB_Helper::get_css_value( $attr['labelFontSizeMobile'], $attr['labelFontSizeType'] ),
		'line-height'    => UAGB_Helper::get_css_value( $attr['labelLineHeightMobile'], $attr['labelLineHeightType'] ),
		'letter-spacing' => UAGB_Helper::get_css_value( $attr['labelLetterSpacingMobile'], $attr['labelLetterSpacingType'] ),
	),

	// register button.
	' .spectra-pro-register-form .spectra-pro-register-form__submit.wp-block-button__link' => array_merge(
		array(
			'padding-top'    => UAGB_Helper::get_css_value( $attr['registerPaddingBtnTopMobile'], $attr['registerMobilePaddingBtnUnit'] ),
			'padding-bottom' => UAGB_Helper::get_css_value( $attr['registerPaddingBtnBottomMobile'], $attr['registerMobilePaddingBtnUnit'] ),
			'padding-left'   => UAGB_Helper::get_css_value( $attr['registerPaddingBtnLeftMobile'], $attr['registerMobilePaddingBtnUnit'] ),
			'padding-right'  => UAGB_Helper::get_css_value( $attr['registerPaddingBtnRightMobile'], $attr['registerMobilePaddingBtnUnit'] ),

			// alignmnet style.
			'margin'         => $align_register_btn_margin_mobile,
			'margin-bottom'  => UAGB_Helper::get_css_value( $attr['rowGapMobile'], $attr['rowGapUnit'] ),
			'column-gap'     => UAGB_Helper::get_css_value( $attr['ctaIconSpaceMobile'], $attr['ctaIconSpaceType'] ),
		),
		$register_btn_border_Mobile
	),

	' .spectra-pro-register-form .spectra-pro-register-form__submit.wp-block-button__link svg' => array(
		'width'  => UAGB_Helper::get_css_value( $attr['registerBtnFontSizeMobile'], $attr['registerBtnFontSizeType'] ),
		'height' => UAGB_Helper::get_css_value( $attr['registerBtnFontSizeMobile'], $attr['registerBtnFontSizeType'] ),
	),


);

if ( 'full' === $attr['alignRegisterBtn'] ) {
	$selectors[' .spectra-pro-register-form .spectra-pro-register-form__submit.wp-block-button__link']['width'] = '100%';
}
if ( 'full' === $attr['alignRegisterBtnTablet'] ) {
	$t_selectors[' .spectra-pro-register-form .spectra-pro-register-form__submit.wp-block-button__link']['width'] = '100%';
}
if ( 'full' === $attr['alignRegisterBtnMobile'] ) {
	$m_selectors[' .spectra-pro-register-form .spectra-pro-register-form__submit.wp-block-button__link']['width'] = '100%';
}

$combined_selectors = array(
	'desktop' => $selectors,
	'tablet'  => $t_selectors,
	'mobile'  => $m_selectors,
);

$base_selector      = '.uagb-block-';
$combined_selectors = UAGB_Helper::get_typography_css( $attr, 'input', ' .spectra-pro-register-form input::placeholder', $combined_selectors );
$combined_selectors = UAGB_Helper::get_typography_css( $attr, 'input', ' .spectra-pro-register-form input:not([type="checkbox"])', $combined_selectors );
$combined_selectors = UAGB_Helper::get_typography_css( $attr, 'registerBtn', ' .spectra-pro-register-form .spectra-pro-register-form__submit.wp-block-button__link', $combined_selectors );
$combined_selectors = UAGB_Helper::get_typography_css( $attr, 'label', ' .spectra-pro-register-form label', $combined_selectors );

return UAGB_Helper::generate_all_css( $combined_selectors, $base_selector . $id );
