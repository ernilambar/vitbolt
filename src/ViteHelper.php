<?php
/**
 * Vite helper for WordPress plugins.
 *
 * @package Nilambar\Vitbolt
 */

namespace Nilambar\Vitbolt;

if ( ! class_exists( \Nilambar\Vitbolt\ViteHelper::class ) ) {

	/**
	 * ViteHelper class.
	 *
	 * Enqueues Vite dev server or production assets.
	 *
	 * @since 1.0.0
	 */
	class ViteHelper {

		/**
		 * Plugin slug.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		private $plugin_slug;

		/**
		 * Plugin URL (trailing slash).
		 *
		 * @since 1.0.0
		 * @var string
		 */
		private $plugin_url;

		/**
		 * Plugin path (trailing slash).
		 *
		 * @since 1.0.0
		 * @var string
		 */
		private $plugin_path;

		/**
		 * Vite dev server URL.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		private $dev_server_url;

		/**
		 * Build directory name.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		private $build_dir;

		/**
		 * Path to manifest file.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		private $manifest_path;

		/**
		 * Cached manifest data.
		 *
		 * @since 1.0.0
		 * @var array|null
		 */
		private $manifest_data = null;

		/**
		 * Whether dev server is running (cached).
		 *
		 * @since 1.0.0
		 * @var bool|null
		 */
		private $is_dev_server_running = null;

		/**
		 * Registered entries (handle => entry config).
		 *
		 * @since 1.0.0
		 * @var array
		 */
		private $registered_entries = array();

		/**
		 * Output pattern: 'manifest' or 'static'.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		private $output_pattern;

		/**
		 * Timeout for dev server check (seconds).
		 *
		 * @since 1.0.0
		 * @var float
		 */
		private $check_server_timeout;

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 * @param string $plugin_slug Unique identifier for the plugin.
		 * @param string $plugin_url  Full URL to plugin directory.
		 * @param string $plugin_path Full filesystem path to plugin directory.
		 * @param array  $options     Optional configuration.
		 */
		public function __construct( $plugin_slug, $plugin_url, $plugin_path, $options = array() ) {
			$this->plugin_slug = $plugin_slug;
			$this->plugin_url  = trailingslashit( $plugin_url );
			$this->plugin_path = trailingslashit( $plugin_path );

			$defaults = array(
				'dev_server_url'       => 'http://localhost:5173',
				'build_dir'            => 'build',
				'manifest_file'        => 'manifest.json',
				'check_server_timeout' => 0.5,
				'output_pattern'       => 'manifest',
			);

			$options = wp_parse_args( $options, $defaults );

			$this->dev_server_url       = rtrim( $options['dev_server_url'], '/' );
			$this->build_dir            = trim( $options['build_dir'], '/' );
			$this->manifest_path        = $this->plugin_path . $this->build_dir . '/' . ltrim( $options['manifest_file'], '/' );
			$this->check_server_timeout = $options['check_server_timeout'];
			$this->output_pattern       = $options['output_pattern'];
		}

		/**
		 * Check if Vite dev server is running.
		 *
		 * @since 1.0.0
		 * @return bool
		 */
		public function is_dev_server_running() {
			if ( null !== $this->is_dev_server_running ) {
				return $this->is_dev_server_running;
			}

			$response = wp_remote_get(
				$this->dev_server_url,
				array(
					'timeout' => $this->check_server_timeout,
					'headers' => array(
						'Accept' => 'application/json',
					),
				)
			);

			$this->is_dev_server_running = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;

			return $this->is_dev_server_running;
		}

		/**
		 * Get manifest data (manifest mode only).
		 *
		 * @since 1.0.0
		 * @return array|null
		 */
		private function get_manifest_data() {
			if ( 'manifest' !== $this->output_pattern ) {
				return null;
			}

			if ( null !== $this->manifest_data ) {
				return $this->manifest_data;
			}

			if ( ! file_exists( $this->manifest_path ) ) {
				return null;
			}

			$content = file_get_contents( $this->manifest_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( false === $content ) {
				return null;
			}

			$this->manifest_data = json_decode( $content, true );
			return $this->manifest_data;
		}

		/**
		 * Get asset URL for an entry.
		 *
		 * @since 1.0.0
		 * @param string $entry Entry identifier.
		 * @param string $type  Asset type: 'js' or 'css'.
		 * @return string|false
		 */
		public function get_asset_url( $entry, $type = 'js' ) {
			if ( 'manifest' === $this->output_pattern ) {
				return $this->get_asset_url_manifest( $entry, $type );
			}
			return $this->get_asset_url_static( $entry, $type );
		}

		/**
		 * Get asset URL from manifest.
		 *
		 * @since 1.0.0
		 * @param string $entry Entry key.
		 * @param string $type  'js' or 'css'.
		 * @return string|false
		 */
		private function get_asset_url_manifest( $entry, $type = 'js' ) {
			$manifest = $this->get_manifest_data();

			if ( ! $manifest || ! isset( $manifest[ $entry ] ) ) {
				return false;
			}

			$entry_data = $manifest[ $entry ];

			if ( 'js' === $type && isset( $entry_data['file'] ) ) {
				return $this->plugin_url . $this->build_dir . '/' . $entry_data['file'];
			}

			if ( 'css' === $type && isset( $entry_data['css'] ) && ! empty( $entry_data['css'] ) ) {
				return $this->plugin_url . $this->build_dir . '/' . $entry_data['css'][0];
			}

			return false;
		}

		/**
		 * Get asset URL for static output (build/assets/{entry}.js or .css).
		 *
		 * @since 1.0.0
		 * @param string $entry Entry key.
		 * @param string $type  'js' or 'css'.
		 * @return string
		 */
		private function get_asset_url_static( $entry, $type = 'js' ) {
			return $this->plugin_url . $this->build_dir . '/assets/' . $entry . '.' . $type;
		}

		/**
		 * Get all CSS URLs for an entry.
		 *
		 * @since 1.0.0
		 * @param string $entry Entry key.
		 * @return array
		 */
		public function get_entry_css_files( $entry ) {
			if ( 'manifest' === $this->output_pattern ) {
				$manifest = $this->get_manifest_data();

				if ( ! $manifest || ! isset( $manifest[ $entry ] ) || ! isset( $manifest[ $entry ]['css'] ) ) {
					return array();
				}

				return array_map(
					function ( $css_file ) {
						return $this->plugin_url . $this->build_dir . '/' . $css_file;
					},
					$manifest[ $entry ]['css']
				);
			}

			$css_url = $this->get_asset_url_static( $entry, 'css' );
			return $css_url ? array( $css_url ) : array();
		}

		/**
		 * Register an entry point.
		 *
		 * @since 1.0.0
		 * @param string   $handle    Script/style handle.
		 * @param string   $entry     Vite entry (e.g. 'src/admin.js').
		 * @param string[] $deps      Dependencies.
		 * @param bool     $in_footer Enqueue script in footer.
		 * @return self
		 */
		public function register_entry( $handle, $entry, $deps = array(), $in_footer = true ) {
			$this->registered_entries[ $handle ] = array(
				'entry'     => $entry,
				'deps'      => $deps,
				'in_footer' => $in_footer,
			);
			return $this;
		}

		/**
		 * Enqueue a registered entry.
		 *
		 * @since 1.0.0
		 * @param string $handle Registered handle.
		 * @return bool
		 */
		public function enqueue_entry( $handle ) {
			if ( ! isset( $this->registered_entries[ $handle ] ) ) {
				return false;
			}

			$entry_data = $this->registered_entries[ $handle ];
			return $this->enqueue(
				$entry_data['entry'],
				$handle,
				$entry_data['deps'],
				$entry_data['in_footer']
			);
		}

		/**
		 * Enqueue assets for an entry (dev or prod).
		 *
		 * @since 1.0.0
		 * @param string   $entry     Vite entry.
		 * @param string   $handle    Script handle.
		 * @param string[] $deps      Dependencies.
		 * @param bool     $in_footer Enqueue script in footer.
		 * @return bool
		 */
		public function enqueue( $entry, $handle, $deps = array(), $in_footer = true ) {
			if ( $this->is_dev_server_running() ) {
				return $this->enqueue_dev( $entry, $handle, $deps, $in_footer );
			}
			return $this->enqueue_prod( $entry, $handle, $deps, $in_footer );
		}

		/**
		 * Enqueue from Vite dev server.
		 *
		 * @since 1.0.0
		 * @param string   $entry     Entry path.
		 * @param string   $handle    Handle.
		 * @param string[] $deps      Dependencies.
		 * @param bool     $in_footer In footer.
		 * @return true
		 */
		private function enqueue_dev( $entry, $handle, $deps = array(), $in_footer = true ) {
			$vite_handle = $this->plugin_slug . '-vite-client';

			// Dev server: no version for cache busting; Vite serves latest.
			wp_enqueue_script(
				$vite_handle,
				$this->dev_server_url . '/@vite/client',
				array(),
				null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
				true
			);

			$deps[] = $vite_handle;

			$script_url = $this->dev_server_url . '/' . ltrim( $entry, '/' );

			wp_enqueue_script(
				$handle,
				$script_url,
				$deps,
				null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
				$in_footer
			);

			// Override tag to type="module" for Vite ES modules (script is enqueued above).
			add_filter(
				'script_loader_tag',
				function ( $tag, $enqueued_handle, $src ) use ( $handle ) {
					if ( $enqueued_handle === $handle || $enqueued_handle === $this->plugin_slug . '-vite-client' ) {
						$tag = '<script type="module" src="' . esc_url( $src ) . '"></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
					}
					return $tag;
				},
				10,
				3
			);

			return true;
		}

		/**
		 * Enqueue production assets.
		 *
		 * @since 1.0.0
		 * @param string   $entry     Entry key.
		 * @param string   $handle    Handle.
		 * @param string[] $deps      Dependencies.
		 * @param bool     $in_footer In footer.
		 * @return bool
		 */
		private function enqueue_prod( $entry, $handle, $deps = array(), $in_footer = true ) {
			if ( 'manifest' === $this->output_pattern ) {
				return $this->enqueue_prod_manifest( $entry, $handle, $deps, $in_footer );
			}
			return $this->enqueue_prod_static( $entry, $handle, $deps, $in_footer );
		}

		/**
		 * Enqueue production assets using manifest.
		 *
		 * @since 1.0.0
		 * @param string   $entry     Entry key.
		 * @param string   $handle    Handle.
		 * @param string[] $deps      Dependencies.
		 * @param bool     $in_footer In footer.
		 * @return bool
		 */
		private function enqueue_prod_manifest( $entry, $handle, $deps = array(), $in_footer = true ) {
			$manifest = $this->get_manifest_data();

			if ( ! $manifest || ! isset( $manifest[ $entry ] ) ) {
				return false;
			}

			$entry_data = $manifest[ $entry ];
			$version    = isset( $entry_data['file'] ) ? hash( 'crc32', $entry_data['file'] ) : null;

			if ( isset( $entry_data['file'] ) ) {
				wp_enqueue_script(
					$handle,
					$this->plugin_url . $this->build_dir . '/' . $entry_data['file'],
					$deps,
					$version,
					$in_footer
				);
			}

			if ( isset( $entry_data['css'] ) ) {
				foreach ( $entry_data['css'] as $index => $css_file ) {
					wp_enqueue_style(
						$handle . '-css-' . ( $index + 1 ),
						$this->plugin_url . $this->build_dir . '/' . $css_file,
						array(),
						$version
					);
				}
			}

			return true;
		}

		/**
		 * Enqueue production assets using static filenames.
		 *
		 * @since 1.0.0
		 * @param string   $entry     Entry key.
		 * @param string   $handle    Handle.
		 * @param string[] $deps      Dependencies.
		 * @param bool     $in_footer In footer.
		 * @return bool
		 */
		private function enqueue_prod_static( $entry, $handle, $deps = array(), $in_footer = true ) {
			$build_path = $this->plugin_path . $this->build_dir;
			$version    = file_exists( $build_path ) ? (string) filemtime( $build_path ) : null;

			$js_url = $this->get_asset_url_static( $entry, 'js' );
			if ( $js_url ) {
				wp_enqueue_script(
					$handle,
					$js_url,
					$deps,
					$version,
					$in_footer
				);
			}

			$css_url = $this->get_asset_url_static( $entry, 'css' );
			if ( $css_url ) {
				wp_enqueue_style(
					$handle . '-css',
					$css_url,
					array(),
					$version
				);
			}

			return true;
		}
	}
}
