<?php
/**
 * Loader.
 *
 * @package ACFFieldBlocks
 */

namespace ACFFieldBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Main
 */
class Helper {

	/**
	 * Build classname from array
	 *
	 * @since  1.0.0
	 * 
	 * @param  array  $classes Class names in array.
	 * @return string          Class string.
	 */
	public static function build_class( $classes, $return_array = false ) {
		if ( ! is_array( $classes ) || empty( $classes ) ) {
			return '';
		}
		$_classes = [];
		foreach ( $classes as $class ) {
			if ( is_array( $class ) ) {
				if ( $class[1] ) {
					$_classes[] = $class[0];
				}
			} else {
				if ( ! empty( $class ) ) {
					$_classes[] = $class;
				}
			}
		}
		if ( $return_array ) {
			return $_classes;
		}
		return implode( ' ', $_classes );
	}

	/**
	 * Build inline style from array
	 *
	 * @since  1.0.0
	 * 
	 * @param  array  $styles Styles in array.
	 * @return string         Inline style.
	 */
	public static function build_style( $styles ) {
		if ( ! is_array( $styles ) || empty( $styles ) ) {
			return '';
		}
		$_styles = [];
		foreach ( $styles as $key => $value ) {
			if ( '' !== $value && false !== $value && ! is_null( $value ) ) {
				$_styles[] = $key . ': ' . $value . ';';
			}
		}
		return implode( '', $_styles );
	}

	/**
	 * Build html attributes from array
	 *
	 * @since  1.0.0
	 * 
	 * @param  array  $styles HTML Attributes.
	 * @return string         Inline attributes.
	 */
	public static function build_attrs( $attrs ) {
		$normalized_attrs = [];
		foreach ( $attrs as $key => $value ) {
			if ( true === $value ) {
				$normalized_attrs[] = $key;
			} elseif ( ! is_null( $value ) && false !== $value && "" !== $value ) {
				$normalized_attrs[] = $key . '="' . esc_attr( trim( $value ) ) . '"';
			}
		}

		return implode( ' ', $normalized_attrs );
	}

	/**
	 * Load field based on selected source an key
	 * 
	 * @param  array  $attr  Block attributes.
	 * @param  object $block Block
	 * @return array         ACF field object.
	 */
	public static function load_field( $attr, $block ) {
		$source = false;
		$parent = false;

		if ( 'option' === $attr['fieldSource'] ) {
			$source = 'option';
		} elseif ( 'current_term' === $attr['fieldSource'] ) {
			if ( isset( $block->context['acf-field-blocks/term'] ) ) {
				$source = $block->context['acf-field-blocks/term']->taxonomy . '_' . $block->context['acf-field-blocks/term']->term_id;
			} elseif ( is_category() ) {
				$source = 'category_' . get_queried_object()->term_id;
			} elseif ( is_tag() ) {
				$source = 'post_tag_' . get_queried_object()->term_id;
			} elseif ( is_tax() ) {
				$source = get_queried_object()->taxonomy . '_' . get_queried_object()->term_id;
			}
		} elseif ( 'current_user' === $attr['fieldSource'] ) {
			if ( isset( $block->context['acf-field-blocks/user'] ) ) {
				$source = 'user_' . $block->context['acf-field-blocks/user']->ID;
			} elseif ( is_author() ) {
				$source = 'user_' . get_query_var( 'author' );
			}
		} elseif ( 0 === stripos( $attr['fieldSource'], 'repeater|' ) ) {
			$ancestors = explode( "/", str_replace( 'repeater|', '', $attr['fieldSource'] ) );
			$parent    = $ancestors[ count( $ancestors ) - 1 ];
			if ( isset( $block->context['acf-field-blocks/repeaters'] ) && isset( $block->context['acf-field-blocks/repeaters'][ $ancestors[0] ] ) ) {
				$source = $block->context['acf-field-blocks/repeaters'][ $ancestors[0] ]['source'];
			}
		}

		$field = get_field_object( $attr['fieldKey'], $source, false, true );

		// field is not found on ACF.
		if ( false === $field ) {
			return false;
		}

		if ( $parent && isset( $block->context['acf-field-blocks/repeaters'] ) && isset( $block->context['acf-field-blocks/repeaters'][ $parent ] ) && isset( $block->context['acf-field-blocks/repeaters'][ $parent ]['value'][ $attr['fieldKey'] ] ) ) {
			$field['value'] = $block->context['acf-field-blocks/repeaters'][ $parent ]['value'][ $attr['fieldKey'] ];
		}

		$field['source'] = $source;

		return $field;
	}

	/**
	 * Generate block's border class and styles based on block attributes.
	 *
	 * @since  1.0.0
	 * 
	 * @param  array  $block_attributes Block Attributes.
	 * @return array                    Array of block class and style.
	 */
	public static function get_border_class_and_style( $block_attributes ) {
		$border_block_styles = array();

		// Border radius.
		if ( isset( $block_attributes['style']['border']['radius'] ) ) {
			$border_radius = $block_attributes['style']['border']['radius'];

			if ( is_numeric( $border_radius ) ) {
				$border_radius .= 'px';
			}

			$border_block_styles['radius'] = $border_radius;
		}

		// Border style.
		if ( isset( $block_attributes['style']['border']['style'] ) ) {
			$border_block_styles['style'] = $block_attributes['style']['border']['style'];
		}

		// Border width.
		if ( isset( $block_attributes['style']['border']['width'] ) ) {
			$border_width = $block_attributes['style']['border']['width'];

			// This check handles original unitless implementation.
			if ( is_numeric( $border_width ) ) {
				$border_width .= 'px';
			}

			$border_block_styles['width'] = $border_width;
		}

		// Border color.
		$preset_border_color          = array_key_exists( 'borderColor', $block_attributes ) ? "var:preset|color|{$block_attributes['borderColor']}" : null;
		$custom_border_color          = _wp_array_get( $block_attributes, array( 'style', 'border', 'color' ), null );
		$border_block_styles['color'] = $preset_border_color ? $preset_border_color : $custom_border_color;

		// Generates styles for individual border sides.
		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			$border                       = _wp_array_get( $block_attributes, array( 'style', 'border', $side ), null );
			$border_side_values           = array(
				'width' => isset( $border['width'] ) ? $border['width'] : null,
				'color' => isset( $border['color'] ) ? $border['color'] : null,
				'style' => isset( $border['style'] ) ? $border['style'] : null,
			);
			$border_block_styles[ $side ] = $border_side_values;
		}

		// Collect classes and styles.
		$attributes = array();
		$styles     = wp_style_engine_get_styles( array( 'border' => $border_block_styles ) );

		if ( ! empty( $styles['classnames'] ) ) {
			$attributes['class'] = $styles['classnames'];
		}

		if ( ! empty( $styles['css'] ) ) {
			$attributes['style'] = $styles['css'];
		}

		return $attributes;
	}

	/**
	 * Generate block's color class and styles based on block attributes.
	 *
	 * @since  1.0.0
	 * 
	 * @param  array  $block_attributes Block Attributes.
	 * @return array                    Array of block class and style.
	 */
	public static function get_color_class_and_style( $block_attributes ) {
		$color_block_styles = array();

		// Text colors.
		$preset_text_color          = array_key_exists( 'textColor', $block_attributes ) ? "var:preset|color|{$block_attributes['textColor']}" : null;
		$custom_text_color          = $block_attributes['style']['color']['text'] ?? null;
		$color_block_styles['text'] = $preset_text_color ? $preset_text_color : $custom_text_color;

		// Background colors.
		$preset_background_color          = array_key_exists( 'backgroundColor', $block_attributes ) ? "var:preset|color|{$block_attributes['backgroundColor']}" : null;
		$custom_background_color          = $block_attributes['style']['color']['background'] ?? null;
		$color_block_styles['background'] = $preset_background_color ? $preset_background_color : $custom_background_color;

		// Gradients.
		$preset_gradient_color          = array_key_exists( 'gradient', $block_attributes ) ? "var:preset|gradient|{$block_attributes['gradient']}" : null;
		$custom_gradient_color          = $block_attributes['style']['color']['gradient'] ?? null;
		$color_block_styles['gradient'] = $preset_gradient_color ? $preset_gradient_color : $custom_gradient_color;

		$attributes = array();
		$styles     = wp_style_engine_get_styles( array( 'color' => $color_block_styles ), array( 'convert_vars_to_classnames' => true ) );

		if ( ! empty( $styles['classnames'] ) ) {
			$attributes['class'] = $styles['classnames'];
		}

		if ( ! empty( $styles['css'] ) ) {
			$attributes['style'] = $styles['css'];
		}

		return $attributes;
	}

	/**
	 * Generate block's spacing class and styles based on block attributes.
	 *
	 * @since  1.0.0
	 * 
	 * @param  array  $block_attributes Block Attributes.
	 * @return array                    Array of block class and style.
	 */
	public static function get_spacing_class_and_style( $block_attributes ) {
		$attributes          = array();
		$block_styles        = isset( $block_attributes['style'] ) ? $block_attributes['style'] : null;

		if ( ! $block_styles ) {
			return $attributes;
		}

		$spacing_block_styles = array(
			'padding' => null,
			'margin'  => null,
		);
		
		$spacing_block_styles['padding'] = $block_styles['spacing']['padding'] ?? null;
		$spacing_block_styles['margin']  = $block_styles['spacing']['margin'] ?? null;
		$styles = wp_style_engine_get_styles( array( 'spacing' => $spacing_block_styles ) );

		if ( ! empty( $styles['css'] ) ) {
			$attributes['style'] = $styles['css'];
		}

		return $attributes;
	}

	/**
	 * Generate block's shadow class and styles based on block attributes.
	 *
	 * @since  1.0.0
	 * 
	 * @param  array  $block_attributes Block Attributes.
	 * @return array                    Array of block class and style.
	 */
	public static function get_shadow_class_and_style( $block_attributes ) {
		$shadow_block_styles = array();

		$custom_shadow                 = $block_attributes['style']['shadow'] ?? null;
		$shadow_block_styles['shadow'] = $custom_shadow;

		$attributes = array();
		$styles     = wp_style_engine_get_styles( $shadow_block_styles );

		if ( ! empty( $styles['css'] ) ) {
			$attributes['style'] = $styles['css'];
		}

		return $attributes;
	}

	/**
	 * Generate block's typography class and styles based on block attributes.
	 *
	 * @since  1.0.0
	 * 
	 * @param  array  $block_attributes Block Attributes.
	 * @return array                    Array of block class and style.
	 */
	public static function get_typography_class_and_style( $block_attributes ) {
		$typography_block_styles = array();

		// font-size.
		$preset_font_size = array_key_exists( 'fontSize', $block_attributes ) ? "var:preset|font-size|{$block_attributes['fontSize']}" : null;
		$custom_font_size = isset( $block_attributes['style']['typography']['fontSize'] ) ? $block_attributes['style']['typography']['fontSize'] : null;
		$typography_block_styles['fontSize'] = $preset_font_size ? $preset_font_size : wp_get_typography_font_size_value(
			array(
				'size' => $custom_font_size,
			)
		);

		// font-family.
		$preset_font_family = array_key_exists( 'fontFamily', $block_attributes ) ? "var:preset|font-family|{$block_attributes['fontFamily']}" : null;
		$custom_font_family = isset( $block_attributes['style']['typography']['fontFamily'] ) ? wp_typography_get_preset_inline_style_value( $block_attributes['style']['typography']['fontFamily'], 'font-family' ) : null;
		$typography_block_styles['fontFamily'] = $preset_font_family ? $preset_font_family : $custom_font_family;

		// font-style.
		if ( isset( $block_attributes['style']['typography']['fontStyle'] ) ) {
			$typography_block_styles['fontStyle'] = wp_typography_get_preset_inline_style_value(
				$block_attributes['style']['typography']['fontStyle'],
				'font-style'
			);
		}

		// font-weight.
		if ( isset( $block_attributes['style']['typography']['fontWeight'] ) ) {
			$typography_block_styles['fontWeight'] = wp_typography_get_preset_inline_style_value(
				$block_attributes['style']['typography']['fontWeight'],
				'font-weight'
			);
		}

		// line-height.
		$typography_block_styles['lineHeight'] = isset( $block_attributes['style']['typography']['lineHeight'] ) ? $block_attributes['style']['typography']['lineHeight'] : null;

		// text-columns.
		if ( isset( $block_attributes['style']['typography']['textColumns'] ) ) {
			$typography_block_styles['textColumns'] = isset( $block_attributes['style']['typography']['textColumns'] ) ? $block_attributes['style']['typography']['textColumns'] : null;
		}

		// text-decoration.
		if ( isset( $block_attributes['style']['typography']['textDecoration'] ) ) {
			$typography_block_styles['textDecoration'] = wp_typography_get_preset_inline_style_value(
				$block_attributes['style']['typography']['textDecoration'],
				'text-decoration'
			);
		}

		// text-transform.
		if ( isset( $block_attributes['style']['typography']['textTransform'] ) ) {
			$typography_block_styles['textTransform'] = wp_typography_get_preset_inline_style_value(
				$block_attributes['style']['typography']['textTransform'],
				'text-transform'
			);
		}

		// letter-spacing.
		if ( isset( $block_attributes['style']['typography']['letterSpacing'] ) ) {
			$typography_block_styles['letterSpacing'] = wp_typography_get_preset_inline_style_value(
				$block_attributes['style']['typography']['letterSpacing'],
				'letter-spacing'
			);
		}

		// writing-mode.
		if ( isset( $block_attributes['style']['typography']['writingMode'] ) ) {
			$typography_block_styles['writingMode'] = isset( $block_attributes['style']['typography']['writingMode'] ) ? $block_attributes['style']['typography']['writingMode'] : null;
		}

		$attributes = array();
		$styles     = wp_style_engine_get_styles(
			array( 'typography' => $typography_block_styles ),
			array( 'convert_vars_to_classnames' => true )
		);

		if ( ! empty( $styles['classnames'] ) ) {
			$attributes['class'] = $styles['classnames'];
		}

		if ( ! empty( $styles['css'] ) ) {
			$attributes['style'] = $styles['css'];
		}

		return $attributes;
	}

}