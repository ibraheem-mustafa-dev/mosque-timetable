<?php
/**
 * Frontend CSS & Google Fonts loading File.
 *
 * @since 1.0.0
 *
 * @package spectra-pro
 */

// Add fonts.
SpectraPro\Core\Utils::blocks_login_gfont( $attr );

$is_rtl = is_rtl();

$form_border_css        = UAGB_Block_Helper::uag_generate_border_css( $attr, 'form' );
$form_border_css_tablet = UAGB_Block_Helper::uag_generate_border_css( $attr, 'form', 'tablet' );
$form_border_css_mobile = UAGB_Block_Helper::uag_generate_border_css( $attr, 'form', 'mobile' );

$fields_border_css        = UAGB_Block_Helper::uag_generate_border_css( $attr, 'fields' );
$fields_border_css_tablet = UAGB_Block_Helper::uag_generate_border_css( $attr, 'fields', 'tablet' );
$fields_border_css_mobile = UAGB_Block_Helper::uag_generate_border_css( $attr, 'fields', 'mobile' );

$login_border_css        = UAGB_Block_Helper::uag_generate_border_css( $attr, 'login' );
$login_border_css_tablet = UAGB_Block_Helper::uag_generate_border_css( $attr, 'login', 'tablet' );
$login_border_css_mobile = UAGB_Block_Helper::uag_generate_border_css( $attr, 'login', 'mobile' );

$full_width_login_btn        = 'full' === $attr['alignLoginBtn'] ? array( 'width' => '100%' ) : array();
$full_width_login_btn_tablet = 'full' === $attr['alignLoginBtnTablet'] ? array( 'width' => '100%' ) : array();
$full_width_login_btn_mobile = 'full' === $attr['alignLoginBtnMobile'] ? array( 'width' => '100%' ) : array();



$field_icon_css = array(
	'width'        => UAGB_Helper::get_css_value( $attr['fieldsIconSize'], $attr['fieldsIconSizeType'] ),
	'height'       => ( array_key_exists( 'border-top-width', $fields_border_css ) && array_key_exists( 'border-bottom-width', $fields_border_css ) ) ?
						'calc( 100% - ' . $fields_border_css['border-top-width'] . ' - ' . $fields_border_css['border-bottom-width'] . ' )'
						: '',
	'top'          => array_key_exists( 'border-top-width', $fields_border_css ) ? $fields_border_css['border-top-width'] : '',
	'bottom'       => array_key_exists( 'border-bottom-width', $fields_border_css ) ? $fields_border_css['border-bottom-width'] : '',
	'left'         => array_key_exists( 'border-left-width', $fields_border_css ) ? $fields_border_css['border-left-width'] : '',
	'right'        => array_key_exists( 'border-right-width', $fields_border_css ) ? $fields_border_css['border-right-width'] : '',
	'border-width' => UAGB_Helper::get_css_value( $attr['fieldsIconBorderWidth'], 'px' ),
	'border-color' => $attr['fieldsIconBorderColor'],
	'fill'         => $attr['fieldsIconColor'],
);

$field_icon_css_tablet = array(
	'height' => ( array_key_exists( 'border-top-width', $fields_border_css_tablet ) && array_key_exists( 'border-bottom-width', $fields_border_css_tablet ) ) ?
				'calc( 100% - ' . $fields_border_css_tablet['border-top-width'] . ' - ' . $fields_border_css_tablet['border-bottom-width'] . ' )' : '',
	'top'    => array_key_exists( 'border-top-width', $fields_border_css_tablet ) ? $fields_border_css_tablet['border-top-width'] : '',
	'bottom' => array_key_exists( 'border-bottom-width', $fields_border_css_tablet ) ? $fields_border_css_tablet['border-bottom-width'] : '',
	'left'   => array_key_exists( 'border-left-width', $fields_border_css_tablet ) ? $fields_border_css_tablet['border-left-width'] : '',
	'right'  => array_key_exists( 'border-right-width', $fields_border_css_tablet ) ? $fields_border_css_tablet['border-right-width'] : '',
);

$field_icon_css_mobile = array(
	'height' => ( array_key_exists( 'border-top-width', $fields_border_css_tablet ) && array_key_exists( 'border-bottom-width', $fields_border_css_tablet ) ) ?
				'calc( 100% - ' . $fields_border_css_mobile['border-top-width'] . ' - ' . $fields_border_css_mobile['border-bottom-width'] . ' )' : '',
	'top'    => array_key_exists( 'border-top-width', $fields_border_css_mobile ) ? $fields_border_css_mobile['border-top-width'] : '',
	'bottom' => array_key_exists( 'border-bottom-width', $fields_border_css_mobile ) ? $fields_border_css_mobile['border-bottom-width'] : '',
	'left'   => array_key_exists( 'border-left-width', $fields_border_css_mobile ) ? $fields_border_css_mobile['border-left-width'] : '',
	'right'  => array_key_exists( 'border-right-width', $fields_border_css_mobile ) ? $fields_border_css_mobile['border-right-width'] : '',
);

$username_icon_input_selector = '.wp-block-spectra-pro-login.wp-block-spectra-pro-login .spectra-pro-login-form__user-login .spectra-pro-login-form-username-wrap.spectra-pro-login-form-username-wrap--have-icon input';
$password_icon_input_selector = '.wp-block-spectra-pro-login.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass .spectra-pro-login-form-pass-wrap.spectra-pro-login-form-pass-wrap--have-icon input';

// shadow.
$box_shadow_position_css = $attr['boxShadowPosition'];

if ( 'outset' === $attr['boxShadowPosition'] ) {
	$box_shadow_position_css = '';
}

$box_shadow_position_css_hover = $attr['boxShadowPositionHover'];

if ( 'outset' === $attr['boxShadowPositionHover'] ) {
	$box_shadow_position_css_hover = '';
}

$m_selectors = array();
$t_selectors = array();

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

// Background.
$bg_obj_desktop           = array_merge(
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
$container_bg_css_desktop = UAGB_Block_Helper::uag_get_background_obj( $bg_obj_desktop );

$bg_obj_tablet           = array_merge(
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
$container_bg_css_tablet = UAGB_Block_Helper::uag_get_background_obj( $bg_obj_tablet );

$bg_obj_mobile           = array_merge(
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
$container_bg_css_mobile = UAGB_Block_Helper::uag_get_background_obj( $bg_obj_mobile );

$form_label_style = array(
	'font-family'     => $attr['labelFontFamily'],
	'font-style'      => $attr['labelFontStyle'],
	'text-decoration' => $attr['labelDecoration'],
	'text-transform'  => $attr['labelTransform'],
	'font-weight'     => $attr['labelFontWeight'],
	'font-size'       => UAGB_Helper::get_css_value( $attr['labelFontSize'], $attr['labelFontSizeType'] ),
	'line-height'     => UAGB_Helper::get_css_value(
		$attr['labelLineHeight'],
		$attr['labelLineHeightType']
	),
	'letter-spacing'  => UAGB_Helper::get_css_value( $attr['labelLetterSpacing'], $attr['labelLetterSpacingType'] ),
	'color'           => $attr['labelColor'],
	'margin-top'      => UAGB_Helper::get_css_value(
		$attr['labelTopMargin'],
		$attr['labelMarginUnit']
	),
	'margin-right'    => UAGB_Helper::get_css_value(
		$attr['labelRightMargin'],
		$attr['labelMarginUnit']
	),
	'margin-bottom'   => UAGB_Helper::get_css_value(
		$attr['labelBottomMargin'],
		$attr['labelMarginUnit']
	),
	'margin-left'     => UAGB_Helper::get_css_value(
		$attr['labelLeftMargin'],
		$attr['labelMarginUnit']
	),
);

$form_input_style = array_merge(
	array(
		'font-family'     => $attr['fieldsFontFamily'],
		'font-style'      => $attr['fieldsFontStyle'],
		'text-decoration' => $attr['fieldsDecoration'],
		'text-transform'  => $attr['fieldsTransform'],
		'font-weight'     => $attr['fieldsFontWeight'],
		'font-size'       => UAGB_Helper::get_css_value( $attr['fieldsFontSize'], $attr['fieldsFontSizeType'] ),
		'letter-spacing'  => UAGB_Helper::get_css_value( $attr['fieldsLetterSpacing'], $attr['fieldsLetterSpacingType'] ),
		'line-height'     => UAGB_Helper::get_css_value( $attr['fieldsLineHeight'], $attr['fieldsLineHeightType'] ),
		'background'      => $attr['fieldsBackground'],
		'color'           => $attr['fieldsColor'],
		'padding-top'     => UAGB_Helper::get_css_value( $attr['paddingFieldTop'], $attr['paddingFieldUnit'] ),
		'padding-bottom'  => UAGB_Helper::get_css_value( $attr['paddingFieldBottom'], $attr['paddingFieldUnit'] ),
		'padding-left'    => UAGB_Helper::get_css_value( $attr['paddingFieldLeft'], $attr['paddingFieldUnit'] ),
		'padding-right'   => UAGB_Helper::get_css_value( $attr['paddingFieldRight'], $attr['paddingFieldUnit'] ),
		'text-align'      => $attr['overallAlignment'],
	),
	$fields_border_css
);

$form_input_style_tablet = array_merge(
	array(
		'font-size'      => UAGB_Helper::get_css_value( $attr['fieldsFontSizeTablet'], $attr['fieldsFontSizeType'] ),
		'letter-spacing' => UAGB_Helper::get_css_value( $attr['fieldsLetterSpacingTablet'], $attr['fieldsLetterSpacingType'] ),
		'line-height'    => UAGB_Helper::get_css_value( $attr['fieldsLineHeightTablet'], $attr['fieldsLineHeightType'] ),
		'padding-top'    => UAGB_Helper::get_css_value( $attr['paddingFieldTopTablet'], $attr['paddingFieldUnitTablet'] ),
		'padding-bottom' => UAGB_Helper::get_css_value( $attr['paddingFieldBottomTablet'], $attr['paddingFieldUnitTablet'] ),
		'padding-left'   => UAGB_Helper::get_css_value( $attr['paddingFieldLeftTablet'], $attr['paddingFieldUnitTablet'] ),
		'padding-right'  => UAGB_Helper::get_css_value( $attr['paddingFieldRightTablet'], $attr['paddingFieldUnitTablet'] ),
	),
	$fields_border_css_tablet
);

$form_input_style_mobile = array_merge(
	array(
		'font-size'      => UAGB_Helper::get_css_value( $attr['fieldsFontSizeMobile'], $attr['fieldsFontSizeType'] ),
		'letter-spacing' => UAGB_Helper::get_css_value( $attr['fieldsLetterSpacingMobile'], $attr['fieldsLetterSpacingType'] ),
		'line-height'    => UAGB_Helper::get_css_value( $attr['fieldsLineHeightMobile'], $attr['fieldsLineHeightType'] ),
		'padding-top'    => UAGB_Helper::get_css_value( $attr['paddingFieldTopMobile'], $attr['paddingFieldUnitmobile'] ),
		'padding-bottom' => UAGB_Helper::get_css_value( $attr['paddingFieldBottomMobile'], $attr['paddingFieldUnitmobile'] ),
		'padding-left'   => UAGB_Helper::get_css_value( $attr['paddingFieldLeftMobile'], $attr['paddingFieldUnitmobile'] ),
		'padding-right'  => UAGB_Helper::get_css_value( $attr['paddingFieldRightMobile'], $attr['paddingFieldUnitmobile'] ),
	),
	$fields_border_css_mobile
);



$selectors = array(
	'.wp-block-spectra-pro-login'                       => array_merge(
		array(
			'width'          => UAGB_Helper::get_css_value( $attr['formWidth'], $attr['formWidthType'] ),
			'padding-top'    => UAGB_Helper::get_css_value( $attr['formTopPadding'], $attr['formPaddingUnit'] ),
			'padding-right'  => UAGB_Helper::get_css_value( $attr['formRightPadding'], $attr['formPaddingUnit'] ),
			'padding-bottom' => UAGB_Helper::get_css_value( $attr['formBottomPadding'], $attr['formPaddingUnit'] ),
			'padding-left'   => UAGB_Helper::get_css_value( $attr['formLeftPadding'], $attr['formPaddingUnit'] ),
			'text-align'     => $attr['overallAlignment'],
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
		),
		$form_border_css,
		$container_bg_css_desktop
	),
	'.wp-block-spectra-pro-login:hover'                 => array(
		'border-color' => $attr['formBorderHColor'],
	),
	' .spectra-pro-login-form__field-error-message'     => array(
		'text-align' => $attr['overallAlignment'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-login' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['formRowsGapSpace'], $attr['formRowsGapSpaceUnit'] ),
	),
	'.wp-block-spectra-pro-login .wp-block-spectra-pro-login__logged-in-message' => array(
		'font-family'     => $attr['labelFontFamily'],
		'font-style'      => $attr['labelFontStyle'],
		'text-decoration' => $attr['labelDecoration'],
		'text-transform'  => $attr['labelTransform'],
		'font-weight'     => $attr['labelFontWeight'],
		'font-size'       => UAGB_Helper::get_css_value( $attr['labelFontSize'], $attr['labelFontSizeType'] ),
		'line-height'     => UAGB_Helper::get_css_value(
			$attr['labelLineHeight'],
			$attr['labelLineHeightType']
		),
		'letter-spacing'  => UAGB_Helper::get_css_value( $attr['labelLetterSpacing'], $attr['labelLetterSpacingType'] ),
		'color'           => $attr['labelColor'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-login label' => $form_label_style,
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-login label:hover' => array(
		'color' => $attr['labelHoverColor'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-login input' => $form_input_style,
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-login input:hover' => array(
		'border-color' => $attr['fieldsBorderHColor'],
		'background'   => $attr['fieldsBackgroundHover'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-login input:focus' => array(
		'background' => $attr['fieldsBackgroundActive'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-login input::placeholder' => array(
		'color' => $attr['placeholderColor'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-login input:hover::placeholder' => array(
		'color' => $attr['placeholderColorHover'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-login input:focus::placeholder' => array(
		'color' => $attr['placeholderColorActive'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass input::placeholder' => array(
		'color' => $attr['placeholderColor'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass input:hover::placeholder' => array(
		'color' => $attr['placeholderColorHover'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass input:focus::placeholder' => array(
		'color' => $attr['placeholderColorActive'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__recaptcha' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['formRowsGapSpace'], $attr['formRowsGapSpaceUnit'] ),
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['formRowsGapSpace'], $attr['formRowsGapSpaceUnit'] ),
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass button' => array(
		'color'        => $attr['eyeIconColor'],
		'margin-right' => array_key_exists( 'border-right-width', $fields_border_css ) && ( ! $is_rtl ) ? 'calc( ' . $fields_border_css['border-right-width'] . ' + 5px )' : '',
		'margin-left'  => array_key_exists( 'border-left-width', $fields_border_css ) && $is_rtl ? 'calc( ' . $fields_border_css['border-left-width'] . ' + 5px )' : '',
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass button span' => array(
		'font-size' => UAGB_Helper::get_css_value( $attr['eyeIconSize'], $attr['eyeIconSizeType'] ),
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass label' => $form_label_style,
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass label:hover' => array(
		'color' => $attr['labelHoverColor'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass input' => $form_input_style,
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass input:hover' => array(
		'color'        => $attr['labelHoverColor'],
		'border-color' => $attr['fieldsBorderHColor'],
		'background'   => $attr['fieldsBackgroundHover'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass input:focus' => array(
		'background' => $attr['fieldsBackgroundActive'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__forgetmenot' => array(
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['formRowsGapSpace'], $attr['formRowsGapSpaceUnit'] ),
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form-forgot-password' => array(
		'margin-top'    => UAGB_Helper::get_css_value( $attr['labelTopMargin'], $attr['labelMarginUnit'] ),
		'margin-right'  => UAGB_Helper::get_css_value( $attr['labelRightMargin'], $attr['labelMarginUnit'] ),
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['labelBottomMargin'], $attr['labelMarginUnit'] ),
		// Left margin not required.
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form-forgot-password a' => array_merge(
		$form_label_style,
		array(
			'margin' => 'unset',
			'color'  => $attr['linkColor'],
		)
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form-forgot-password a:hover' => array(
		'color' => $attr['linkHColor'],
	),

	$username_icon_input_selector                       => array(
		'padding-left' => UAGB_Helper::get_css_value( $attr['paddingFieldLeft'], $attr['paddingFieldUnit'] ),
	),

	$password_icon_input_selector                       => array(
		'padding-left' => UAGB_Helper::get_css_value( $attr['paddingFieldLeft'], $attr['paddingFieldUnit'] ),
	),

	// Field icon - Username.
	'.wp-block-spectra-pro-login .spectra-pro-login-form .spectra-pro-login-form-username-wrap--have-icon > svg' => array_merge(
		$field_icon_css
	),

	// Field icon - Password.
	'.wp-block-spectra-pro-login .spectra-pro-login-form .spectra-pro-login-form__user-pass .spectra-pro-login-form-pass-wrap--have-icon > svg' => array_merge(
		$field_icon_css
	),

	'.wp-block-spectra-pro-login .spectra-pro-login-form-rememberme label' => array_merge(
		$form_label_style,
		array(
			'margin' => 'unset',
		)
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form-rememberme label:hover' => array(
		'color' => $attr['labelHoverColor'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form-rememberme' => array(
		'margin-top'    => UAGB_Helper::get_css_value( $attr['labelTopMargin'], $attr['labelMarginUnit'] ),
		// Right margin not required.
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['labelBottomMargin'], $attr['labelMarginUnit'] ),
		'margin-left'   => UAGB_Helper::get_css_value( $attr['labelLeftMargin'], $attr['labelMarginUnit'] ),
	),
	// checkbox.
	' .spectra-pro-login-form-rememberme .spectra-pro-login-form-rememberme__checkmark' => array(
		'width'         => UAGB_Helper::get_css_value( $attr['checkboxSize'], 'px' ),
		'height'        => UAGB_Helper::get_css_value( $attr['checkboxSize'], 'px' ),
		'background'    => $attr['checkboxBackgroundColor'],
		'border-width'  => UAGB_Helper::get_css_value( $attr['checkboxBorderWidth'], 'px' ),
		'border-radius' => UAGB_Helper::get_css_value( $attr['checkboxBorderRadius'], 'px' ),
		'border-color'  => $attr['checkboxBorderColor'],
	),
	' .spectra-pro-login-form-rememberme .spectra-pro-login-form-rememberme__checkmark:after' => array(
		'font-size' => UAGB_Helper::get_css_value( $attr['checkboxSize'] / 2, 'px' ),
		'color'     => $attr['checkboxColor'],
	),
	// If the user clicks on the checkbox, light it up with some box shadow to portray some interaction!
	' .spectra-pro-login-form-rememberme input[type="checkbox"]:focus + .spectra-pro-login-form-rememberme__checkmark' => array(
		'box-shadow' => $attr['checkboxGlowEnable'] && $attr['checkboxGlowColor'] ? ( '0 0 0 1px ' . $attr['checkboxGlowColor'] ) : '',
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form-register:hover' => array(
		'color' => $attr['linkHColor'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form__submit' => array(
		'justify-content' => $attr['alignLoginBtn'],
		'margin-bottom'   => UAGB_Helper::get_css_value( $attr['formRowsGapSpace'], $attr['formRowsGapSpaceUnit'] ),
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form-submit-button' => array_merge(
		array(
			'font-family'     => $attr['loginFontFamily'],
			'font-style'      => $attr['loginFontStyle'],
			'text-decoration' => $attr['loginDecoration'],
			'text-transform'  => $attr['loginTransform'],
			'font-weight'     => $attr['loginFontWeight'],
			'font-size'       => UAGB_Helper::get_css_value( $attr['loginFontSize'], $attr['loginFontSizeType'] ),
			'letter-spacing'  => UAGB_Helper::get_css_value( $attr['loginLetterSpacing'], $attr['loginLetterSpacingType'] ),
			'line-height'     => UAGB_Helper::get_css_value( $attr['loginLineHeight'], $attr['loginLineHeightType'] ),
			'background'      => $attr['loginBackground'],
			'color'           => $attr['loginColor'],
			'padding-top'     => UAGB_Helper::get_css_value( $attr['loginTopPadding'], $attr['loginPaddingUnit'] ),
			'padding-right'   => UAGB_Helper::get_css_value( $attr['loginRightPadding'], $attr['loginPaddingUnit'] ),
			'padding-bottom'  => UAGB_Helper::get_css_value( $attr['loginBottomPadding'], $attr['loginPaddingUnit'] ),
			'padding-left'    => UAGB_Helper::get_css_value( $attr['loginLeftPadding'], $attr['loginPaddingUnit'] ),
			'column-gap'      => UAGB_Helper::get_css_value( $attr['ctaIconSpace'], $attr['ctaIconSpaceType'] ),

		),
		$full_width_login_btn,
		$login_border_css
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form-submit-button:hover' => array(
		'border-color' => $attr['loginBorderHColor'],
		'background'   => $attr['loginHBackground'],
		'color'        => $attr['loginHColor'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form-submit-button:hover svg' => array(
		'fill' => $attr['loginHColor'],
	),

	'.wp-block-spectra-pro-login .spectra-pro-login-form-status .spectra-pro-login-form-status__success' => array(
		'border-left-color' => $attr['successMessageBorderColor'],
		'background-color'  => $attr['successMessageBackground'],
		'color'             => $attr['successMessageColor'],
	),
	'.wp-block-spectra-pro-login .spectra-pro-login-form-status .spectra-pro-login-form-status__error' => array(
		'border-left-color' => $attr['errorMessageBorderColor'],
		'background-color'  => $attr['errorMessageBackground'],
		'color'             => $attr['errorMessageColor'],
	),



	// Info Link.
	' .wp-block-spectra-pro-login-info'                 => array(
		'color'           => $attr['registerInfoColor'],
		'font-family'     => $attr['registerInfoFontFamily'],
		'font-style'      => $attr['registerInfoFontStyle'],
		'text-decoration' => $attr['registerInfoDecoration'],
		'text-transform'  => $attr['registerInfoTransform'],
		'font-weight'     => $attr['registerInfoFontWeight'],
		'font-size'       => UAGB_Helper::get_css_value( $attr['registerInfoFontSize'], $attr['registerInfoFontSizeType'] ),
		'letter-spacing'  => UAGB_Helper::get_css_value( $attr['registerInfoLetterSpacing'], $attr['registerInfoLetterSpacingType'] ),
	),
	' .wp-block-spectra-pro-login-info a'               => array(
		'color' => $attr['linkColor'],
	),
	' .wp-block-spectra-pro-login__logged-in-message a' => array(
		'color' => $attr['linkColor'],
	),
	' .wp-block-spectra-pro-login-info:hover'           => array(
		'color' => $attr['registerInfoHoverColor'],
	),
	' .wp-block-spectra-pro-login__logged-in-message a:hover' => array(
		'color' => $attr['linkHColor'],
	),
);

// If hover blur or hover color are set, show the hover shadow.
if ( ( ( '' !== $attr['boxShadowBlurHover'] ) && ( null !== $attr['boxShadowBlurHover'] ) ) || '' !== $attr['boxShadowColorHover'] ) {

	$selectors['.wp-block-spectra-pro-login:hover']['box-shadow'] = UAGB_Helper::get_css_value( $attr['boxShadowHOffsetHover'], 'px' ) .
																' ' .
																UAGB_Helper::get_css_value( $attr['boxShadowVOffsetHover'], 'px' ) .
																' ' .
																UAGB_Helper::get_css_value( $attr['boxShadowBlurHover'], 'px' ) .
																' ' .
																UAGB_Helper::get_css_value( $attr['boxShadowSpreadHover'], 'px' ) .
																' ' .
																$attr['boxShadowColorHover'] .
																' ' .
																$box_shadow_position_css_hover;

}

// tablet.
$t_selectors['.wp-block-spectra-pro-login'] = array_merge(
	array(
		'width'          => UAGB_Helper::get_css_value( $attr['formWidthTablet'], $attr['formWidthTypeTablet'] ),
		'padding-top'    => UAGB_Helper::get_css_value( $attr['formTopPaddingTablet'], $attr['formPaddingUnitTablet'] ),
		'padding-right'  => UAGB_Helper::get_css_value( $attr['formRightPaddingTablet'], $attr['formPaddingUnitTablet'] ),
		'padding-bottom' => UAGB_Helper::get_css_value( $attr['formBottomPaddingTablet'], $attr['formPaddingUnitTablet'] ),
		'padding-left'   => UAGB_Helper::get_css_value( $attr['formLeftPaddingTablet'], $attr['formPaddingUnitTablet'] ),
	),
	$container_bg_css_tablet,
	$form_border_css_tablet
);

$t_selectors[' .wp-block-spectra-pro-login-info']                                     = array(
	'font-size'      => UAGB_Helper::get_css_value( $attr['registerInfoFontSizeTablet'], $attr['registerInfoFontSizeType'] ),
	'letter-spacing' => UAGB_Helper::get_css_value( $attr['registerInfoLetterSpacingTablet'], $attr['registerInfoLetterSpacingType'] ),
);
$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__user-login label'] = array(
	'font-size'      => UAGB_Helper::get_css_value(
		$attr['labelFontSizeTablet'],
		$attr['labelFontSizeType']
	),
	'letter-spacing' => UAGB_Helper::get_css_value( $attr['labelLetterSpacingTablet'], $attr['labelLetterSpacingType'] ),
	'line-height'    => UAGB_Helper::get_css_value(
		$attr['labelLineHeightTablet'],
		$attr['labelLineHeightType']
	),
	'margin-top'     => UAGB_Helper::get_css_value(
		$attr['labelTopMarginTablet'],
		$attr['labelMarginUnitTablet']
	),
	'margin-right'   => UAGB_Helper::get_css_value(
		$attr['labelRightMarginTablet'],
		$attr['labelMarginUnitTablet']
	),
	'margin-bottom'  => UAGB_Helper::get_css_value(
		$attr['labelBottomMarginTablet'],
		$attr['labelMarginUnitTablet']
	),
	'margin-left'    => UAGB_Helper::get_css_value(
		$attr['labelLeftMarginTablet'],
		$attr['labelMarginUnitTablet']
	),
);

$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass label'] = array(
	'font-size'      => UAGB_Helper::get_css_value(
		$attr['labelFontSizeTablet'],
		$attr['labelFontSizeType']
	),
	'letter-spacing' => UAGB_Helper::get_css_value( $attr['labelLetterSpacingTablet'], $attr['labelLetterSpacingType'] ),
	'line-height'    => UAGB_Helper::get_css_value(
		$attr['labelLineHeightTablet'],
		$attr['labelLineHeightType']
	),
	'margin-top'     => UAGB_Helper::get_css_value(
		$attr['labelTopMarginTablet'],
		$attr['labelMarginUnitTablet']
	),
	'margin-right'   => UAGB_Helper::get_css_value(
		$attr['labelRightMarginTablet'],
		$attr['labelMarginUnitTablet']
	),
	'margin-bottom'  => UAGB_Helper::get_css_value(
		$attr['labelBottomMarginTablet'],
		$attr['labelMarginUnitTablet']
	),
	'margin-left'    => UAGB_Helper::get_css_value(
		$attr['labelLeftMarginTablet'],
		$attr['labelMarginUnitTablet']
	),
);


$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__user-login input'] = $form_input_style_tablet;
$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass input']  = $form_input_style_tablet;

$t_selectors[ $username_icon_input_selector ] = array(
	'padding-left' => UAGB_Helper::get_css_value( $attr['paddingFieldLeftTablet'], $attr['paddingFieldUnitTablet'] ),
);

$t_selectors[ $password_icon_input_selector ] = array(
	'padding-left' => UAGB_Helper::get_css_value( $attr['paddingFieldLeftTablet'], $attr['paddingFieldUnitTablet'] ),
);

// Field Icon - Username.
$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form-username-wrap--have-icon > svg'] = array_merge(
	$field_icon_css_tablet
);

// Field Icon - Password.
$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass .spectra-pro-login-form-pass-wrap--have-icon > svg'] = array_merge(
	$field_icon_css_tablet
);

$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass button'] = array(
	'margin-right' => array_key_exists( 'border-right-width', $fields_border_css_tablet ) && ( ! $is_rtl ) ? $fields_border_css_tablet['border-right-width'] : '',
	'margin-left'  => array_key_exists( 'border-left-width', $fields_border_css_tablet ) && $is_rtl ? $fields_border_css_tablet['border-left-width'] : '',
);

$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__forgetmenot .spectra-pro-login-form-rememberme'] = array(
	'margin-top'    => UAGB_Helper::get_css_value(
		$attr['labelTopMarginTablet'],
		$attr['labelMarginUnitTablet']
	),
	// Right margin not required.
	'margin-bottom' => UAGB_Helper::get_css_value(
		$attr['labelBottomMarginTablet'],
		$attr['labelMarginUnitTablet']
	),
	'margin-left'   => UAGB_Helper::get_css_value(
		$attr['labelLeftMarginTablet'],
		$attr['labelMarginUnitTablet']
	),
);

$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__forgetmenot .spectra-pro-login-form-rememberme label'] = array(
	'font-size'      => UAGB_Helper::get_css_value(
		$attr['labelFontSizeTablet'],
		$attr['labelFontSizeType']
	),
	'letter-spacing' => UAGB_Helper::get_css_value( $attr['labelLetterSpacingTablet'], $attr['labelLetterSpacingType'] ),
	'line-height'    => UAGB_Helper::get_css_value(
		$attr['labelLineHeightTablet'],
		$attr['labelLineHeightType']
	),
);

$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__forgetmenot .spectra-pro-login-form-forgot-password'] = array(
	'margin-top'    => UAGB_Helper::get_css_value(
		$attr['labelTopMarginTablet'],
		$attr['labelMarginUnitTablet']
	),
	'margin-right'  => UAGB_Helper::get_css_value(
		$attr['labelRightMarginTablet'],
		$attr['labelMarginUnitTablet']
	),
	'margin-bottom' => UAGB_Helper::get_css_value(
		$attr['labelBottomMarginTablet'],
		$attr['labelMarginUnitTablet']
	),
	// Margin left not required.
);

$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__forgetmenot .spectra-pro-login-form-forgot-password a'] = array(
	'font-size'      => UAGB_Helper::get_css_value(
		$attr['labelFontSizeTablet'],
		$attr['labelFontSizeType']
	),
	'letter-spacing' => UAGB_Helper::get_css_value( $attr['labelLetterSpacingTablet'], $attr['labelLetterSpacingType'] ),
	'line-height'    => UAGB_Helper::get_css_value(
		$attr['labelLineHeightTablet'],
		$attr['labelLineHeightType']
	),
);
$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__submit'] = array(
	'justify-content' => $attr['alignLoginBtnTablet'],
);

$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form-submit-button'] = array_merge(
	array(
		'font-size'      => UAGB_Helper::get_css_value(
			$attr['loginFontSizeTablet'],
			$attr['loginFontSizeType']
		),
		'letter-spacing' => UAGB_Helper::get_css_value( $attr['loginLetterSpacingTablet'], $attr['loginLetterSpacingType'] ),
		'line-height'    => UAGB_Helper::get_css_value(
			$attr['loginLineHeightTablet'],
			$attr['loginLineHeightType']
		),
		'padding-top'    => UAGB_Helper::get_css_value(
			$attr['loginTopPaddingTablet'],
			$attr['loginPaddingUnitTablet']
		),
		'padding-right'  => UAGB_Helper::get_css_value(
			$attr['loginRightPaddingTablet'],
			$attr['loginPaddingUnitTablet']
		),
		'padding-bottom' => UAGB_Helper::get_css_value(
			$attr['loginBottomPaddingTablet'],
			$attr['loginPaddingUnitTablet']
		),
		'padding-left'   => UAGB_Helper::get_css_value(
			$attr['loginLeftPaddingTablet'],
			$attr['loginPaddingUnitTablet']
		),
		'column-gap'     => UAGB_Helper::get_css_value( $attr['ctaIconSpaceTablet'], $attr['ctaIconSpaceType'] ),
	),
	$full_width_login_btn_tablet,
	$login_border_css_tablet
);


// mobile.
$m_selectors['.wp-block-spectra-pro-login'] = array_merge(
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
	$container_bg_css_mobile,
	$form_border_css_mobile
);

$t_selectors[' .wp-block-spectra-pro-login-info']                                     = array(
	'font-size'      => UAGB_Helper::get_css_value( $attr['registerInfoFontSizeMobile'], $attr['registerInfoFontSizeType'] ),
	'letter-spacing' => UAGB_Helper::get_css_value( $attr['registerInfoLetterSpacingMobile'], $attr['registerInfoLetterSpacingType'] ),
);
$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__user-login label'] = array(
	'font-size'      => UAGB_Helper::get_css_value(
		$attr['labelFontSizeMobile'],
		$attr['labelFontSizeType']
	),
	'letter-spacing' => UAGB_Helper::get_css_value( $attr['labelLetterSpacingMobile'], $attr['labelLetterSpacingType'] ),
	'line-height'    => UAGB_Helper::get_css_value(
		$attr['labelLineHeightMobile'],
		$attr['labelLineHeightType']
	),
	'margin-top'     => UAGB_Helper::get_css_value(
		$attr['labelTopMarginMobile'],
		$attr['labelMarginUnitMobile']
	),
	'margin-right'   => UAGB_Helper::get_css_value(
		$attr['labelRightMarginMobile'],
		$attr['labelMarginUnitMobile']
	),
	'margin-bottom'  => UAGB_Helper::get_css_value(
		$attr['labelBottomMarginMobile'],
		$attr['labelMarginUnitMobile']
	),
	'margin-left'    => UAGB_Helper::get_css_value(
		$attr['labelLeftMarginMobile'],
		$attr['labelMarginUnitMobile']
	),
);

$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass label'] = array(
	'font-size'      => UAGB_Helper::get_css_value(
		$attr['labelFontSizeMobile'],
		$attr['labelFontSizeType']
	),
	'letter-spacing' => UAGB_Helper::get_css_value( $attr['labelLetterSpacingMobile'], $attr['labelLetterSpacingType'] ),
	'line-height'    => UAGB_Helper::get_css_value(
		$attr['labelLineHeightMobile'],
		$attr['labelLineHeightType']
	),
	'margin-top'     => UAGB_Helper::get_css_value(
		$attr['labelTopMarginMobile'],
		$attr['labelMarginUnitMobile']
	),
	'margin-right'   => UAGB_Helper::get_css_value(
		$attr['labelRightMarginMobile'],
		$attr['labelMarginUnitMobile']
	),
	'margin-bottom'  => UAGB_Helper::get_css_value(
		$attr['labelBottomMarginMobile'],
		$attr['labelMarginUnitMobile']
	),
	'margin-left'    => UAGB_Helper::get_css_value(
		$attr['labelLeftMarginMobile'],
		$attr['labelMarginUnitMobile']
	),
);

$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__user-login input'] = $form_input_style_mobile;
$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass input']  = $form_input_style_mobile;

$m_selectors[ $username_icon_input_selector ] = array(
	'padding-left' => UAGB_Helper::get_css_value( $attr['paddingFieldLeftMobile'], $attr['paddingFieldUnitmobile'] ),
);

$m_selectors[ $password_icon_input_selector ] = array(
	'padding-left' => UAGB_Helper::get_css_value( $attr['paddingFieldLeftMobile'], $attr['paddingFieldUnitmobile'] ),
);

// Field Icon - Username.
$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form-username-wrap--have-icon > svg'] = array_merge(
	$field_icon_css_mobile
);

// Field Icon - Password.
$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass .spectra-pro-login-form-pass-wrap--have-icon > svg'] = array_merge(
	$field_icon_css_mobile
);

$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass button'] = array(
	'margin-right' => array_key_exists( 'border-right-width', $fields_border_css_mobile ) && ( ! $is_rtl ) ? $fields_border_css_mobile['border-right-width'] : '',
	'margin-left'  => array_key_exists( 'border-left-width', $fields_border_css_mobile ) && $is_rtl ? $fields_border_css_mobile['border-left-width'] : '',
);

$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__forgetmenot .spectra-pro-login-form-rememberme'] = array(
	'margin-top'    => UAGB_Helper::get_css_value(
		$attr['labelTopMarginMobile'],
		$attr['labelMarginUnitMobile']
	),
	// Right margin not required.
	'margin-bottom' => UAGB_Helper::get_css_value(
		$attr['labelBottomMarginMobile'],
		$attr['labelMarginUnitMobile']
	),
	'margin-left'   => UAGB_Helper::get_css_value(
		$attr['labelLeftMarginMobile'],
		$attr['labelMarginUnitMobile']
	),
);

$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__forgetmenot .spectra-pro-login-form-rememberme label'] = array(
	'font-size'      => UAGB_Helper::get_css_value(
		$attr['labelFontSizeMobile'],
		$attr['labelFontSizeType']
	),
	'letter-spacing' => UAGB_Helper::get_css_value( $attr['labelLetterSpacingMobile'], $attr['labelLetterSpacingType'] ),
	'line-height'    => UAGB_Helper::get_css_value(
		$attr['labelLineHeightMobile'],
		$attr['labelLineHeightType']
	),
);

$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__forgetmenot .spectra-pro-login-form-forgot-password'] = array(
	'margin-top'    => UAGB_Helper::get_css_value(
		$attr['labelTopMarginMobile'],
		$attr['labelMarginUnitMobile']
	),
	'margin-right'  => UAGB_Helper::get_css_value(
		$attr['labelRightMarginMobile'],
		$attr['labelMarginUnitMobile']
	),
	'margin-bottom' => UAGB_Helper::get_css_value(
		$attr['labelBottomMarginMobile'],
		$attr['labelMarginUnitMobile']
	),
	// Margin left not required.
);

$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__forgetmenot .spectra-pro-login-form-forgot-password a'] = array(
	'font-size'      => UAGB_Helper::get_css_value(
		$attr['labelFontSizeMobile'],
		$attr['labelFontSizeType']
	),
	'letter-spacing' => UAGB_Helper::get_css_value( $attr['labelLetterSpacingMobile'], $attr['labelLetterSpacingType'] ),
	'line-height'    => UAGB_Helper::get_css_value(
		$attr['labelLineHeightMobile'],
		$attr['labelLineHeightType']
	),
);
$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__submit']       = array(
	'justify-content' => $attr['alignLoginBtnMobile'],
);
$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form-submit-button'] = array_merge(
	array(
		'font-size'      => UAGB_Helper::get_css_value(
			$attr['loginFontSizeMobile'],
			$attr['loginFontSizeType']
		),
		'letter-spacing' => UAGB_Helper::get_css_value( $attr['loginLetterSpacingMobile'], $attr['loginLetterSpacingType'] ),
		'line-height'    => UAGB_Helper::get_css_value(
			$attr['loginLineHeightMobile'],
			$attr['loginLineHeightType']
		),
		'padding-top'    => UAGB_Helper::get_css_value(
			$attr['loginTopPaddingMobile'],
			$attr['loginPaddingUnitMobile']
		),
		'padding-right'  => UAGB_Helper::get_css_value(
			$attr['loginRightPaddingMobile'],
			$attr['loginPaddingUnitMobile']
		),
		'padding-bottom' => UAGB_Helper::get_css_value(
			$attr['loginBottomPaddingMobile'],
			$attr['loginPaddingUnitMobile']
		),
		'padding-left'   => UAGB_Helper::get_css_value(
			$attr['loginLeftPaddingMobile'],
			$attr['loginPaddingUnitMobile']
		),
		'column-gap'     => UAGB_Helper::get_css_value( $attr['ctaIconSpaceMobile'], $attr['ctaIconSpaceType'] ),
	),
	$full_width_login_btn_mobile,
	$login_border_css_mobile
);



if ( 'before' === $attr['ctaIconPosition'] ) {
	$selectors['.wp-block-spectra-pro-login .spectra-pro-login-form-submit-button svg']   = array(
		'width'  => UAGB_Helper::get_css_value(
			$attr['loginFontSize'],
			$attr['loginFontSizeType']
		),
		'height' => UAGB_Helper::get_css_value(
			$attr['loginFontSize'],
			$attr['loginFontSizeType']
		),
		'fill'   => $attr['loginColor'],
	);
	$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form-submit-button svg'] = array(
		'width'  => UAGB_Helper::get_css_value(
			$attr['loginFontSizeTablet'],
			$attr['loginFontSizeType']
		),
		'height' => UAGB_Helper::get_css_value(
			$attr['loginFontSizeTablet'],
			$attr['loginFontSizeType']
		),
	);
	$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form-submit-button svg'] = array(
		'width'  => UAGB_Helper::get_css_value(
			$attr['loginFontSizeMobile'],
			$attr['loginFontSizeType']
		),
		'height' => UAGB_Helper::get_css_value(
			$attr['loginFontSizeMobile'],
			$attr['loginFontSizeType']
		),
	);
}//end if
if ( 'after' === $attr['ctaIconPosition'] ) {
	$selectors['.wp-block-spectra-pro-login .spectra-pro-login-form-submit-button svg']   = array(
		'width'  => UAGB_Helper::get_css_value(
			$attr['loginFontSize'],
			$attr['loginFontSizeType']
		),
		'height' => UAGB_Helper::get_css_value(
			$attr['loginFontSize'],
			$attr['loginFontSizeType']
		),
		'fill'   => $attr['loginColor'],
	);
	$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form-submit-button svg'] = array(
		'width'  => UAGB_Helper::get_css_value(
			$attr['loginFontSizeTablet'],
			$attr['loginFontSizeType']
		),
		'height' => UAGB_Helper::get_css_value(
			$attr['loginFontSizeTablet'],
			$attr['loginFontSizeType']
		),
	);
	$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form-submit-button svg'] = array(
		'width'  => UAGB_Helper::get_css_value(
			$attr['loginFontSizeMobile'],
			$attr['loginFontSizeType']
		),
		'height' => UAGB_Helper::get_css_value(
			$attr['loginFontSizeMobile'],
			$attr['loginFontSizeType']
		),
	);
}//end if

// Grouping together Row Gap Selectors - Tablet.
$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__user-login']  = array(
	'margin-bottom' => UAGB_Helper::get_css_value( $attr['formRowsGapSpaceTablet'], $attr['formRowsGapSpaceUnit'] ),
);
$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__recaptcha']   = array(
	'margin-bottom' => UAGB_Helper::get_css_value( $attr['formRowsGapSpaceTablet'], $attr['formRowsGapSpaceUnit'] ),
);
$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass']   = array(
	'margin-bottom' => UAGB_Helper::get_css_value( $attr['formRowsGapSpaceTablet'], $attr['formRowsGapSpaceUnit'] ),
);
$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__forgetmenot'] = array(
	'margin-bottom' => UAGB_Helper::get_css_value( $attr['formRowsGapSpaceTablet'], $attr['formRowsGapSpaceUnit'] ),
);
$t_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__submit']      = array(
	'margin-bottom' => UAGB_Helper::get_css_value( $attr['formRowsGapSpaceTablet'], $attr['formRowsGapSpaceUnit'] ),
);

// Grouping together Row Gap Selectors - Mobile.
$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__user-login']  = array(
	'margin-bottom' => UAGB_Helper::get_css_value( $attr['formRowsGapSpaceMobile'], $attr['formRowsGapSpaceUnit'] ),
);
$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__recaptcha']   = array(
	'margin-bottom' => UAGB_Helper::get_css_value( $attr['formRowsGapSpaceMobile'], $attr['formRowsGapSpaceUnit'] ),
);
$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__user-pass']   = array(
	'margin-bottom' => UAGB_Helper::get_css_value( $attr['formRowsGapSpaceMobile'], $attr['formRowsGapSpaceUnit'] ),
);
$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__forgetmenot'] = array(
	'margin-bottom' => UAGB_Helper::get_css_value( $attr['formRowsGapSpaceMobile'], $attr['formRowsGapSpaceUnit'] ),
);
$m_selectors['.wp-block-spectra-pro-login .spectra-pro-login-form__submit']      = array(
	'margin-bottom' => UAGB_Helper::get_css_value( $attr['formRowsGapSpaceMobile'], $attr['formRowsGapSpaceUnit'] ),
);


$combined_selectors = array(
	'desktop' => $selectors,
	'tablet'  => $t_selectors,
	'mobile'  => $m_selectors,
);

$base_selector = '.uagb-block-';

return UAGB_Helper::generate_all_css( $combined_selectors, $base_selector . $id );
