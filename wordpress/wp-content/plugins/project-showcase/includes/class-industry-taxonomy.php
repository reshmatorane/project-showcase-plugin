<?php
/**
 * Industry Custom Taxonomy Registration.
 *
 * @package ProjectShowcase
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Project_Showcase_Taxonomy
 *
 * Handles registration and management of the 'industry' custom taxonomy.
 */
class Project_Showcase_Taxonomy {

	/**
	 * Taxonomy identifier constant.
	 */
	const TAXONOMY = 'industry';

	/**
	 * Associated post type constant.
	 */
	const POST_TYPE = 'project';

	/**
	 * Register the 'industry' taxonomy.
	 *
	 * @return void
	 */
	public function register() {
		$labels = array(
			'name'                       => _x( 'Industries', 'Taxonomy General Name', 'project-showcase' ),
			'singular_name'              => _x( 'Industry', 'Taxonomy Singular Name', 'project-showcase' ),
			'menu_name'                  => __( 'Industries', 'project-showcase' ),
			'all_items'                  => __( 'All Industries', 'project-showcase' ),
			'parent_item'                => __( 'Parent Industry', 'project-showcase' ),
			'parent_item_colon'          => __( 'Parent Industry:', 'project-showcase' ),
			'new_item_name'              => __( 'New Industry Name', 'project-showcase' ),
			'add_new_item'               => __( 'Add New Industry', 'project-showcase' ),
			'edit_item'                  => __( 'Edit Industry', 'project-showcase' ),
			'update_item'                => __( 'Update Industry', 'project-showcase' ),
			'view_item'                  => __( 'View Industry', 'project-showcase' ),
			'separate_items_with_commas' => __( 'Separate industries with commas', 'project-showcase' ),
			'add_or_remove_items'        => __( 'Add or remove industries', 'project-showcase' ),
			'choose_from_most_used'      => __( 'Choose from the most used industries', 'project-showcase' ),
			'popular_items'              => __( 'Popular Industries', 'project-showcase' ),
			'search_items'               => __( 'Search Industries', 'project-showcase' ),
			'not_found'                  => __( 'Not Found', 'project-showcase' ),
			'no_terms'                   => __( 'No industries', 'project-showcase' ),
			'items_list'                 => __( 'Industries list', 'project-showcase' ),
			'items_list_navigation'      => __( 'Industries list navigation', 'project-showcase' ),
		);

		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
			'show_in_rest'               => true,
			'rest_base'                  => 'industries-core',
			'rest_controller_class'      => 'WP_REST_Terms_Controller',
			'rewrite'                    => array(
				'slug'         => 'industry',
				'with_front'   => false,
				'hierarchical' => true,
			),
		);

		register_taxonomy( self::TAXONOMY, array( self::POST_TYPE ), $args );
	}
}
