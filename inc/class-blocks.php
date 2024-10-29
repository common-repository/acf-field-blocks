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
 * Class Blocks
 */
class Blocks {

	/**
	 * Block name
	 * 
	 * @var string
	 */
	protected $name;

	/**
	 * Flag to list all the blocks.
	 *
	 * @var array
	 */
	public static $blocks = array();

	/**
	 * Flag to list all the blocks dependencies.
	 *
	 * @var array
	 */
	public static $block_dependencies = array();

	/**
	 * Flag to mark that the scripts which have loaded.
	 *
	 * @var array
	 */
	public static $scripts_loaded = array(
	);

	/**
	 * Flag to mark that the styles which have loaded.
	 *
	 * @var array
	 */
	public static $styles_loaded = array();

	/**
	 * Attributes
	 * 
	 * @var array
	 */
	protected $attrs;

	/**
	 * Class instance
	 * 
	 * @var Main
	 */
	private static $instance;

	/**
	 * Initiator
	 * 
	 * @return Blocks()
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
		add_filter( 'block_categories_all', [ $this, 'register_block_categories' ] );
		add_action( 'init', [ $this, 'register_blocks' ] );
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_assets' ], 1 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );
	}

	/**
	 * Register block categories
	 *
	 * @since  1.0.0
	 * 
	 * @param  array $categories Block categories.
	 * @return array             Block categories.
	 */
	public function register_block_categories( $categories ) {
		$categories[] = array(
			'slug'  => 'acf-field-blocks',
			'title' => 'ACF Field Blocks'
		);
		return $categories;
	}

	/**
	 * Get block metadata from file.
	 * 
	 * @since  1.0.0
	 *
	 * @param  string $metadata_file Metadata file link.
	 * @return mixed
	 */
	public function get_metadata( $metadata_file ) {
		if ( ! file_exists( $metadata_file ) ) {
			return false;
		}

		ob_start();
		include $metadata_file;
		$metadata = ob_get_clean();
		$metadata = json_decode( $metadata, true );

		if ( ! is_array( $metadata ) || empty( $metadata['name'] ) ) {
			return false;
		}

		return $metadata;
	}


	public function enqueue_assets() {
		$asset_file = include ACF_FIELD_BLOCKS_PATH . '/build/blocks/blocks.asset.php';

		if ( is_admin() ) {
			wp_enqueue_style( 'acf-field-blocks-editor', ACF_FIELD_BLOCKS_URL . '/build/blocks/blocks.css', array( 'wp-edit-blocks' ), $asset_file['version'] );
		} else {
			// wp_enqueue_style( 'acf-field-blocks', ACF_FIELD_BLOCKS_URL . '/build/blocks/blocks.css', [], $asset_file['version'] );
		}
	}

	/**
	 * Load blocks assets.
	 *
	 * @since   1.0.0
	 */
	public function enqueue_block_editor_assets() {
		global $wp_version;
		$asset_file = include ACF_FIELD_BLOCKS_PATH . '/build/blocks/blocks.asset.php';

		$current_screen = get_current_screen();

		// wp_register_script( 'acf-field-vendor', ACF_FIELD_BLOCKS_URL . '/build/blocks/vendor.js', array( 'react', 'react-dom' ), $asset_file['version'], true );

		wp_enqueue_script(
			'acf-field-blocks',
			ACF_FIELD_BLOCKS_URL . '/build/blocks/blocks.js',
			array_merge(
				$asset_file['dependencies'],
				[]
				// array( 'acf-field-vendor' )
			),
			$asset_file['version'],
			true
		);

		wp_set_script_translations( 'acf-field-blocks', 'acf-field-blocks' );

		wp_localize_script( 'acf-field-blocks', 'ACFFieldBlocks', array(
			'wpVersion'            => $wp_version,
			'pluginVersion'        => ACF_FIELD_BLOCKS_VERSION,
			'hasACFOptionPage'     => function_exists('acf_get_options_pages') ? ! empty( acf_get_options_pages() ) : false
		) );

		// wp_enqueue_style( 'acf-field-editor', ACF_FIELD_BLOCKS_URL . '/build/blocks/blocks.css', array( 'wp-edit-blocks' ), $asset_file['version'] );
	}

	/**
	 * Load frontend assets for our blocks.
	 *
	 * @since   1.0.0
	 */
	public function enqueue_block_assets() {
		global $wp_query, $wp_registered_sidebars;

		if ( is_admin() ) {
			return;
		}

		if ( is_singular() ) {
			$this->enqueue_dependencies();
		} else {
			if ( ! is_null( $wp_query->posts ) && 0 < count( $wp_query->posts ) ) {
				$posts = wp_list_pluck( $wp_query->posts, 'ID' );

				foreach ( $posts as $post ) {
					$this->enqueue_dependencies( $post );
				}
			}
		}

		add_filter(
			'the_content',
			function ( $content ) {
				$this->enqueue_dependencies();

				return $content;
			}
		);

		$has_widgets = false;

		foreach ( $wp_registered_sidebars as $key => $sidebar ) {
			if ( is_active_sidebar( $key ) ) {
				$has_widgets = true;
				break;
			}
		}

		if ( $has_widgets ) {

			add_filter(
				'wp_footer',
				function ( $content ) {
					$this->enqueue_dependencies( 'widgets' );

					return $content;
				}
			);

		}

		if ( function_exists( 'get_block_templates' ) && function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() && current_theme_supports( 'block-templates' ) ) {
			$this->enqueue_dependencies( 'block-templates' );
		}
	}

	/**
	 * Handler which checks the blocks used and enqueue the assets which needs.
	 *
	 * @since   1.0.0
	 * 
	 * @param   string|int|null $post Current post.
	 */
	public function enqueue_dependencies( $post = null ) {
		$content = '';

		if ( 'block-templates' === $post ) {
			global $_wp_current_template_content;

			$slugs           = array();
			$template_blocks = parse_blocks( $_wp_current_template_content );

			foreach ( $template_blocks as $template_block ) {
				if ( 'core/template-part' === $template_block['blockName'] ) {
					$slugs[] = $template_block['attrs']['slug'];
				}
			}

			$templates_parts = get_block_templates( array( 'slugs__in' => $slugs ), 'wp_template_part' );

			foreach ( $templates_parts as $templates_part ) {
				if ( ! empty( $templates_part->content ) && ! empty( $templates_part->slug ) && in_array( $templates_part->slug, $slugs ) ) {
					$content .= $templates_part->content;
				}
			}

			$content .= $_wp_current_template_content;
			$post     = $content;
		} else {
			$content = get_the_content( null, false, $post );
		}

		$this->enqueue_block_styles( $post );

		if ( has_block( 'core/block', $post ) ) {
			$blocks = parse_blocks( $content );
			$blocks = array_filter(
				$blocks,
				function( $block ) {
					return 'core/block' === $block['blockName'] && isset( $block['attrs']['ref'] );
				}
			);

			foreach ( $blocks as $block ) {
				$this->enqueue_dependencies( $block['attrs']['ref'] );
			}
		}
	}

	/**
	 * Enqueue block styles.
	 *
	 * @since   1.0.0
	 * 
	 * @param null $post Current post.
	 */
	public function enqueue_block_styles( $post ) {
		foreach ( self::$blocks as $block ) {
			if ( in_array( $block, self::$styles_loaded ) || ! has_block( 'acf-field-blocks/' . $block, $post ) ) {
				continue;
			}

			$block_path = ACF_FIELD_BLOCKS_PATH . '/build/blocks/' . $block;
			$style      = ACF_FIELD_BLOCKS_URL . '/build/blocks/' . $block . '/style.css';

			if ( ! file_exists( $block_path ) && defined('ACF_FIELD_BLOCKS_PRO_BUILD_PATH') ) {
				$block_path = ACF_FIELD_BLOCKS_PRO_BUILD_PATH . $block;
				$style      = ACF_FIELD_BLOCKS_PRO_BUILD_URL . $block . '/style.css';
			}

			$metadata_file = trailingslashit( $block_path ) . 'block.json';
			$style_path    = trailingslashit( $block_path ) . 'style.css';

			$metadata = $this->get_metadata( $metadata_file );

			if ( false === $metadata ) {
				continue;
			}

			$asset_file = include ACF_FIELD_BLOCKS_PATH . '/build/blocks/blocks.asset.php';

			$deps = array();

			if ( isset( self::$block_dependencies[ $block ] ) ) {
				$deps = self::$block_dependencies[ $block ];
			}

			if ( file_exists( $style_path ) && ! empty( $metadata['style'] ) ) {
				wp_register_style(
					$metadata['style'],
					$style,
					$deps,
					$asset_file['version']
				);

				wp_style_add_data( $metadata['style'], 'path', $style_path );
			}

			array_push( self::$styles_loaded, $block );
		}
	}

	/**
	 * Register blocks.
	 *
	 * @since  1.0.0
	 */
	public function register_blocks() {
		$dynamic_blocks = array(
			'acf-text'  => '\ACFFieldBlocks\Blocks\ACF_Text',
			'acf-image' => '\ACFFieldBlocks\Blocks\ACF_Image',
			'acf-button' => '\ACFFieldBlocks\Blocks\ACF_Button'
		);

		$dynamic_blocks = apply_filters( 'acf_field_blocks_register_dynamic_blocks', $dynamic_blocks );

		self::$blocks = array(
			"acf-text",
			"acf-image",
			"acf-button"
		);

		self::$blocks = apply_filters( 'acf_field_blocks_register_blocks', self::$blocks );

		$this->enqueue_assets();

		self::$block_dependencies = array();

		$local_dependencies = array_merge(
			self::$block_dependencies,
			array()
		);

		foreach ( self::$blocks as $block ) {
			$block_path   = ACF_FIELD_BLOCKS_PATH . '/build/blocks/' . $block;
			$editor_style = ACF_FIELD_BLOCKS_URL . '/build/blocks/' . $block . '/editor.css';

			if ( ! file_exists( $block_path ) && defined('ACF_FIELD_BLOCKS_PRO_BUILD_PATH') ) {
				$block_path   = ACF_FIELD_BLOCKS_PRO_BUILD_PATH . $block;
				$editor_style = ACF_FIELD_BLOCKS_PRO_BUILD_URL . $block . '/editor.css';
			}

			$metadata_file     = trailingslashit( $block_path ) . 'block.json';
			$editor_style_path = trailingslashit( $block_path ) . 'editor.css';
			$metadata = $this->get_metadata( $metadata_file );

			if ( false === $metadata ) {
				continue;
			}

			$asset_file = include ACF_FIELD_BLOCKS_PATH . '/build/blocks/blocks.asset.php';

			$deps = array();

			if ( isset( $local_dependencies[ $block ] ) ) {
				$deps = $local_dependencies[ $block ];
			}

			if ( file_exists( $editor_style_path ) && ! empty( $metadata['editorStyle'] ) ) {
				wp_register_style(
					$metadata['editorStyle'],
					$editor_style,
					$deps,
					$asset_file['version']
				);
			}

			if ( isset( $dynamic_blocks[ $block ] ) ) {
				$classname = $dynamic_blocks[ $block ];
				$renderer  = new $classname();

				if ( method_exists( $renderer, 'render' ) ) {
					register_block_type(
						$metadata_file,
						array(
							'render_callback' => array( $renderer, 'render' ),
						)
					);

					continue;
				}
			}

			register_block_type( $metadata_file );
		}
	}

	/**
	 * Render block
	 * 
	 * @param  array  $attrs         Block attributes.
	 * @param  string $block_content Block content.
	 * @return string                Block content.
	 */
	public function render_block( $attrs, $block_content ) {
		return $block_content;
	}

}