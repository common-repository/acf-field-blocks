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
class ACF_Image {

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

		// throw if value is empty.
		if ( '' === $field['value'] || is_null( $field['value'] ) ) {
			if ( isset( $attr['defaultImage'] ) && ! empty( $attr['defaultImage'] ) ) {
				$image_id = intval( $attr['defaultImage'] );
			} else {
				return '';
			}
		} else {
			$image_id = intval( $field['value'] );
		}

		$size_slug      = isset( $attr['sizeSlug'] ) ? $attr['sizeSlug'] : 'post-thumbnail';
		$img_attr       = Helper::get_border_class_and_style( $attr );
		$overlay_markup = $this->get_overlay_element_markup( $attr );

		$extra_styles = '';

		// Aspect ratio with a height set needs to override the default width/height.
		if ( ! empty( $attr['aspectRatio'] ) ) {
			$extra_styles .= 'width:100%;height:100%;';
		} elseif ( ! empty( $attr['height'] ) ) {
			$extra_styles .= "height:{$attr['height']};";
		}

		if ( ! empty( $attr['scale'] ) ) {
			$extra_styles .= "object-fit:{$attr['scale']};";
		}
		if ( ! empty( $attr['style']['shadow'] ) ) {
			$shadow_styles = wp_style_engine_get_styles( array( 'shadow' => $attr['style']['shadow'] ) );

			if ( ! empty( $shadow_styles['css'] ) ) {
				$extra_styles .= $shadow_styles['css'];
			}
		}

		if ( ! empty( $extra_styles ) ) {
			$img_attr['style'] = empty( $img_attr['style'] ) ? $extra_styles : $img_attr['style'] . $extra_styles;
		}

		$image = wp_get_attachment_image( $image_id, $size_slug, false, $img_attr );

		if ( empty( $image ) ) {
			return '';
		}

		$image = $image . $overlay_markup;

		$aspect_ratio = ! empty( $attr['aspectRatio'] )
			? esc_attr( safecss_filter_attr( 'aspect-ratio:' . $attr['aspectRatio'] ) ) . ';'
			: '';
		$width        = ! empty( $attr['width'] )
			? esc_attr( safecss_filter_attr( 'width:' . $attr['width'] ) ) . ';'
			: '';
		$height       = ! empty( $attr['height'] )
			? esc_attr( safecss_filter_attr( 'height:' . $attr['height'] ) ) . ';'
			: '';
		if ( ! $height && ! $width && ! $aspect_ratio ) {
			$wrapper_attributes = get_block_wrapper_attributes();
		} else {
			$wrapper_attributes = get_block_wrapper_attributes( array( 'style' => $aspect_ratio . $width . $height ) );
		}
		return "<figure " . wp_kses_post( $wrapper_attributes ) . ">" . wp_kses_post( $image ) . "</figure>";

	}

	public function get_overlay_element_markup( $attributes ) {
		$has_dim_background  = isset( $attributes['dimRatio'] ) && $attributes['dimRatio'];
		$has_gradient        = isset( $attributes['gradient'] ) && $attributes['gradient'];
		$has_custom_gradient = isset( $attributes['customGradient'] ) && $attributes['customGradient'];
		$has_solid_overlay   = isset( $attributes['overlayColor'] ) && $attributes['overlayColor'];
		$has_custom_overlay  = isset( $attributes['customOverlayColor'] ) && $attributes['customOverlayColor'];
		$class_names         = array( 'acf-field-blocks-image__overlay' );
		$styles              = array();

		if ( ! $has_dim_background ) {
			return '';
		}

		// Apply border classes and styles.
		$border_attributes = Helper::get_border_class_and_style( $attributes );

		if ( ! empty( $border_attributes['class'] ) ) {
			$class_names[] = $border_attributes['class'];
		}

		if ( ! empty( $border_attributes['style'] ) ) {
			$styles[] = $border_attributes['style'];
		}

		// Apply overlay and gradient classes.
		if ( $has_dim_background ) {
			$class_names[] = 'has-background-dim';
			$class_names[] = "has-background-dim-{$attributes['dimRatio']}";
		}

		if ( $has_solid_overlay ) {
			$class_names[] = "has-{$attributes['overlayColor']}-background-color";
		}

		if ( $has_gradient || $has_custom_gradient ) {
			$class_names[] = 'has-background-gradient';
		}

		if ( $has_gradient ) {
			$class_names[] = "has-{$attributes['gradient']}-gradient-background";
		}

		// Apply background styles.
		if ( $has_custom_gradient ) {
			$styles[] = sprintf( 'background-image: %s;', $attributes['customGradient'] );
		}

		if ( $has_custom_overlay ) {
			$styles[] = sprintf( 'background-color: %s;', $attributes['customOverlayColor'] );
		}

		return sprintf(
			'<span class="%s" style="%s" aria-hidden="true"></span>',
			esc_attr( implode( ' ', $class_names ) ),
			esc_attr( safecss_filter_attr( implode( ' ', $styles ) ) )
		);
	}

}