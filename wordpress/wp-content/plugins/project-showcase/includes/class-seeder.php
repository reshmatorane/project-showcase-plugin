<?php
/**
 * Sample Projects Data Seeder.
 *
 * Populates 10 comprehensive demonstration projects with distinct industries,
 * detailed case studies, client metadata, tech stacks, launch dates, and
 * reliable local featured images.
 *
 * Fully idempotent: safe to execute repeatedly without duplicating posts or media.
 *
 * @package ProjectShowcase
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Project_Showcase_Seeder
 */
class Project_Showcase_Seeder {

	/**
	 * Seed sample projects if no projects currently exist in the database.
	 *
	 * @return int Number of seeded projects.
	 */
	public function seed_if_empty() {
		$existing = get_posts(
			array(
				'post_type'      => 'project',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $existing ) ) {
			return 0; // Already populated.
		}

		return $this->seed();
	}

	/**
	 * Allow administrator to trigger seeding manually via URL parameter:
	 * /wp-admin/?seed_project_showcase=1&_wpnonce=...
	 *
	 * @return void
	 */
	public function maybe_seed_via_admin() {
		if (
			isset( $_GET['seed_project_showcase'] ) &&
			'1' === $_GET['seed_project_showcase'] &&
			current_user_can( 'manage_options' )
		) {
			check_admin_referer( 'seed_project_showcase_action' );
			$count = $this->seed();
			wp_safe_redirect(
				add_query_arg(
					array(
						'post_type'    => 'project',
						'seeded_count' => $count,
					),
					admin_url( 'edit.php' )
				)
			);
			exit;
		}
	}

	/**
	 * Insert sample projects, assign industry terms, custom metadata, and featured images.
	 *
	 * Idempotent: checks for existing projects by title before creating new entries.
	 *
	 * @return int Number of newly inserted or refreshed projects.
	 */
	public function seed() {
		$samples = $this->get_sample_data();
		$seeded  = 0;

		foreach ( $samples as $item ) {
			// Check if project with same title already exists.
			$existing_post = get_page_by_title( $item['title'], OBJECT, 'project' );
			if ( $existing_post ) {
				$post_id = $existing_post->ID;
			} else {
				// Insert post.
				$post_id = wp_insert_post(
					array(
						'post_title'   => $item['title'],
						'post_name'    => sanitize_title( $item['title'] ),
						'post_content' => $item['content'],
						'post_excerpt' => $item['excerpt'],
						'post_status'  => 'publish',
						'post_type'    => 'project',
					)
				);
			}

			if ( ! is_wp_error( $post_id ) && $post_id > 0 ) {
				// 1. Assign Industry taxonomy term.
				if ( ! empty( $item['industry'] ) ) {
					$term = term_exists( $item['industry'], 'industry' );
					if ( ! $term ) {
						$term = wp_insert_term( $item['industry'], 'industry' );
					}
					if ( ! is_wp_error( $term ) ) {
						$term_id = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
						wp_set_object_terms( $post_id, array( $term_id ), 'industry' );
					}
				}

				// 2. Assign Custom Metadata fields.
				update_post_meta( $post_id, '_project_client_name', sanitize_text_field( $item['client_name'] ) );
				update_post_meta( $post_id, '_project_url', esc_url_raw( $item['project_url'] ) );
				update_post_meta( $post_id, '_project_tech_stack', sanitize_text_field( $item['tech_stack'] ) );
				update_post_meta( $post_id, '_project_launch_date', sanitize_text_field( $item['launch_date'] ) );

				// 3. Attach reliable local featured image if present.
				if ( ! empty( $item['image_file'] ) ) {
					$this->attach_featured_image( $post_id, $item['image_file'], $item['title'] );
				}

				$seeded++;
			}
		}

		// Invalidate cache immediately after seeding.
		$cache = new Project_Showcase_Cache();
		$cache->invalidate();

		return $seeded;
	}

	/**
	 * Attach a local asset file as the post's featured thumbnail.
	 *
	 * Idempotent: searches existing attachments by title before creating duplicates.
	 *
	 * @param int    $post_id        Target project post ID.
	 * @param string $image_filename Filename located inside assets/images/.
	 * @param string $image_title    Descriptive title for attachment and alt text.
	 * @return int|false Attachment ID or false on failure.
	 */
	public function attach_featured_image( $post_id, $image_filename, $image_title ) {
		$source_path = PROJECT_SHOWCASE_DIR . 'assets/images/' . $image_filename;

		if ( ! file_exists( $source_path ) ) {
			return false;
		}

		// Check if this image has already been attached to the post.
		if ( has_post_thumbnail( $post_id ) ) {
			return get_post_thumbnail_id( $post_id );
		}

		// Check if an attachment with the same title already exists in the media library.
		$existing_attachment = get_page_by_title( $image_title . ' Cover', OBJECT, 'attachment' );
		if ( $existing_attachment ) {
			set_post_thumbnail( $post_id, $existing_attachment->ID );
			return $existing_attachment->ID;
		}

		// Read file contents.
		$file_content = file_get_contents( $source_path );
		if ( false === $file_content ) {
			return false;
		}

		// Upload into active WordPress uploads directory.
		$upload = wp_upload_bits( $image_filename, null, $file_content );
		if ( ! empty( $upload['error'] ) ) {
			return false;
		}

		// Detect MIME type.
		$file_type = wp_check_filetype( $upload['file'] );
		$mime_type = $file_type['type'] ? $file_type['type'] : 'image/svg+xml';

		// Prepare attachment object.
		$attachment_data = array(
			'guid'           => $upload['url'],
			'post_mime_type' => $mime_type,
			'post_title'     => sanitize_text_field( $image_title . ' Cover' ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		// Insert attachment into database.
		$attach_id = wp_insert_attachment( $attachment_data, $upload['file'], $post_id );
		if ( is_wp_error( $attach_id ) || ! $attach_id ) {
			return false;
		}

		// Generate attachment metadata if image function is available.
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		// Set alt text.
		update_post_meta( $attach_id, '_wp_attachment_image_alt', sanitize_text_field( $image_title ) );

		// Set as featured image for the project.
		set_post_thumbnail( $post_id, $attach_id );

		return $attach_id;
	}

	/**
	 * Get dataset of 10 demonstration projects spanning varied industries and technologies.
	 *
	 * @return array
	 */
	public function get_sample_data() {
		return array(
			array(
				'title'       => 'Apex Ledger: Multi-Currency Cloud Treasury',
				'industry'    => 'FinTech',
				'client_name' => 'Apex Global Capital',
				'project_url' => 'https://apexledger.example.com',
				'tech_stack'  => 'React, TypeScript, GraphQL, Node.js, PostgreSQL',
				'launch_date' => '2024-03-15',
				'image_file'  => 'fintech-ledger.svg',
				'excerpt'     => 'An institutional multi-currency digital treasury portal enabling sub-second ledger reconciliation and automated SWIFT transaction reporting.',
				'content'     => '<p>Apex Ledger is an enterprise treasury management platform built for multi-national financial institutions. It provides real-time cross-border currency conversion, institutional asset custody tracking, and auditable ledger verification.</p><h3>Key Architectural Highlights</h3><ul><li>Sub-second ledger synchronization via event-driven architecture.</li><li>Comprehensive compliance reporting with automated AML checking.</li><li>Bank-grade security featuring hardware security module (HSM) key storage.</li></ul>',
			),
			array(
				'title'       => 'HealthPulse: Telehealth & Remote Patient Portal',
				'industry'    => 'Healthcare',
				'client_name' => 'Metropolitan Health Network',
				'project_url' => 'https://healthpulse.example.com',
				'tech_stack'  => 'React, TypeScript, WebRTC, Tailwind CSS, Python, Redis',
				'launch_date' => '2024-05-20',
				'image_file'  => 'healthpulse-portal.svg',
				'excerpt'     => 'HIPAA-compliant virtual clinic suite with encrypted WebRTC video visits, EHR integration, and biometric device sync.',
				'content'     => '<p>HealthPulse connects over 120,000 active patients with certified clinical specialists. The web application streamlines intake triage, symptom assessments, prescription renewals, and live HD video consultations.</p><h3>Key Architectural Highlights</h3><ul><li>Zero-latency encrypted peer-to-peer consultation rooms.</li><li>Seamless bi-directional integration with Epic and Cerner EHR APIs.</li><li>Accessible WCAG 2.1 AAA compliant patient UI.</li></ul>',
			),
			array(
				'title'       => 'EcoFleet: Electric Telemetry & Route Optimization',
				'industry'    => 'Logistics',
				'client_name' => 'GreenTransit Freight',
				'project_url' => 'https://ecofleet.example.com',
				'tech_stack'  => 'React, Mapbox GL, TypeScript, Golang, MQTT, TimescaleDB',
				'launch_date' => '2023-11-10',
				'image_file'  => 'ecofleet-logistics.svg',
				'excerpt'     => 'Real-time fleet monitoring and battery thermal management system that cut commercial delivery fuel overhead by 24%.',
				'content'     => '<p>EcoFleet monitors 1,500 commercial electric transport vans across North America. Real-time CAN bus telemetry informs predictive battery degradation modeling and dynamic charging stop scheduling.</p><h3>Key Architectural Highlights</h3><ul><li>Interactive vector map rendering 50,000 concurrent GPS pings per second.</li><li>Dynamic route recalculation based on terrain elevation and battery state-of-charge.</li><li>Automated driver safety analytics and maintenance alert dispatch.</li></ul>',
			),
			array(
				'title'       => 'NexaLearn: Collaborative AI Campus',
				'industry'    => 'EdTech',
				'client_name' => 'Horizon Virtual University',
				'project_url' => 'https://nexalearn.example.com',
				'tech_stack'  => 'React, Next.js, WebSockets, Python, MySQL, Redis',
				'launch_date' => '2024-01-30',
				'image_file'  => 'nexalearn-campus.svg',
				'excerpt'     => 'Interactive virtual campus platform featuring live collaborative code sandboxes, peer study pods, and contextual AI study coaches.',
				'content'     => '<p>NexaLearn empowers online computer science students to collaborate in synchronized web IDEs and interactive lecture auditoriums. Context-aware AI tutors assist students during complex homework assignments.</p><h3>Key Architectural Highlights</h3><ul><li>Conflict-free replicated data types (CRDTs) powering live multi-user editor.</li><li>Low-latency real-time voice pods for student study groups.</li><li>Automated grading pipeline with isolated Docker execution sandboxes.</li></ul>',
			),
			array(
				'title'       => 'CyberShield: Zero-Trust SIEM Threat Intelligence',
				'industry'    => 'Cybersecurity',
				'client_name' => 'Sentinel Defense Systems',
				'project_url' => 'https://cybershield.example.com',
				'tech_stack'  => 'React, TypeScript, D3.js, Rust, Kafka, ClickHouse',
				'launch_date' => '2024-06-12',
				'image_file'  => 'cybershield-siem.svg',
				'excerpt'     => 'Enterprise SOC dashboard providing automated attack vector graph visualization, packet anomaly detection, and instant policy isolation.',
				'content'     => '<p>CyberShield centralizes security signals from endpoints, firewalls, and Kubernetes clusters into a unified incident command console. Security engineers can isolate infected nodes with a single click.</p><h3>Key Architectural Highlights</h3><ul><li>D3.js force-directed graph illustrating attack vectors in real-time.</li><li>Rust-powered log ingestion engine ingesting 2.4 GB/s of syslog streams.</li><li>Automated playbooks for instant cloud network egress isolation.</li></ul>',
			),
			array(
				'title'       => 'Velox OmniCart: Edge Headless Commerce',
				'industry'    => 'E-Commerce',
				'client_name' => 'Velox Apparel Group',
				'project_url' => 'https://omnicart.example.com',
				'tech_stack'  => 'React, TypeScript, GraphQL, Shopify Storefront API, Cloudflare Workers',
				'launch_date' => '2023-09-05',
				'image_file'  => 'velox-omnicart.svg',
				'excerpt'     => 'High-converting headless storefront delivering sub-200ms page transitions, edge geolocation pricing, and seamless Apple Pay checkout.',
				'content'     => '<p>Velox OmniCart was engineered to withstand viral Black Friday traffic surges without degradation. It decouples product browsing from checkout processing using edge computing and localized caching.</p><h3>Key Architectural Highlights</h3><ul><li>99+ Google Lighthouse performance scores across all mobile product pages.</li><li>Edge-computed multi-currency pricing and localized inventory estimation.</li><li>Instant optimistic cart drawer updates and single-step biometric checkout.</li></ul>',
			),
			array(
				'title'       => 'SolarNova: Smart Distributed Grid Analytics',
				'industry'    => 'CleanTech',
				'client_name' => 'SolarNova Energy Cooperative',
				'project_url' => 'https://gridnova.example.com',
				'tech_stack'  => 'React, TypeScript, Chart.js, Express, InfluxDB, AWS IoT',
				'launch_date' => '2024-04-18',
				'image_file'  => 'solarnova-grid.svg',
				'excerpt'     => 'Micro-grid monitoring software visualizing solar panel array output, commercial battery storage, and automated net-metering yields.',
				'content'     => '<p>SolarNova is utilized by regional clean power cooperatives to manage solar microgrids. The system uses weather satellite forecasts to predict photovoltaic generation 48 hours in advance.</p><h3>Key Architectural Highlights</h3><ul><li>Time-series analytics visualizing power production down to individual panel strings.</li><li>Automated battery charge/discharge switching based on peak utility tariffs.</li><li>Real-time automated alerting for inverter anomalies and shading losses.</li></ul>',
			),
			array(
				'title'       => 'AutoPulse: Vehicle Telematics & Diagnostics',
				'industry'    => 'Automotive',
				'client_name' => 'Vantage Motor Corp',
				'project_url' => 'https://autodrive.example.com',
				'tech_stack'  => 'React, TypeScript, Python, FastAPI, ClickHouse, Docker',
				'launch_date' => '2023-12-01',
				'image_file'  => 'autopulse-telematics.svg',
				'excerpt'     => 'Connected vehicle diagnostic hub enabling fleet managers to monitor engine vibration, OBD-II error codes, and preventative service intervals.',
				'content'     => '<p>AutoPulse aggregates diagnostic trouble codes (DTCs) from commercial vehicle fleets. Machine learning models analyze acoustic and vibration sensors to forecast alternator and brake rotor failures weeks in advance.</p><h3>Key Architectural Highlights</h3><ul><li>Instant OBD-II code decoding and step-by-step repair technician walk-throughs.</li><li>Automated spare parts warranty claim submission and inventory reservation.</li><li>Interactive vehicle health scoring dashboard with exportable PDF audit logs.</li></ul>',
			),
			array(
				'title'       => 'AeroSky Cargo: Real-Time Air Freight Tracker',
				'industry'    => 'Logistics',
				'client_name' => 'AeroSky Worldwide Logistics',
				'project_url' => 'https://aerosky.example.com',
				'tech_stack'  => 'React, TypeScript, Mapbox GL, Go, gRPC, Redis',
				'launch_date' => '2024-02-14',
				'image_file'  => 'aerosky-cargo.svg',
				'excerpt'     => 'International aviation cargo dispatch portal tracking unit load devices (ULDs), temperature-sensitive pharmaceuticals, and customs clearances.',
				'content'     => '<p>AeroSky Cargo synchronizes flight cargo manifests across 40 international air hubs. Cold-chain sensors broadcast temperature and pressure alerts via satellite uplinks directly to ramp agents.</p><h3>Key Architectural Highlights</h3><ul><li>Real-time aircraft cargo hold balance and ULD weight calculation.</li><li>Cold-chain compliance temperature log certification for pharmaceutical shipments.</li><li>Multi-tenant ground handler interface with offline-first synchronization.</li></ul>',
			),
			array(
				'title'       => 'NeuralVision: Edge Defect Detection Engine',
				'industry'    => 'Artificial Intelligence',
				'client_name' => 'Synthetix Industrial Robotics',
				'project_url' => 'https://neuralvision.example.com',
				'tech_stack'  => 'React, TypeScript, Python, PyTorch, WebGL, Docker, CUDA',
				'launch_date' => '2024-07-08',
				'image_file'  => 'neuralvision-ai.svg',
				'excerpt'     => 'High-speed industrial machine vision dashboard flagging silicon wafer microscopic anomalies at 120 frames per second.',
				'content'     => '<p>NeuralVision equips manufacturing semiconductor fabs with computer vision models. High-resolution sensor cameras detect surface fractures and particulate contamination in real-time.</p><h3>Key Architectural Highlights</h3><ul><li>Hardware-accelerated WebGL wafer inspection heatmap rendering.</li><li>Sub-8ms inference latency running directly on factory floor edge compute nodes.</li><li>Model training feedback loop allowing QA engineers to label false positives in 1 click.</li></ul>',
			),
		);
	}
}
