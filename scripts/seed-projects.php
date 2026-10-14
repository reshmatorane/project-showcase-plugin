<?php
/**
 * Standalone Demonstration Projects Seeding Script.
 *
 * Can be executed via WP-CLI:
 *   wp eval-file scripts/seed-projects.php
 *
 * Or directly via PHP CLI if wp-load.php is available:
 *   php scripts/seed-projects.php
 *
 * @package ProjectShowcase
 */

// Attempt to locate WordPress root if run directly via PHP CLI.
if ( ! defined( 'ABSPATH' ) ) {
	$wp_load_candidates = array(
		__DIR__ . '/../wp-load.php',
		__DIR__ . '/../../wp-load.php',
		__DIR__ . '/../../../wp-load.php',
		__DIR__ . '/../../../../wp-load.php',
		__DIR__ . '/../wordpress/wp-load.php',
	);

	foreach ( $wp_load_candidates as $candidate ) {
		if ( file_exists( $candidate ) ) {
			require_once $candidate;
			break;
		}
	}
}

if ( ! defined( 'ABSPATH' ) ) {
	echo "Error: WordPress environment not detected.\n";
	echo "Run this script using WP-CLI:\n";
	echo "  wp eval-file scripts/seed-projects.php\n";
	echo "Or ensure wp-load.php is in your WordPress root.\n";
	exit( 1 );
}

echo "=====================================================\n";
echo "  PROJECT SHOWCASE: EXECUTING REPEATABLE SEED SCRIPT\n";
echo "=====================================================\n";

$plugin_file = WP_PLUGIN_DIR . '/project-showcase/project-showcase.php';
if ( file_exists( $plugin_file ) ) {
	require_once $plugin_file;
}

if ( ! class_exists( 'Project_Showcase_Seeder' ) ) {
	echo "Error: Project_Showcase_Seeder class not found.\n";
	exit( 1 );
}

$seeder = new Project_Showcase_Seeder();
$count  = $seeder->seed();

echo "Seeding completed successfully!\n";
echo "Total projects processed/seeded: {$count}\n";
echo "Industry terms and metadata have been synchronized.\n";
echo "Transient cache has been invalidated.\n";
echo "=====================================================\n";
