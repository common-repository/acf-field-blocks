<?php
/**
 * Loader.
 *
 * @package ACFFieldBlocks
 */

namespace ACFFieldBlocks\Blocks;

use ACFFieldBlocks\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Blocks
 */
class ACF_Button {

	public function render( $attr, $block_content, $block ) {

		// field key and source must be specified.
		if ( empty( $attr['fieldKey'] ) || empty( $attr['fieldSource'] ) ) {
			return '';
		}

		// load field.
		$field = Helper::load_field( $attr, $block );

		// throw if the field is not found on ACF.
		if ( false === $field ) {
			return '';
		}

		if ( 'email' === $field['type'] ) {
			$url = 'mailto:' . $field['value'];
		} elseif ( 'image' === $field['type'] || 'file' === $field['type'] ) {
			$url = wp_get_attachment_url( $field['value'] );
		} elseif ( 'link' === $field['type'] && isset( $field['value']['url'] ) ) {
			$url = $field['value']['url'];
		} elseif ( 'page_link' === $field['type'] ) {
			$url = is_numeric( $field['value'] ) ? get_permalink( $field['value'] ) : $field['value'];
		} else {
			$url = $field['value'];
		}
		
		$text = 'email' === $field['type'] ? $field['value'] : $url;
		if ( 'custom' === $attr['textSource'] && isset( $attr['customText'] ) && ! empty( $attr['customText'] ) ) {
			$text = $attr['customText'];
		} elseif ( 'field' === $attr['textSource'] && ! empty( $attr['textFieldKey'] ) ) {
			$text_field = get_field_object( $attr['textFieldKey'], $field['source'], false, true );
			if ( ! empty( $text_field['value'] ) ) {
				$text = $text_field['value'];
			}
		}

		$block_attributes = get_block_wrapper_attributes([
			'class' => Helper::build_class( [
				[ "has-custom-width wp-block-acf-field-blocks-acf-button__width-" . ( $attr['width'] ?? '' ), ( isset( $attr['width'] ) && ! empty( $attr['width'] ) ) ],
				[ "has-text-align-" . $attr['buttonAlign'], isset( $attr['buttonAlign'] ) && ! empty( $attr['buttonAlign'] ) ]
			] )
		]);

		$color      = Helper::get_color_class_and_style( $attr );
		$border     = Helper::get_border_class_and_style( $attr );
		$spacing    = Helper::get_spacing_class_and_style( $attr );
		// $shadow     = Helper::get_shadow_class_and_style( $attr );
		$typography = Helper::get_typography_class_and_style( $attr );

		$button_class = Helper::build_class( [
			'wp-block-acf-field-blocks-acf-button__link',
			'wp-element-button',
			[ "has-text-align-" . ( $attr['textAlign'] ?? '' ), ( isset( $attr['textAlign'] ) && ! empty( $attr['textAlign'] ) ) ],
			$color['class'] ?? '',
			$border['class'] ?? '',
			$spacing['class'] ?? '',
			// $shadow['class'] ?? '',
			$typography['class'] ?? ''
		] );

		$button_styles = [
			$color['style'] ?? '',
			$border['style'] ?? '',
			$spacing['style'] ?? '',
			// $shadow['style'] ?? '',
			$typography['style'] ?? ''
		];
		$button_style = implode( '', $button_styles );

		$rel = '';
		if ( isset( $attr['linkTarget'] ) && '_blank' === $attr['linkTarget'] ) {
			$rel .= "noreferrer noopener ";
		}
		if ( isset( $attr['nofollow'] ) && $attr['nofollow'] ) {
			$rel .= "nofollow ";
		}

		$button_attributes = Helper::build_attrs([
			'href'     => esc_url( $url ),
			'class'    => esc_attr( $button_class ),
			'style'    => esc_attr( $button_style ),
			'target'   => esc_attr( $attr['linkTarget'] ),
			'rel'      => esc_attr( $rel ),
			'download' => isset( $attr['asDownload'] ) && $attr['asDownload']
		]);

		ob_start();
		?>
			<div <?php echo wp_kses_post( $block_attributes ); ?>>
				<a <?php echo wp_kses_post( $button_attributes ); ?>><?php echo wp_kses_post( $text ); ?></a>
			</div>
		<?php
		return ob_get_clean();
	}

}