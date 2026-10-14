<?php
/**
 * REST API Controller for Project Showcase.
 *
 * Implements public endpoints:
 * - GET /wp-json/showcase/v1/projects
 * - GET /wp-json/showcase/v1/industries
 *
 * Features:
 * - Debounced search & industry taxonomy filtering.
 * - Robust pagination with total items and total pages.
 * - Full metadata envelope (Client Name, URL, Tech Stack array, Launch Date).
 * - Safe featured image extraction with null fallback.
 * - Transient caching with cache hit/miss response headers.
 * - Strict argument sanitization & schema validation.
 *
 * @package ProjectShowcase
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Project_Showcase_REST_Controller
 */
class Project_Showcase_REST_Controller {

	/**
	 * REST route namespace.
	 */
	const NAMESPACE = 'showcase/v1';

	/**
	 * Cache Manager instance.
	 *
	 * @var Project_Showcase_Cache
	 */
	private $cache;

	/**
	 * Constructor.
	 *
	 * @param Project_Showcase_Cache $cache Cache manager instance.
	 */
	public function __construct( Project_Showcase_Cache $cache ) {
		$this->cache = $cache;
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		// Endpoint: GET /wp-json/showcase/v1/projects
		register_rest_route(
			self::NAMESPACE,
			'/projects',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_projects' ),
					'permission_callback' => '__return_true', // Public endpoint.
					'args'                => $this->get_projects_collection_params(),
				),
			)
		);

		// Endpoint: GET /wp-json/showcase/v1/industries
		register_rest_route(
			self::NAMESPACE,
			'/industries',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_industries' ),
					'permission_callback' => '__return_true', // Public endpoint.
				),
			)
		);
	}

	/**
	 * Define collection parameters schema for the /projects endpoint.
	 *
	 * @return array
	 */
	public function get_projects_collection_params() {
		return array(
			'search'   => array(
				'description'       => __( 'Search query matching title, content, or excerpt.', 'project-showcase' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'industry' => array(
				'description'       => __( 'Industry taxonomy slug or term ID to filter by.', 'project-showcase' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'project-showcase' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => function( $param ) {
					return is_numeric( $param ) && (int) $param >= 1;
				},
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items to return per page.', 'project-showcase' ),
				'type'              => 'integer',
				'default'           => 20,
				'minimum'           => 1,
				'maximum'           => 50,
				'sanitize_callback' => 'absint',
				'validate_callback' => function( $param ) {
					return is_numeric( $param ) && (int) $param >= 1 && (int) $param <= 50;
				},
			),
			'orderby'  => array(
				'description'       => __( 'Sort collection by object attribute.', 'project-showcase' ),
				'type'              => 'string',
				'default'           => 'date',
				'enum'              => array( 'date', 'title', 'id', 'modified' ),
				'sanitize_callback' => 'sanitize_key',
			),
			'order'    => array(
				'description'       => __( 'Order sort attribute ascending or descending.', 'project-showcase' ),
				'type'              => 'string',
				'default'           => 'DESC',
				'enum'              => array( 'ASC', 'DESC', 'asc', 'desc' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Handler for GET /wp-json/showcase/v1/projects.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_projects( $request ) {
		// Extract sanitized parameters.
		$search   = trim( (string) $request->get_param( 'search' ) );
		$industry = trim( (string) $request->get_param( 'industry' ) );
		$page     = (int) ( $request->get_param( 'page' ) ?: 1 );
		$per_page = (int) ( $request->get_param( 'per_page' ) ?: 6 );
		$orderby  = (string) ( $request->get_param( 'orderby' ) ?: 'date' );
		$order    = strtoupper( (string) ( $request->get_param( 'order' ) ?: 'DESC' ) );

		// Validate order parameter.
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'DESC';
		}

		// Build normalized query signature for transient caching.
		$cache_params = array(
			'search'   => $search,
			'industry' => $industry,
			'page'     => $page,
			'per_page' => $per_page,
			'orderby'  => $orderby,
			'order'    => $order,
		);

		$cache_key = $this->cache->generate_key( 'projects', $cache_params );
		$cached    = $this->cache->get( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			$response = new WP_REST_Response( $cached, 200 );
			$response->header( 'X-Showcase-Cache', 'HIT' );
			$response->header( 'X-WP-Total', (string) ( $cached['pagination']['total'] ?? 0 ) );
			$response->header( 'X-WP-TotalPages', (string) ( $cached['pagination']['total_pages'] ?? 1 ) );
			return $response;
		}

		// Build WP_Query arguments.
		$query_args = array(
			'post_type'              => 'project',
			'post_status'            => 'publish', // Strictly published projects only (no drafts or trashed).
			'posts_per_page'         => $per_page,
			'paged'                  => $page,
			'orderby'                => $orderby,
			'order'                  => $order,
			'no_found_rows'          => false,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		);

		// Apply search filter.
		if ( ! empty( $search ) ) {
			$query_args['s'] = $search;
		}

		// Apply industry taxonomy filter.
		if ( ! empty( $industry ) ) {
			if ( is_numeric( $industry ) ) {
				$query_args['tax_query'] = array(
					array(
						'taxonomy' => 'industry',
						'field'    => 'term_id',
						'terms'    => (int) $industry,
					),
				);
			} else {
				$query_args['tax_query'] = array(
					array(
						'taxonomy' => 'industry',
						'field'    => 'slug',
						'terms'    => sanitize_title( $industry ),
					),
				);
			}
		}

		$query = new WP_Query( $query_args );

		$projects = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$projects[] = $this->format_project( get_post() );
			}
			wp_reset_postdata();
		}

		$total_posts = (int) $query->found_posts;
		$total_pages = (int) $query->max_num_pages;

		$payload = array(
			'success'    => true,
			'data'       => $projects,
			'pagination' => array(
				'total'        => $total_posts,
				'total_pages'  => $total_pages,
				'current_page' => $page,
				'per_page'     => $per_page,
				'has_next'     => $page < $total_pages,
				'has_prev'     => $page > 1,
			),
		);

		// Store in transient cache.
		$this->cache->set( $cache_key, $payload );

		$response = new WP_REST_Response( $payload, 200 );
		$response->header( 'X-Showcase-Cache', 'MISS' );
		$response->header( 'X-WP-Total', (string) $total_posts );
		$response->header( 'X-WP-TotalPages', (string) $total_pages );

		return $response;
	}

	/**
	 * Handler for GET /wp-json/showcase/v1/industries.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_industries( $request ) {
		$cache_key = $this->cache->generate_key( 'industries', array( 'all' => true ) );
		$cached    = $this->cache->get( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			$response = new WP_REST_Response( $cached, 200 );
			$response->header( 'X-Showcase-Cache', 'HIT' );
			return $response;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'industry',
				'hide_empty' => false,
			)
		);

		$industries = array();
		if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$industries[] = array(
					'id'          => (int) $term->term_id,
					'name'        => sanitize_text_field( $term->name ),
					'slug'        => sanitize_title( $term->slug ),
					'count'       => (int) $term->count,
					'description' => sanitize_text_field( $term->description ),
				);
			}
		}

		$payload = array(
			'success' => true,
			'data'    => $industries,
		);

		$this->cache->set( $cache_key, $payload );

		$response = new WP_REST_Response( $payload, 200 );
		$response->header( 'X-Showcase-Cache', 'MISS' );

		return $response;
	}

	/**
	 * Format a WP_Post object into the standardized Project JSON schema.
	 *
	 * @param WP_Post $post Project post object.
	 * @return array
	 */
	private function format_project( $post ) {
		$post_id = $post->ID;

		// 1. Extract metadata.
		$client_name = get_post_meta( $post_id, '_project_client_name', true );
		$project_url = get_post_meta( $post_id, '_project_url', true );
		$raw_stack   = get_post_meta( $post_id, '_project_tech_stack', true );
		$launch_date = get_post_meta( $post_id, '_project_launch_date', true );

		// Parse comma-separated tech stack into a clean array.
		$tech_stack = array();
		if ( ! empty( $raw_stack ) ) {
			$tech_stack = array_values(
				array_filter(
					array_map( 'trim', explode( ',', (string) $raw_stack ) )
				)
			);
		}

		// 2. Extract industries taxonomy.
		$terms = wp_get_post_terms( $post_id, 'industry' );
		$industries = array();
		if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$industries[] = array(
					'id'   => (int) $term->term_id,
					'name' => sanitize_text_field( $term->name ),
					'slug' => sanitize_title( $term->slug ),
				);
			}
		}

		// 3. Extract featured image safely.
		$featured_image = null;
		if ( has_post_thumbnail( $post_id ) ) {
			$thumb_id   = get_post_thumbnail_id( $post_id );
			$large_src  = wp_get_attachment_image_url( $thumb_id, 'large' );
			$thumb_src  = wp_get_attachment_image_url( $thumb_id, 'medium' );
			$meta_alt   = get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );

			if ( $large_src ) {
				$featured_image = array(
					'url'       => esc_url_raw( $large_src ),
					'thumbnail' => esc_url_raw( $thumb_src ? $thumb_src : $large_src ),
					'alt'       => sanitize_text_field( $meta_alt ? $meta_alt : $post->post_title ),
				);
			}
		}

		// 4. Return clean, unified response model.
		return array(
			'id'             => (int) $post_id,
			'title'          => sanitize_text_field( get_the_title( $post_id ) ),
			'slug'           => sanitize_title( $post->post_name ),
			'excerpt'        => wp_strip_all_tags( get_the_excerpt( $post_id ) ),
			'content'        => wp_kses_post( apply_filters( 'the_content', $post->post_content ) ),
			'date'           => get_the_date( 'c', $post_id ),
			'modified'       => get_the_modified_date( 'c', $post_id ),
			'featured_image' => $featured_image, // null if not set.
			'industries'     => $industries,
			'meta'           => array(
				'client_name' => sanitize_text_field( $client_name ? $client_name : '' ),
				'project_url' => esc_url_raw( $project_url ? $project_url : '' ),
				'tech_stack'  => $tech_stack,
				'launch_date' => sanitize_text_field( $launch_date ? $launch_date : '' ),
			),
		);
	}
}
