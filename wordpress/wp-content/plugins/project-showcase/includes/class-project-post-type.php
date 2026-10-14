<?php
/**
 * Project Custom Post Type Registration.
 *
 * @package ProjectShowcase
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Project_Showcase_Post_Type
 *
 * Handles registration and configuration of the 'project' post type.
 */
class Project_Showcase_Post_Type {

	/**
	 * Post type identifier constant.
	 */
	const POST_TYPE = 'project';

	/**
	 * Register the 'project' custom post type.
	 *
	 * @return void
	 */
	public function register() {
		$labels = array(
			'name'                  => _x( 'Projects', 'Post Type General Name', 'project-showcase' ),
			'singular_name'         => _x( 'Project', 'Post Type Singular Name', 'project-showcase' ),
			'menu_name'             => __( 'Projects', 'project-showcase' ),
			'name_admin_bar'        => __( 'Project', 'project-showcase' ),
			'archives'              => __( 'Project Archives', 'project-showcase' ),
			'attributes'            => __( 'Project Attributes', 'project-showcase' ),
			'parent_item_colon'     => __( 'Parent Project:', 'project-showcase' ),
			'all_items'             => __( 'All Projects', 'project-showcase' ),
			'add_new_item'          => __( 'Add New Project', 'project-showcase' ),
			'add_new'               => __( 'Add New', 'project-showcase' ),
			'new_item'              => __( 'New Project', 'project-showcase' ),
			'edit_item'             => __( 'Edit Project', 'project-showcase' ),
			'update_item'           => __( 'Update Project', 'project-showcase' ),
			'view_item'             => __( 'View Project', 'project-showcase' ),
			'view_items'            => __( 'View Projects', 'project-showcase' ),
			'search_items'          => __( 'Search Projects', 'project-showcase' ),
			'not_found'             => __( 'No projects found', 'project-showcase' ),
			'not_found_in_trash'    => __( 'No projects found in Trash', 'project-showcase' ),
			'featured_image'        => __( 'Project Cover Image', 'project-showcase' ),
			'set_featured_image'    => __( 'Set project cover image', 'project-showcase' ),
			'remove_featured_image' => __( 'Remove project cover image', 'project-showcase' ),
			'use_featured_image'    => __( 'Use as project cover image', 'project-showcase' ),
			'insert_into_item'      => __( 'Insert into project', 'project-showcase' ),
			'uploaded_to_this_item' => __( 'Uploaded to this project', 'project-showcase' ),
			'items_list'            => __( 'Projects list', 'project-showcase' ),
			'items_list_navigation' => __( 'Projects list navigation', 'project-showcase' ),
			'filter_items_list'     => __( 'Filter projects list', 'project-showcase' ),
		);

		$args = array(
			'label'                 => __( 'Project', 'project-showcase' ),
			'description'           => __( 'Showcase portfolio project entries', 'project-showcase' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			'taxonomies'            => array( 'industry' ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 20,
			'menu_icon'             => 'dashicons-portfolio',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => 'projects',
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'post',
			'show_in_rest'          => true,
			'rest_base'             => 'projects-core',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'rewrite'               => array(
				'slug'       => 'projects',
				'with_front' => false,
			),
		);

		register_post_type( self::POST_TYPE, $args );
	}
}
