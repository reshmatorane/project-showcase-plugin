=== Project Showcase ===
Contributors: senior-engineer
Tags: portfolio, project-showcase, rest-api, react, custom-post-type, transient-cache
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A technical full-stack project showcase plugin featuring custom post types, hierarchical industry taxonomy, secure meta box, transient caching, and high-performance REST API endpoints.

== Description ==

Project Showcase is built for technical portfolio demonstrations. It provides:
* **Custom Post Type**: `project` with support for title, editor, excerpt, thumbnail, and revisions.
* **Hierarchical Taxonomy**: `industry` for categorizing projects by market segment.
* **Custom Meta Box**: Secure input fields for Client Name, Project URL, Tech Stack, and Launch Date.
* **Security First**: WP nonces, user capability checks (`edit_post`), autosave protection, data sanitization, URL validation, and output escaping.
* **REST API**: Public endpoints at `GET /wp-json/showcase/v1/projects` and `GET /wp-json/showcase/v1/industries` with debounced search, industry filtering, and pagination envelopes.
* **Transient Caching**: Automated transient caching keyed by query parameters, with safe cache invalidation hooks on post, meta, image, and taxonomy updates.
* **Built-in Seeder**: Pre-populates 8 realistic, rich sample projects across multiple industries.

== Installation ==

1. Upload the `project-showcase` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Upon activation, the `project` CPT and `industry` taxonomy will register, rewrite rules will flush, and 8 sample projects will be seeded automatically.
4. Visit `GET /wp-json/showcase/v1/projects` to test the REST API.

== Endpoints ==

* `GET /wp-json/showcase/v1/projects` - Parameters: `search`, `industry`, `page`, `per_page`, `orderby`, `order`.
* `GET /wp-json/showcase/v1/industries` - Returns all registered industries with project counts.

== Changelog ==

= 1.0.0 =
* Initial release with CPT, taxonomy, meta box, transient caching, sample seeder, and REST API.
