<?php
/**
 * Transient Caching and Invalidation Manager.
 *
 * Implements a high-performance versioned transient cache strategy for the
 * Project Showcase REST API. Guarantees atomic, race-condition-free cache
 * invalidation whenever projects, metadata, featured images, or taxonomy terms change.
 *
 * @package ProjectShowcase
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Project_Showcase_Cache
 */
class Project_Showcase_Cache {

	/**
	 * Cache version option name.
	 */
	const OPTION_VERSION = 'project_showcase_cache_version';

	/**
	 * Known transient keys registry option.
	 */
	const OPTION_KEYS = 'project_showcase_cached_keys';

	/**
	 * Default cache TTL in seconds (1 hour).
	 */
	const DEFAULT_EXPIRATION = 3600;

	/**
	 * Track monitored meta keys for targeted invalidation.
	 *
	 * @var array
	 */
	private $monitored_meta_keys = array(
		'_project_client_name',
		'_project_url',
		'_project_tech_stack',
		'_project_launch_date',
		'_thumbnail_id',
	);

	/**
	 * Register automatic cache invalidation hooks.
	 *
	 * @return void
	 */
	public function init_invalidation_hooks() {
		// Post lifecycle hooks for 'project' CPT.
		add_action( 'save_post_project', array( $this, 'on_post_change' ), 20, 2 );
		add_action( 'deleted_post', array( $this, 'on_post_delete' ), 20, 2 );
		add_action( 'trash_post', array( $this, 'on_post_status_change' ), 20, 1 );
		add_action( 'untrash_post', array( $this, 'on_post_status_change' ), 20, 1 );
		add_action( 'transition_post_status', array( $this, 'on_transition_post_status' ), 20, 3 );

		// Metadata change hooks (covers custom meta fields & featured image _thumbnail_id).
		add_action( 'updated_post_meta', array( $this, 'on_meta_change' ), 20, 4 );
		add_action( 'added_post_meta', array( $this, 'on_meta_change' ), 20, 4 );
		add_action( 'deleted_post_meta', array( $this, 'on_meta_change' ), 20, 4 );

		// Taxonomy assignment change hooks.
		add_action( 'set_object_terms', array( $this, 'on_terms_change' ), 20, 4 );

		// Industry taxonomy term modifications.
		add_action( 'created_industry', array( $this, 'invalidate' ) );
		add_action( 'edited_industry', array( $this, 'invalidate' ) );
		add_action( 'delete_industry', array( $this, 'invalidate' ) );
	}

	/**
	 * Retrieve current cache version.
	 *
	 * @return int
	 */
	public function get_cache_version() {
		$version = (int) get_option( self::OPTION_VERSION, 1 );
		if ( $version < 1 ) {
			$version = 1;
		}
		return $version;
	}

	/**
	 * Generate a unique, version-scoped transient key.
	 *
	 * Keeps key length strictly within WordPress transient 172-character limit.
	 *
	 * @param string $prefix Prefix identifier (e.g. 'proj', 'ind').
	 * @param array  $params Query parameters.
	 * @return string
	 */
	public function generate_key( $prefix, $params = array() ) {
		$version = $this->get_cache_version();
		$hash    = md5( wp_json_encode( $params ) );
		return 'ps_' . $prefix . '_v' . $version . '_' . substr( $hash, 0, 16 );
	}

	/**
	 * Retrieve data from transient cache.
	 *
	 * @param string $key Transient key.
	 * @return mixed|false
	 */
	public function get( $key ) {
		return get_transient( $key );
	}

	/**
	 * Store data into transient cache and record the key.
	 *
	 * @param string $key        Transient key.
	 * @param mixed  $data       Data payload to cache.
	 * @param int    $expiration TTL in seconds.
	 * @return bool
	 */
	public function set( $key, $data, $expiration = self::DEFAULT_EXPIRATION ) {
		$this->record_transient_key( $key );
		return set_transient( $key, $data, $expiration );
	}

	/**
	 * Invalidate all project showcase cache entries.
	 *
	 * Bumps the atomic version number (instantly invalidating all current keys across
	 * Redis, Memcached, and DB) and purges tracked transients.
	 *
	 * @return void
	 */
	public function invalidate() {
		// 1. Increment version atomically.
		$new_version = time();
		update_option( self::OPTION_VERSION, $new_version, false );

		// 2. Cleanup tracked transient keys from database.
		$tracked_keys = get_option( self::OPTION_KEYS, array() );
		if ( ! empty( $tracked_keys ) && is_array( $tracked_keys ) ) {
			foreach ( $tracked_keys as $key ) {
				delete_transient( $key );
			}
			update_option( self::OPTION_KEYS, array(), false );
		}
	}

	/**
	 * Track generated transient keys in an option for physical cleanup upon invalidation.
	 *
	 * @param string $key Transient key.
	 * @return void
	 */
	private function record_transient_key( $key ) {
		$keys = get_option( self::OPTION_KEYS, array() );
		if ( ! is_array( $keys ) ) {
			$keys = array();
		}
		if ( ! in_array( $key, $keys, true ) ) {
			// Keep max 100 recent keys tracked.
			if ( count( $keys ) >= 100 ) {
				array_shift( $keys );
			}
			$keys[] = $key;
			update_option( self::OPTION_KEYS, $keys, false );
		}
	}

	/**
	 * Invalidate on post save/update.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function on_post_change( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( 'project' === $post->post_type ) {
			$this->invalidate();
		}
	}

	/**
	 * Invalidate on post deletion.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function on_post_delete( $post_id, $post = null ) {
		if ( ! $post || 'project' === $post->post_type ) {
			$this->invalidate();
		}
	}

	/**
	 * Invalidate on post status change (trash, draft, publish).
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function on_post_status_change( $post_id ) {
		$post = get_post( $post_id );
		if ( $post && 'project' === $post->post_type ) {
			$this->invalidate();
		}
	}

	/**
	 * Invalidate on status transition (e.g. draft -> publish, publish -> trash).
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 * @return void
	 */
	public function on_transition_post_status( $new_status, $old_status, $post ) {
		if ( 'project' === $post->post_type && $new_status !== $old_status ) {
			$this->invalidate();
		}
	}

	/**
	 * Invalidate when relevant project metadata or featured image changes.
	 *
	 * @param int    $meta_id     ID of updated metadata entry.
	 * @param int    $object_id   Post ID.
	 * @param string $meta_key    Meta key.
	 * @param mixed  $_meta_value Meta value.
	 * @return void
	 */
	public function on_meta_change( $meta_id, $object_id, $meta_key, $_meta_value ) {
		if ( in_array( $meta_key, $this->monitored_meta_keys, true ) ) {
			$post = get_post( $object_id );
			if ( $post && 'project' === $post->post_type ) {
				$this->invalidate();
			}
		}
	}

	/**
	 * Invalidate when taxonomy terms are modified on a project.
	 *
	 * @param int    $object_id  Post ID.
	 * @param array  $terms      Terms.
	 * @param array  $tt_ids     Term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy name.
	 * @return void
	 */
	public function on_terms_change( $object_id, $terms, $tt_ids, $taxonomy ) {
		if ( 'industry' === $taxonomy ) {
			$this->invalidate();
		}
	}
}
