<?php
/**
 * WP-CLI Commands for Project Showcase.
 *
 * Provides command-line tools to seed, verify, and purge showcase demonstration data:
 * - wp showcase seed [--force]
 * - wp showcase clear
 * - wp showcase status
 *
 * @package ProjectShowcase
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * Manage Project Showcase custom post type, taxonomies, and sample data.
	 */
	class Project_Showcase_CLI_Command {

		/**
		 * Seed sample demonstration projects and attach local featured images.
		 *
		 * ## OPTIONS
		 *
		 * [--force]
		 * : Re-seed sample projects even if projects already exist.
		 *
		 * ## EXAMPLES
		 *
		 *     wp showcase seed
		 *     wp showcase seed --force
		 *
		 * @param array $args       Positional arguments.
		 * @param array $assoc_args Associative flags.
		 * @return void
		 */
		public function seed( $args, $assoc_args ) {
			$force = isset( $assoc_args['force'] );

			WP_CLI::line( 'Initializing Project Showcase seeder...' );

			$seeder = new Project_Showcase_Seeder();

			if ( ! $force ) {
				$existing_count = count(
					get_posts(
						array(
							'post_type'      => 'project',
							'post_status'    => 'any',
							'posts_per_page' => 10,
							'fields'         => 'ids',
						)
					)
				);

				if ( $existing_count > 0 ) {
					WP_CLI::warning( "Projects already exist in the database ({$existing_count} found). Use --force to proceed anyway." );
					return;
				}
			}

			$count = $seeder->seed();

			WP_CLI::success( "Successfully seeded {$count} demonstration projects with featured images, industries, and secure metadata." );
		}

		/**
		 * Clear all demonstration projects from the database.
		 *
		 * ## EXAMPLES
		 *
		 *     wp showcase clear
		 *
		 * @param array $args       Positional arguments.
		 * @param array $assoc_args Associative flags.
		 * @return void
		 */
		public function clear( $args, $assoc_args ) {
			WP_CLI::confirm( 'Are you sure you want to delete all projects and reset the showcase cache?' );

			$projects = get_posts(
				array(
					'post_type'      => 'project',
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);

			$deleted = 0;
			foreach ( $projects as $post_id ) {
				wp_delete_post( $post_id, true );
				$deleted++;
			}

			$cache = new Project_Showcase_Cache();
			$cache->invalidate();

			WP_CLI::success( "Removed {$deleted} showcase projects and flushed transient caches." );
		}

		/**
		 * Display summary count of showcase projects and industries.
		 *
		 * ## EXAMPLES
		 *
		 *     wp showcase status
		 *
		 * @param array $args       Positional arguments.
		 * @param array $assoc_args Associative flags.
		 * @return void
		 */
		public function status( $args, $assoc_args ) {
			$total_projects = wp_count_posts( 'project' );
			$published      = $total_projects->publish ?? 0;
			$drafts         = $total_projects->draft ?? 0;
			$trash          = $total_projects->trash ?? 0;

			$industries = get_terms(
				array(
					'taxonomy'   => 'industry',
					'hide_empty' => false,
				)
			);

			WP_CLI::line( '=========================================' );
			WP_CLI::line( '       PROJECT SHOWCASE STATUS' );
			WP_CLI::line( '=========================================' );
			WP_CLI::line( "Published Projects : {$published}" );
			WP_CLI::line( "Draft Projects     : {$drafts}" );
			WP_CLI::line( "Trashed Projects   : {$trash}" );
			WP_CLI::line( 'Registered Industries: ' . count( $industries ) );

			if ( ! empty( $industries ) && ! is_wp_error( $industries ) ) {
				$table_data = array();
				foreach ( $industries as $term ) {
					$table_data[] = array(
						'ID'    => $term->term_id,
						'Name'  => $term->name,
						'Slug'  => $term->slug,
						'Count' => $term->count,
					);
				}
				WP_CLI\Utils\format_items( 'table', $table_data, array( 'ID', 'Name', 'Slug', 'Count' ) );
			}
		}
	}

	WP_CLI::add_command( 'showcase', 'Project_Showcase_CLI_Command' );
}
