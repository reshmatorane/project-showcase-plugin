<?php
/**
 * Project Details Meta Box.
 *
 * Implements secure meta fields for:
 * - Client Name
 * - Project URL
 * - Tech Stack
 * - Launch Date
 *
 * Enforces strict nonce validation, capability checks, input sanitization,
 * validation, and safe output escaping.
 *
 * @package ProjectShowcase
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Project_Showcase_Meta_Box
 */
class Project_Showcase_Meta_Box {

	/**
	 * Nonce action string.
	 */
	const NONCE_ACTION = 'project_showcase_save_meta_action';

	/**
	 * Nonce field name.
	 */
	const NONCE_NAME = 'project_showcase_meta_nonce';

	/**
	 * Meta keys constants.
	 */
	const META_CLIENT_NAME = '_project_client_name';
	const META_PROJECT_URL = '_project_url';
	const META_TECH_STACK  = '_project_tech_stack';
	const META_LAUNCH_DATE = '_project_launch_date';

	/**
	 * Register the meta box.
	 *
	 * @return void
	 */
	public function register() {
		add_meta_box(
			'project_showcase_details',
			__( 'Project Showcase Details', 'project-showcase' ),
			array( $this, 'render' ),
			'project',
			'normal',
			'high'
		);
	}

	/**
	 * Render meta box markup in the WordPress admin post editor.
	 *
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render( $post ) {
		// Output security nonce.
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		// Retrieve current values.
		$client_name = get_post_meta( $post->ID, self::META_CLIENT_NAME, true );
		$project_url = get_post_meta( $post->ID, self::META_PROJECT_URL, true );
		$tech_stack  = get_post_meta( $post->ID, self::META_TECH_STACK, true );
		$launch_date = get_post_meta( $post->ID, self::META_LAUNCH_DATE, true );
		?>
		<style>
			.ps-meta-grid {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 16px;
				margin-top: 10px;
			}
			@media (max-width: 782px) {
				.ps-meta-grid { grid-template-columns: 1fr; }
			}
			.ps-field-group {
				margin-bottom: 12px;
			}
			.ps-field-group label {
				display: block;
				font-weight: 600;
				margin-bottom: 5px;
				color: #1e293b;
			}
			.ps-field-group input[type="text"],
			.ps-field-group input[type="url"],
			.ps-field-group input[type="date"] {
				width: 100%;
				padding: 8px 12px;
				border: 1px solid #cbd5e1;
				border-radius: 4px;
				font-size: 14px;
				box-sizing: border-box;
			}
			.ps-field-group input:focus {
				border-color: #2563eb;
				box-shadow: 0 0 0 1px #2563eb;
				outline: none;
			}
			.ps-field-desc {
				font-size: 12px;
				color: #64748b;
				margin-top: 4px;
				font-style: italic;
			}
		</style>

		<div class="ps-meta-container">
			<div class="ps-meta-grid">
				<!-- Client Name -->
				<div class="ps-field-group">
					<label for="project_client_name">
						<?php esc_html_e( 'Client / Organization Name', 'project-showcase' ); ?>
					</label>
					<input
						type="text"
						id="project_client_name"
						name="project_client_name"
						value="<?php echo esc_attr( $client_name ); ?>"
						placeholder="<?php esc_attr_e( 'e.g. Apex Global Corp', 'project-showcase' ); ?>"
					/>
					<p class="ps-field-desc">
						<?php esc_html_e( 'The entity or customer for whom this project was built.', 'project-showcase' ); ?>
					</p>
				</div>

				<!-- Project URL -->
				<div class="ps-field-group">
					<label for="project_url">
						<?php esc_html_e( 'Project Live URL', 'project-showcase' ); ?>
					</label>
					<input
						type="url"
						id="project_url"
						name="project_url"
						value="<?php echo esc_url( $project_url ); ?>"
						placeholder="<?php esc_attr_e( 'https://example.com', 'project-showcase' ); ?>"
					/>
					<p class="ps-field-desc">
						<?php esc_html_e( 'Public URL to the live site, case study, or demo repository.', 'project-showcase' ); ?>
					</p>
				</div>

				<!-- Tech Stack -->
				<div class="ps-field-group">
					<label for="project_tech_stack">
						<?php esc_html_e( 'Tech Stack (comma-separated)', 'project-showcase' ); ?>
					</label>
					<input
						type="text"
						id="project_tech_stack"
						name="project_tech_stack"
						value="<?php echo esc_attr( $tech_stack ); ?>"
						placeholder="<?php esc_attr_e( 'e.g. React, TypeScript, GraphQL, Docker', 'project-showcase' ); ?>"
					/>
					<p class="ps-field-desc">
						<?php esc_html_e( 'Technologies and frameworks used (comma delimited).', 'project-showcase' ); ?>
					</p>
				</div>

				<!-- Launch Date -->
				<div class="ps-field-group">
					<label for="project_launch_date">
						<?php esc_html_e( 'Launch Date', 'project-showcase' ); ?>
					</label>
					<input
						type="date"
						id="project_launch_date"
						name="project_launch_date"
						value="<?php echo esc_attr( $launch_date ); ?>"
					/>
					<p class="ps-field-desc">
						<?php esc_html_e( 'Official deployment or launch date (YYYY-MM-DD).', 'project-showcase' ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save meta box field values securely.
	 *
	 * Performs:
	 * 1. Autosave / revision checks.
	 * 2. Nonce verification.
	 * 3. Capability authorization check.
	 * 4. Input sanitization and date/URL format validation.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		// Bail if doing autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Bail if revision.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Verify nonce exists and is valid.
		if (
			! isset( $_POST[ self::NONCE_NAME ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION )
		) {
			return;
		}

		// Verify user permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Ensure correct post type.
		if ( 'project' !== $post->post_type ) {
			return;
		}

		// 1. Sanitize & save Client Name.
		if ( isset( $_POST['project_client_name'] ) ) {
			$client_name = sanitize_text_field( wp_unslash( $_POST['project_client_name'] ) );
			if ( ! empty( $client_name ) ) {
				update_post_meta( $post_id, self::META_CLIENT_NAME, $client_name );
			} else {
				delete_post_meta( $post_id, self::META_CLIENT_NAME );
			}
		}

		// 2. Validate, sanitize & save Project URL.
		if ( isset( $_POST['project_url'] ) ) {
			$raw_url = trim( sanitize_text_field( wp_unslash( $_POST['project_url'] ) ) );
			if ( ! empty( $raw_url ) ) {
				$sanitized_url = esc_url_raw( $raw_url );
				// Validate scheme.
				$scheme = wp_parse_url( $sanitized_url, PHP_URL_SCHEME );
				$is_valid = in_array( $scheme, array( 'http', 'https' ), true );
				if ( $is_valid && function_exists( 'wp_http_validate_url' ) ) {
					$is_valid = (bool) wp_http_validate_url( $sanitized_url );
				}
				if ( $is_valid ) {
					update_post_meta( $post_id, self::META_PROJECT_URL, $sanitized_url );
				}
			} else {
				delete_post_meta( $post_id, self::META_PROJECT_URL );
			}
		}

		// 3. Sanitize & save Tech Stack.
		if ( isset( $_POST['project_tech_stack'] ) ) {
			$tech_stack = sanitize_text_field( wp_unslash( $_POST['project_tech_stack'] ) );
			if ( ! empty( $tech_stack ) ) {
				update_post_meta( $post_id, self::META_TECH_STACK, $tech_stack );
			} else {
				delete_post_meta( $post_id, self::META_TECH_STACK );
			}
		}

		// 4. Validate, sanitize & save Launch Date (YYYY-MM-DD).
		if ( isset( $_POST['project_launch_date'] ) ) {
			$raw_date = trim( sanitize_text_field( wp_unslash( $_POST['project_launch_date'] ) ) );
			if ( ! empty( $raw_date ) && $this->validate_date( $raw_date ) ) {
				update_post_meta( $post_id, self::META_LAUNCH_DATE, $raw_date );
			} else {
				delete_post_meta( $post_id, self::META_LAUNCH_DATE );
			}
		}
	}

	/**
	 * Validate date strictly in YYYY-MM-DD format.
	 *
	 * @param string $date Raw date string.
	 * @return bool
	 */
	private function validate_date( $date ) {
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches ) ) {
			return false;
		}
		$year  = (int) $matches[1];
		$month = (int) $matches[2];
		$day   = (int) $matches[3];

		return checkdate( $month, $day, $year );
	}

	/**
	 * Register custom admin list table columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function register_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;
			if ( 'title' === $key ) {
				$new_columns['client']      = __( 'Client', 'project-showcase' );
				$new_columns['tech_stack']  = __( 'Tech Stack', 'project-showcase' );
				$new_columns['launch_date'] = __( 'Launch Date', 'project-showcase' );
			}
		}
		return $new_columns;
	}

	/**
	 * Render values in custom admin list table columns.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'client':
				$client = get_post_meta( $post_id, self::META_CLIENT_NAME, true );
				echo esc_html( $client ? $client : '—' );
				break;

			case 'tech_stack':
				$stack = get_post_meta( $post_id, self::META_TECH_STACK, true );
				if ( $stack ) {
					$items = array_map( 'trim', explode( ',', $stack ) );
					$badges = array_map(
						function( $item ) {
							return '<span style="display:inline-block;padding:2px 6px;margin:2px;font-size:11px;background:#e2e8f0;border-radius:3px;">' . esc_html( $item ) . '</span>';
						},
						$items
					);
					echo wp_kses_post( implode( '', $badges ) );
				} else {
					echo '—';
				}
				break;

			case 'launch_date':
				$date = get_post_meta( $post_id, self::META_LAUNCH_DATE, true );
				echo esc_html( $date ? $date : '—' );
				break;
		}
	}
}
