<?php
/**
 * Tests for the ViteHelper class.
 *
 * @package Nilambar\Vitbolt
 */

use Nilambar\Vitbolt\ViteHelper;
use PHPUnit\Framework\TestCase;

/**
 * ViteHelper test case.
 */
class ViteHelperTest extends TestCase {

	/**
	 * Path to fixtures directory (with trailing slash).
	 *
	 * @var string
	 */
	private $fixtures_path;

	/**
	 * URL for fixtures (with trailing slash).
	 *
	 * @var string
	 */
	private $fixtures_url = 'https://example.com/wp-content/plugins/my-plugin/';

	/**
	 * Reset globals before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->fixtures_path = __DIR__ . '/fixtures/';
		$GLOBALS['wp_remote_get_response']   = null;
		$GLOBALS['wp_enqueue_script_calls']   = array();
		$GLOBALS['wp_enqueue_style_calls']   = array();
	}

	// -------------------------------------------------------------------------
	// Constructor / options.
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function constructor_normalizes_plugin_url_and_path_with_trailing_slash() {
		$vite = new ViteHelper(
			'my-plugin',
			'https://example.com/plugin',
			'/var/www/plugin',
			array( 'output_pattern' => 'static' )
		);

		$this->assertSame( 'https://example.com/plugin/', $this->get_plugin_url( $vite ) );
		$this->assertSame( '/var/www/plugin/', $this->get_plugin_path( $vite ) );
	}

	/**
	 * @test
	 */
	public function constructor_merges_options_with_defaults() {
		$vite = new ViteHelper(
			'my-plugin',
			$this->fixtures_url,
			$this->fixtures_path,
			array(
				'output_pattern' => 'static',
				'build_dir'      => 'dist',
				'dev_server_url' => 'http://localhost:3000',
			)
		);

		$this->assertSame( 'dist', $this->get_build_dir( $vite ) );
		$this->assertSame( 'http://localhost:3000', $this->get_dev_server_url( $vite ) );
	}

	// -------------------------------------------------------------------------
	// is_dev_server_running().
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function is_dev_server_running_returns_true_when_response_is_200() {
		$GLOBALS['wp_remote_get_response'] = array(
			'response' => array( 'code' => 200 ),
			'body'     => '',
		);

		$vite = new ViteHelper( 'my-plugin', $this->fixtures_url, $this->fixtures_path, array( 'output_pattern' => 'static' ) );

		$this->assertTrue( $vite->is_dev_server_running() );
	}

	/**
	 * @test
	 */
	public function is_dev_server_running_returns_false_on_wp_error() {
		$GLOBALS['wp_remote_get_response'] = new WP_Error();

		$vite = new ViteHelper( 'my-plugin', $this->fixtures_url, $this->fixtures_path, array( 'output_pattern' => 'static' ) );

		$this->assertFalse( $vite->is_dev_server_running() );
	}

	/**
	 * @test
	 */
	public function is_dev_server_running_returns_false_on_non_200_response() {
		$GLOBALS['wp_remote_get_response'] = array(
			'response' => array( 'code' => 404 ),
			'body'     => '',
		);

		$vite = new ViteHelper( 'my-plugin', $this->fixtures_url, $this->fixtures_path, array( 'output_pattern' => 'static' ) );

		$this->assertFalse( $vite->is_dev_server_running() );
	}

	/**
	 * @test
	 */
	public function is_dev_server_running_caches_result() {
		$GLOBALS['wp_remote_get_response'] = array(
			'response' => array( 'code' => 200 ),
			'body'     => '',
		);

		$vite = new ViteHelper( 'my-plugin', $this->fixtures_url, $this->fixtures_path, array( 'output_pattern' => 'static' ) );

		$this->assertTrue( $vite->is_dev_server_running() );
		$this->assertTrue( $vite->is_dev_server_running() );
	}

	// -------------------------------------------------------------------------
	// get_asset_url() – static mode.
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function get_asset_url_static_returns_default_js_url_when_no_static_files() {
		$vite = new ViteHelper(
			'my-plugin',
			$this->fixtures_url,
			$this->fixtures_path,
			array(
				'output_pattern' => 'static',
				'build_dir'      => 'build',
			)
		);

		$url = $vite->get_asset_url( 'admin', 'js' );

		$this->assertSame( $this->fixtures_url . 'build/assets/admin.js', $url );
	}

	/**
	 * @test
	 */
	public function get_asset_url_static_returns_default_css_url_when_no_static_files() {
		$vite = new ViteHelper(
			'my-plugin',
			$this->fixtures_url,
			$this->fixtures_path,
			array(
				'output_pattern' => 'static',
				'build_dir'      => 'build',
			)
		);

		$url = $vite->get_asset_url( 'admin', 'css' );

		$this->assertSame( $this->fixtures_url . 'build/assets/admin.css', $url );
	}

	/**
	 * @test
	 */
	public function get_asset_url_static_uses_static_files_mapping_when_provided() {
		$vite = new ViteHelper(
			'my-plugin',
			$this->fixtures_url,
			$this->fixtures_path,
			array(
				'output_pattern' => 'static',
				'build_dir'      => 'build',
				'static_files'   => array(
					'admin.js'  => 'admin-hashed.js',
					'admin.css' => 'admin-hashed.css',
				),
			)
		);

		$this->assertSame( $this->fixtures_url . 'build/admin-hashed.js', $vite->get_asset_url( 'admin', 'js' ) );
		$this->assertSame( $this->fixtures_url . 'build/admin-hashed.css', $vite->get_asset_url( 'admin', 'css' ) );
	}

	// -------------------------------------------------------------------------
	// get_asset_url() – manifest mode.
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function get_asset_url_manifest_returns_js_url_from_manifest() {
		$vite = new ViteHelper(
			'my-plugin',
			$this->fixtures_url,
			$this->fixtures_path,
			array(
				'output_pattern' => 'manifest',
				'build_dir'      => '',
			)
		);

		$url = $vite->get_asset_url( 'src/admin.js', 'js' );

		$this->assertSame( $this->fixtures_url . '/assets/admin-abc123.js', $url );
	}

	/**
	 * @test
	 */
	public function get_asset_url_manifest_returns_first_css_url_from_manifest() {
		$vite = new ViteHelper(
			'my-plugin',
			$this->fixtures_url,
			$this->fixtures_path,
			array(
				'output_pattern' => 'manifest',
				'build_dir'      => '',
			)
		);

		$url = $vite->get_asset_url( 'src/admin.js', 'css' );

		$this->assertSame( $this->fixtures_url . '/assets/admin-def456.css', $url );
	}

	/**
	 * @test
	 */
	public function get_asset_url_manifest_returns_false_for_missing_entry() {
		$vite = new ViteHelper(
			'my-plugin',
			$this->fixtures_url,
			$this->fixtures_path,
			array(
				'output_pattern' => 'manifest',
				'build_dir'      => '',
			)
		);

		$this->assertFalse( $vite->get_asset_url( 'src/nonexistent.js', 'js' ) );
	}

	// -------------------------------------------------------------------------
	// get_entry_css_files().
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function get_entry_css_files_manifest_returns_all_css_from_manifest() {
		$vite = new ViteHelper(
			'my-plugin',
			$this->fixtures_url,
			$this->fixtures_path,
			array(
				'output_pattern' => 'manifest',
				'build_dir'      => '',
			)
		);

		$css = $vite->get_entry_css_files( 'src/front.js' );

		$this->assertCount( 2, $css );
		$this->assertSame( $this->fixtures_url . '/assets/front-a.css', $css[0] );
		$this->assertSame( $this->fixtures_url . '/assets/front-b.css', $css[1] );
	}

	/**
	 * @test
	 */
	public function get_entry_css_files_static_returns_single_css_url_when_exists() {
		$vite = new ViteHelper(
			'my-plugin',
			$this->fixtures_url,
			$this->fixtures_path,
			array(
				'output_pattern' => 'static',
				'build_dir'      => 'build',
			)
		);

		$css = $vite->get_entry_css_files( 'admin' );

		$this->assertCount( 1, $css );
		$this->assertSame( $this->fixtures_url . 'build/assets/admin.css', $css[0] );
	}

	// -------------------------------------------------------------------------
	// register_entry() / enqueue_entry().
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function register_entry_stores_entry_and_returns_self() {
		$vite = new ViteHelper( 'my-plugin', $this->fixtures_url, $this->fixtures_path, array( 'output_pattern' => 'static' ) );

		$result = $vite->register_entry( 'my-handle', 'src/admin.js', array( 'jquery' ), false );

		$this->assertSame( $vite, $result );
		$this->assertTrue( $vite->enqueue_entry( 'my-handle' ) );
	}

	/**
	 * @test
	 */
	public function enqueue_entry_returns_false_for_unregistered_handle() {
		$GLOBALS['wp_remote_get_response'] = array( 'response' => array( 'code' => 404 ) );

		$vite = new ViteHelper( 'my-plugin', $this->fixtures_url, $this->fixtures_path, array( 'output_pattern' => 'static' ) );

		$this->assertFalse( $vite->enqueue_entry( 'unknown-handle' ) );
	}

	/**
	 * @test
	 */
	public function enqueue_entry_production_static_enqueues_script_and_style() {
		$GLOBALS['wp_remote_get_response'] = array( 'response' => array( 'code' => 404 ) );

		$build_dir = $this->fixtures_path . 'build';
		if ( ! is_dir( $build_dir ) ) {
			mkdir( $build_dir, 0755, true );
		}

		$vite = new ViteHelper(
			'my-plugin',
			$this->fixtures_url,
			$this->fixtures_path,
			array(
				'output_pattern' => 'static',
				'build_dir'      => 'build',
			)
		);
		$vite->register_entry( 'my-admin', 'admin', array(), true );
		$vite->enqueue_entry( 'my-admin' );

		$this->assertNotEmpty( $GLOBALS['wp_enqueue_script_calls'] );
		$this->assertSame( 'my-admin', $GLOBALS['wp_enqueue_script_calls'][0]['handle'] );
		$this->assertStringContainsString( 'build/assets/admin.js', $GLOBALS['wp_enqueue_script_calls'][0]['src'] );
	}

	/**
	 * @test
	 */
	public function enqueue_entry_production_manifest_enqueues_script_and_styles_from_manifest() {
		$GLOBALS['wp_remote_get_response'] = array( 'response' => array( 'code' => 404 ) );

		$vite = new ViteHelper(
			'my-plugin',
			$this->fixtures_url,
			$this->fixtures_path,
			array(
				'output_pattern' => 'manifest',
				'build_dir'      => '',
			)
		);
		$vite->register_entry( 'my-admin', 'src/admin.js', array(), true );
		$vite->enqueue_entry( 'my-admin' );

		$this->assertNotEmpty( $GLOBALS['wp_enqueue_script_calls'] );
		$this->assertSame( 'my-admin', $GLOBALS['wp_enqueue_script_calls'][0]['handle'] );
		$this->assertStringContainsString( 'admin-abc123.js', $GLOBALS['wp_enqueue_script_calls'][0]['src'] );
		$this->assertNotEmpty( $GLOBALS['wp_enqueue_style_calls'] );
		$this->assertStringContainsString( 'admin-def456.css', $GLOBALS['wp_enqueue_style_calls'][0]['src'] );
	}

	// -------------------------------------------------------------------------
	// Helpers (reflection to access private properties).
	// -------------------------------------------------------------------------

	/**
	 * Get plugin_url from ViteHelper.
	 *
	 * @param ViteHelper $vite ViteHelper instance.
	 * @return string
	 */
	private function get_plugin_url( ViteHelper $vite ) {
		$prop = ( new ReflectionClass( $vite ) )->getProperty( 'plugin_url' );
		$prop->setAccessible( true );
		return $prop->getValue( $vite );
	}

	/**
	 * Get plugin_path from ViteHelper.
	 *
	 * @param ViteHelper $vite ViteHelper instance.
	 * @return string
	 */
	private function get_plugin_path( ViteHelper $vite ) {
		$prop = ( new ReflectionClass( $vite ) )->getProperty( 'plugin_path' );
		$prop->setAccessible( true );
		return $prop->getValue( $vite );
	}

	/**
	 * Get build_dir from ViteHelper.
	 *
	 * @param ViteHelper $vite ViteHelper instance.
	 * @return string
	 */
	private function get_build_dir( ViteHelper $vite ) {
		$prop = ( new ReflectionClass( $vite ) )->getProperty( 'build_dir' );
		$prop->setAccessible( true );
		return $prop->getValue( $vite );
	}

	/**
	 * Get dev_server_url from ViteHelper.
	 *
	 * @param ViteHelper $vite ViteHelper instance.
	 * @return string
	 */
	private function get_dev_server_url( ViteHelper $vite ) {
		$prop = ( new ReflectionClass( $vite ) )->getProperty( 'dev_server_url' );
		$prop->setAccessible( true );
		return $prop->getValue( $vite );
	}
}
