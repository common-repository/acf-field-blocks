<?php
/**
 * REST handlers.
 *
 * @package ACFFieldBlocks
 */

namespace ACFFieldBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rest
 */
class Rest {

	/**
	 * Instance
	 * 
	 * @var ACFFieldBlocks/Rest
	 */
	private static $instance;

	/**
	 * API Namespace
	 * 
	 * @var string
	 */
	public $namespace = 'acf-field-blocks';

	/**
	 * API Version
	 * 
	 * @var string
	 */
	public $version = 'v1';

	/**
	 * Initiator
	 * 
	 * @return Rest()
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register API routes
	 */
	public function register_routes() {
		$namespace = $this->namespace . '/' . $this->version;

		register_rest_route(
			$namespace,
			'/fieldgroups',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_all_field_groups' ),
				'args'                => array(),
				'permission_callback' => function() {
					return current_user_can('publish_posts');
				},
			)
		);

		register_rest_route(
			$namespace,
			'/fields',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_all_fields' ),
				'args'                => array(),
				'permission_callback' => function() {
					return current_user_can('publish_posts');
				},
			)
		);

		register_rest_route(
			$namespace,
			'/values',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_all_values' ),
				'args'                => array(),
				'permission_callback' => function() {
					return current_user_can('publish_posts');
				},
			)
		);
	}

	/**
	 * Get all registered ACF field groups and its fields
	 *
	 * @since  1.0.0
	 * 
	 * @param  \WP_REST_Request $request Request params.
	 * @return array                     List of field groups and its fields.
	 */
	public function get_all_field_groups( \WP_REST_Request $request ) {
		$field_groups = acf_get_field_groups();
		$json         = array();

		if ( empty( $field_groups ) ) {
			return $json;
		}

		foreach ( $field_groups as $field_group ) {
			$post_type = acf_determine_internal_post_type( $field_group['key'] );
			$post      = acf_get_internal_post_type( $field_group['key'], $post_type );

			if ( empty( $post ) ) {
				continue;
			}

			if ( 'acf-field-group' === $post_type ) {
				$post['fields'] = acf_get_fields( $post );
			}

			$post   = acf_prepare_internal_post_type_for_export( $post, $post_type );
			$post['object_types'] = $this->get_object_types( $post );
			$json[] = $post;
		}

		return $json;
	}

	/**
	 * Get the object type of a field group.
	 *
	 * @since  1.0.0
	 * 
	 * @param  array $field_group Field group object.
	 * @return array              Object types.
	 */
	private function get_object_types( $field_group ) {
		$objects = array();
		if ( $field_group['location'] ) {
			foreach ( $field_group['location'] as $i => $rules ) {

				// Determine object types for each rule.
				foreach ( $rules as $j => $rule ) {

					// Get location type and subtype for the current rule.
					$location                = acf_get_location_rule( $rule['param'] );
					$location_object_type    = '';
					$location_object_subtype = '';
					if ( $location ) {
						$location_object_type    = $location->get_object_type( $rule );
						$location_object_subtype = $location->get_object_subtype( $rule );
					}
					$rules[ $j ]['object_type']    = $location_object_type;
					$rules[ $j ]['object_subtype'] = $location_object_subtype;
				}

				// Now that each $rule conains object type data...
				$object_types = array_column( $rules, 'object_type' );
				$object_types = array_filter( $object_types );
				$object_types = array_values( $object_types );
				if ( $object_types ) {
					$object_type = $object_types[0];
				} else {
					continue;
				}

				$object_subtypes = array_column( $rules, 'object_subtype' );
				$object_subtypes = array_filter( $object_subtypes );
				$object_subtypes = array_values( $object_subtypes );
				$object_subtypes = array_map( 'acf_array', $object_subtypes );
				if ( count( $object_subtypes ) > 1 ) {
					$object_subtypes = call_user_func_array( 'array_intersect', $object_subtypes );
					$object_subtypes = array_values( $object_subtypes );
				} elseif ( $object_subtypes ) {
					$object_subtypes = $object_subtypes[0];
				} else {
					$object_subtypes = array( '' );
				}

				// Append to objects.
				foreach ( $object_subtypes as $object_subtype ) {
					$object = acf_get_object_type( $object_type, $object_subtype );

					// fix all terms.
					if ( false === $object ) {
						$object = new \stdClass();
						$object->type    = $object_type;
						$object->subtype = $object_subtype;
						$object->name    = "$object_type/$object_subtype";
						$object->label   = '';
						$object->icon    = '';
					}

					if ( $object ) {
						$objects[ $object->name ] = $object;
					}
				}
			}
		}

		// Reset keys.
		$objects = array_values( $objects );

		return $objects;
	}

	/**
	 * Get all registered ACF fields
	 *
	 * @since  1.0.0
	 * 
	 * @param  \WP_REST_Request $request Request params.
	 * @return array                     List of fields that contains.
	 */
	public function get_all_fields( \WP_REST_Request $request ) {
		$field_groups = acf_get_field_groups();
		$json         = array();

		if ( empty( $field_groups ) ) {
			return $json;
		}

		foreach ( $field_groups as $field_group ) {
			$post_type = acf_determine_internal_post_type( $field_group['key'] );
			$post      = acf_get_internal_post_type( $field_group['key'], $post_type );

			if ( empty( $post ) ) {
				continue;
			}

			if ( 'acf-field-group' === $post_type ) {
				$fields = acf_get_fields( $post );

				foreach ( $fields as $field ) {
					$json[ $field['key'] ] = [
						'type'          => $field['type'],
						'label'         => $field['label'],
						'return_format' => $field['return_format'] ?? '',
						'full_label'    => $field['label'],
						'multiple'      => $this->is_field_has_multiple_values( $field )
					];

					if ( 'taxonomy' === $field['type'] ) {
						$json[ $field['key'] ]['taxonomy'] = $field['taxonomy'];
					}

					if ( isset( $field['sub_fields'] ) ) {
						$json = $this->iterate_sub_fields( $json, $field );
					}
				}
			}
		}

		return $json;
	}

	/**
	 * Iterate to deep sub fields
	 *
	 * @since  1.0.0
	 * 
	 * @param  array  $json  Field list.
	 * @param  array  $field FIeld object.
	 * @return array         Updated field list.
	 */
	private function iterate_sub_fields( $json, $field ) {
		foreach ( $field['sub_fields'] as $sub_field ) {
			$json[ $sub_field['key'] ] = [
				'type'          => $sub_field['type'],
				'label'         => $sub_field['label'],
				'return_format' => $sub_field['return_format'] ?? '',
				'full_label'    => $json[ $field['key'] ]['full_label'] . ' - ' . $sub_field['label'],
				'multiple'      => $this->is_field_has_multiple_values( $sub_field )
			];

			if ( 'taxonomy' === $sub_field['type'] ) {
				$json[ $sub_field['key'] ]['taxonomy'] = $sub_field['taxonomy'];
			}

			if ( isset( $sub_field['sub_fields'] ) ) {
				$json = $this->iterate_sub_fields( $json, $sub_field );
			}
		}
		return $json;
	}

	/**
	 * Check if the field has multiple returns.
	 *
	 * @since  1.0.0
	 * 
	 * @param  array   $field Field object.
	 * @return boolean        Is has multiple returns.
	 */
	private function is_field_has_multiple_values( $field ) {
		$result = false;
		if ( 'checkbox' === $field['type'] ) {
			$result = true;
		} elseif ( 'relationship' === $field['type'] ) {
			$result = true;
		} elseif ( 'taxonomy' === $field['type'] && ( "checkbox" === $field['field_type'] || "multi_select" === $field['field_type'] ) ) {
			$result = true;
		} elseif ( isset( $field['multiple'] ) ) {
			$result = boolval( $field['multiple'] );
		}
		return apply_filters( 'acf_field_blocks_field_has_multiple_returns', $result );
	}

	/**
	 * Get all ACF values of an object.
	 *
	 * @since  1.0.0
	 * 
	 * @param  \WP_REST_Request $request Request params.
	 * @return array                     List of values.
	 */
	public function get_all_values( \WP_REST_Request $request ) {
		if ( ! $request->has_param('id') ) {
			return [];
		}

		$post_id = $request->get_param('id');

		if ( empty( $post_id ) ) {
			return [];
		}

		$fields = get_field_objects( $post_id, false, true, false );
		$meta   = array();

		if ( ! $fields ) {
			return [];
		}

		foreach ( $fields as $k => $field ) {
			$meta[ $field['key'] ] = $this->get_field_value( $field );
		}

		return $meta;
	}

	/**
	 * Get the field value
	 *
	 * @since  1.0.0
	 * 
	 * @param  array  $field Field object
	 * @return mixed         Unformatted value of the field.
	 */
	private function get_field_value( $field ) {
		if ( isset( $field['choices'] ) && ! empty( $field['value'] ) ) {
			if ( is_array( $field['value'] ) ) {
				return array_map( function( $val ) use ( $field ) {
					return [
						'value' => $val,
						'label' => $field['choices'][ $val ]
					];
				}, $field['value'] );
			} else {
				return [
					'value' => $field['value'],
					'label' => $field['choices'][ $field['value'] ]
				];
			}
		} elseif ( 'post_object' === $field['type'] || 'relationship' === $field['type'] ) {
			if ( is_array( $field['value'] ) ) {
				return array_map( 'get_post', $field['value'] );
			} elseif ( ! empty( $field['value'] ) ) {
				return get_post( $field['value'] );
			}
		} elseif ( 'taxonomy' === $field['type'] ) {
			if ( is_array( $field['value'] ) ) {
				return array_map( 'get_term', $field['value'] );
			} elseif ( ! empty( $field['value'] ) ) {
				return get_term( $field['value'] );
			}
		} elseif ( 'page_link' === $field['type'] ) {
			if ( is_array( $field['value'] ) ) {
				return array_map( function( $val ) {
					if ( is_numeric( $val ) ) {
						return get_permalink( $val );
					}
					return $val;
				}, $field['value'] );
			} elseif ( ! empty( $field['value'] ) && is_numeric( $field['value'] ) ) {
				return get_permalink( $field['value'] );
			}
		} elseif ( 'user' === $field['type'] ) {
			if ( is_array( $field['value'] ) ) {
				return array_map( function( $val ) {
					$user = get_userdata( $val );
					if ( $user ) {
						$return = [
							'ID'            => $user->data->ID,
							'user_email'    => $user->data->user_email,
							'user_url'      => $user->data->user_url,
							'display_name'  => $user->data->display_name,
							'user_nicename' => $user->data->user_nicename,
							'user_login'    => $user->data->user_login
						];
						return $return;
					}
				}, $field['value'] );
			} elseif ( ! empty( $field['value'] ) ) {
				$user = get_userdata( $field['value'] );
				if ( $user ) {
					$return = [
						'ID'            => $user->data->ID,
						'user_email'    => $user->data->user_email,
						'user_url'      => $user->data->user_url,
						'display_name'  => $user->data->display_name,
						'user_nicename' => $user->data->user_nicename,
						'user_login'    => $user->data->user_login
					];
					return $return;
				}
			}
		}
		return apply_filters( 'acf_field_blocks_get_value', $field['value'], $field );
	}

}