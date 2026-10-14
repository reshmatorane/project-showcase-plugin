<?php
/**
 * Plugin Name:       Project Showcase
 * Plugin URI:        https://github.com/example/project-showcase
 * Description:       Full-stack portfolio and project showcase with custom post type, hierarchical industry taxonomy, secure meta box, transient caching, and REST API endpoints.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Reshma Torane
 * Author URI:        https://github.com/example
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       project-showcase
 * Domain Path:       /languages
 *
 * @package ProjectShowcase
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'PROJECT_SHOWCASE_VERSION', '1.0.0' );
define( 'PROJECT_SHOWCASE_PLUGIN_FILE', __FILE__ );
define( 'PROJECT_SHOWCASE_DIR', plugin_dir_path( __FILE__ ) );
define( 'PROJECT_SHOWCASE_URL', plugin_dir_url( __FILE__ ) );

// Load modular classes.
require_once PROJECT_SHOWCASE_DIR . 'includes/class-project-post-type.php';
require_once PROJECT_SHOWCASE_DIR . 'includes/class-industry-taxonomy.php';
require_once PROJECT_SHOWCASE_DIR . 'includes/class-meta-box.php';
require_once PROJECT_SHOWCASE_DIR . 'includes/class-cache.php';
require_once PROJECT_SHOWCASE_DIR . 'includes/class-rest-api.php';
require_once PROJECT_SHOWCASE_DIR . 'includes/class-seeder.php';
require_once PROJECT_SHOWCASE_DIR . 'includes/class-cli.php';

/**
 * Main Project Showcase Plugin Class.
 *
 * Coordinates initialization of CPT, taxonomy, meta boxes, caching, and REST routes.
 */
final class Project_Showcase_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Project_Showcase_Plugin|null
	 */
	private static $instance = null;

	/**
	 * CPT Manager instance.
	 *
	 * @var Project_Showcase_Post_Type
	 */
	public $post_type;

	/**
	 * Taxonomy Manager instance.
	 *
	 * @var Project_Showcase_Taxonomy
	 */
	public $taxonomy;

	/**
	 * Meta Box Manager instance.
	 *
	 * @var Project_Showcase_Meta_Box
	 */
	public $meta_box;

	/**
	 * Cache Manager instance.
	 *
	 * @var Project_Showcase_Cache
	 */
	public $cache;

	/**
	 * REST API Controller instance.
	 *
	 * @var Project_Showcase_REST_Controller
	 */
	public $rest_api;

	/**
	 * Seeder instance.
	 *
	 * @var Project_Showcase_Seeder
	 */
	public $seeder;

	/**
	 * Get the single class instance.
	 *
	 * @return Project_Showcase_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to enforce singleton pattern.
	 */
	private function __construct() {
		$this->post_type = new Project_Showcase_Post_Type();
		$this->taxonomy  = new Project_Showcase_Taxonomy();
		$this->meta_box  = new Project_Showcase_Meta_Box();
		$this->cache     = new Project_Showcase_Cache();
		$this->rest_api  = new Project_Showcase_REST_Controller( $this->cache );
		$this->seeder    = new Project_Showcase_Seeder();

		$this->init_hooks();
	}

	/**
	 * Initialize plugin action and filter hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Register post type and taxonomy on init.
		add_action( 'init', array( $this->post_type, 'register' ), 0 );
		add_action( 'init', array( $this->taxonomy, 'register' ), 0 );

		// Register meta boxes.
		add_action( 'add_meta_boxes', array( $this->meta_box, 'register' ) );
		add_action( 'save_post_project', array( $this->meta_box, 'save' ), 10, 2 );

		// Register REST API endpoints.
		add_action( 'rest_api_init', array( $this->rest_api, 'register_routes' ) );

		// Cache invalidation hooks.
		$this->cache->init_invalidation_hooks();

		// Add custom admin column for meta values.
		add_filter( 'manage_project_posts_columns', array( $this->meta_box, 'register_columns' ) );
		add_action( 'manage_project_posts_custom_column', array( $this->meta_box, 'render_column' ), 10, 2 );

		// Add CORS header for local frontend development.
		add_action( 'rest_pre_serve_request', array( $this, 'add_cors_headers' ) );

		// Seed trigger via admin query parameter or activation.
		add_action( 'admin_init', array( $this->seeder, 'maybe_seed_via_admin' ) );
	}

	/**
	 * Add permissive CORS headers specifically for the showcase REST namespace.
	 *
	 * Allows seamless local Vite development (e.g. from http://localhost:5173).
	 *
	 * @param bool $served Whether the request has already been served.
	 * @return bool
	 */
	public function add_cors_headers( $served ) {
		if ( ! empty( $_SERVER['REQUEST_URI'] ) && false !== strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/wp-json/showcase/v1' ) ) {
			header( 'Access-Control-Allow-Origin: *' );
			header( 'Access-Control-Allow-Methods: GET, OPTIONS' );
			header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce' );
		}
		return $served;
	}

	/**
	 * Plugin activation routine.
	 *
	 * Registers rewrite rules, flushes them safely, and seeds initial sample projects.
	 *
	 * @return void
	 */
	public static function activate() {
		$instance = self::get_instance();
		$instance->post_type->register();
		$instance->taxonomy->register();

		// Flush rewrites.
		flush_rewrite_rules();

		// Seed sample projects if repository is empty.
		$instance->seeder->seed_if_empty();
	}

	/**
	 * Plugin deactivation routine.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}

// Register activation and deactivation hooks.
register_activation_hook( __FILE__, array( 'Project_Showcase_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Project_Showcase_Plugin', 'deactivate' ) );

// Bootstrap the plugin.
Project_Showcase_Plugin::get_instance();
