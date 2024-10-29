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
class ACF_Text {

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

		$wrapper_classes = Helper::build_class([
			'field-' . $field['name'],
			( isset( $attr['textAlign'] ) && ! empty( $attr['textAlign'] ) ? 'has-text-align-' . $attr['textAlign'] : '' )
		]);

		$wrapper_attributes = get_block_wrapper_attributes([
			'class' => $wrapper_classes
		]);
		$tag         = $attr['tag'] ?? 'p';
		$opening_tag = "<{$tag} {$wrapper_attributes}>";
		$closing_tag = "</{$tag}>";

		// throw if value is empty.
		if ( ( '' === $field['value'] || is_null( $field['value'] ) || ( is_array( $field['value'] ) && empty( $field['value'] ) ) ) && 'true_false' !== $field['type'] ) {
			if ( isset( $attr['showMessageIfEmpty'] ) && boolval( $attr['showMessageIfEmpty'] ) && isset( $attr['emptyMessage'] ) && ! empty( $attr['emptyMessage'] ) ) {
				return $opening_tag . '<span class="empty">' . $attr['emptyMessage'] . '</span>' . $closing_tag;
			} else {
				return '';
			}
		}

		$value   = '';
		$is_link = isset( $attr['linkToObject'] ) && boolval( $attr['linkToObject'] );
		$new_tab = isset( $attr['newTab'] ) && boolval( $attr['newTab'] );

		switch ( $field['type'] ) {
			case 'true_false':
				if ( boolval( $field['value'] ) && isset( $attr['checkedText'] ) && ! empty( $attr['checkedText'] ) ) {
					$value = esc_html( $attr['checkedText'] );
				} elseif ( isset( $attr['uncheckedText'] ) && ! empty( $attr['uncheckedText'] ) ) {
					$value = esc_html( $attr['uncheckedText'] );
				}
				break;

			case 'wysiwyg':
				$tag   = 'div';
				$value = wp_kses_post( $field['value'] );
				break;
				
			case 'link':
				if ( isset( $field['value']['url'], $field['value']['title'] ) ) {
					$value = $this->create_link( $field['value']['url'], $field['value']['title'], $new_tab );
				}
				break;

			case 'post_object':
			case 'relationship':
				if ( is_array( $field['value'] ) ) {
					$value = array_map( function( $val ) use ( $is_link, $field, $new_tab ) {
						if ( $is_link ) {
							return $this->create_link( get_permalink( $field['value'] ), get_the_title( $field['value'] ), $new_tab );
						} else {
							return get_the_title( $field['value'] );
						}
					}, $field['value'] );
				} else {
					if ( $is_link ) {
						$value = $this->create_link( get_permalink( $field['value'] ), get_the_title( $field['value'] ), $new_tab );
					} else {
						$value = get_the_title( $field['value'] );
					}
				}
				break;

			case 'taxonomy':
				if ( is_array( $field['value'] ) ) {
					$value = array_map( function( $val ) use ( $is_link, $field, $new_tab ) {
						$term = get_term( $val );
						if ( is_wp_error( $term ) || is_null( $term ) ) {
							return '';
						}
						if ( $is_link ) {
							return $this->create_link( get_term_link( $term ), $term->name, $new_tab );
						} else {
							return esc_html( $term->name );
						}
					}, $field['value'] );
				} else {
					$term = get_term( $field['value'] );
					if ( is_wp_error( $term ) || is_null( $term ) ) {
						break;
					}
					if ( $is_link ) {
						$value = $this->create_link( get_term_link( $term ), $term->name, $new_tab );
					} else {
						$value = esc_html( $term->name );
					}
				}
				break;

			case 'user':
				if ( is_array( $field['value'] ) ) {
					$value = array_map( function( $val ) use ( $is_link, $field, $new_tab ) {
						$user = get_userdata( $val );
						if ( ! $user ) {
							return '';
						}
						if ( $is_link ) {
							return $this->create_link( get_author_posts_url( $user->data->ID ), $user->data->display_name, $new_tab );
						} else {
							return esc_html( $user->data->display_name );
						}
					}, $field['value'] );
				} else {
					$user = get_userdata( $field['value'] );
					if ( ! $user ) {
						break;
					}
					if ( $is_link ) {
						$value = $this->create_link( get_author_posts_url( $user->data->ID ), $user->data->display_name, $new_tab );
					} else {
						$value = esc_html( $user->data->display_name );
					}
				}
				break;

			case 'select':
			case 'radio':
			case 'checkbox':
			case 'button_group':
				if ( is_array( $field['value'] ) ) {
					$value = array_map( function( $val ) use ( $attr, $field ) {
						if ( isset( $attr['returnFormat'] ) && 'label' === $attr['returnFormat'] && isset( $field['choices'][ $val ] ) ) {
							return esc_html( $field['choices'][ $val ] );
						} else {
							return esc_html( $val );
						}
					}, $field['value'] );
				} else {
					if ( isset( $attr['returnFormat'] ) && 'label' === $attr['returnFormat'] && isset( $field['choices'][ $field['value'] ] ) ) {
						$value = esc_html( $field['choices'][ $field['value'] ] );
					} else {
						$value = esc_html( $field['value'] );
					}
				}
				break;

			case 'email':
				if ( $is_link ) {
					$value = $this->create_link( 'mailto:' . $field['value'], $field['value'], false );
				} else {
					$value = esc_html( $field['value'] );
				}
				break;

			case 'url':
				if ( $is_link ) {
					$value = $this->create_link( $field['value'], $field['value'], $new_tab );
				} else {
					$value = esc_url( $field['value'] );
				}
				break;

			case 'page_link':
				if ( is_array( $field['value'] ) ) {
					$value = array_map( function( $val ) use ( $new_tab ) {
						$link = is_numeric( $val ) ? get_permalink( $val ) : $val;
						return $this->create_link( $link, $link, $new_tab );
					}, $field['value'] );
				} else {
					$link  = is_numeric( $field['value'] ) ? get_permalink( $field['value'] ) : $field['value'];
					$value = $this->create_link( $link, $link, $new_tab );
				}
				break;
			
			default:
				$value = is_array( $field['value'] ) ? array_map( 'esc_html', $field['value'] ) : esc_html( $field['value'] );
				break;
		}

		// filter empty strings from value if array.
		if ( is_array( $value ) ) {
			$value = array_filter( $value, function( $val ) {
				return '' !== $val;
			} );
		}

		if ( '' === $value || ( is_array( $value ) && empty( $value ) ) ) {
			return '';
		}

		$prefix = ( 'true_false' !== $field['type'] || 'wysiwyg' !== $field['type'] ) && isset( $attr['prefix'] ) && ! empty( $attr['prefix'] ) ? $attr['prefix'] : '';
		$suffix = ( 'true_false' !== $field['type'] || 'wysiwyg' !== $field['type'] ) && isset( $attr['suffix'] ) && ! empty( $attr['suffix'] ) ? $attr['suffix'] : '';

		ob_start();
		?>
			<<?php echo esc_html( $tag ); ?> <?php echo wp_kses_post( $wrapper_attributes ); ?>>
				<?php if ( ! empty( $prefix ) ) : ?>
					<span class="prefix"><?php echo esc_html( $prefix ); ?></span>
				<?php endif; ?>
				<?php if ( 'wysiwyg' === $field['type'] ) : ?>
					<?php echo wp_kses_post( $value ); ?>
				<?php elseif ( is_array( $value ) ) : ?>
					<?php
					foreach ( $value as $key => $val ) {
						if ( 0 < $key && isset( $attr['separator'] ) ) {
							echo '<span class="separator">' . esc_html( $attr['separator'] ) . '</span>';
						}
						echo '<span class="value">' . wp_kses_post( $val ) . '</span>';
					}
					?>
				<?php else: ?>
					<span class="value"><?php echo wp_kses_post( $value ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $suffix ) ) : ?>
					<span class="suffix"><?php echo esc_html( $suffix ); ?></span>
				<?php endif; ?>
			</<?php echo esc_html( $tag ); ?>>
		<?php
		return ob_get_clean();
	}

	private function create_link( $url, $text, $new_tab = false ) {
		return '<a href="' . esc_url( $url ) . '" ' . ( $new_tab ? 'target="_blank"' : '' ) . '>' . esc_html( $text ) . '</a>';
	}

}