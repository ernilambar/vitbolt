# Vitbolt

Vite integration for WordPress plugins. Use the **manifest** (hashed build) or **static** (fixed filenames) approach.

## Install

```bash
composer require ernilambar/vitbolt
```

## Load

In your plugin bootstrap (e.g. before `wp_enqueue_scripts`):

```php
require_once __DIR__ . '/vendor/ernilambar/vitbolt/init.php';
```

## Usage

```php
use Nilambar\Vitbolt\ViteHelper;

$vite = new ViteHelper(
	'my-plugin',
	plugins_url( '', __FILE__ ),
	plugin_dir_path( __FILE__ ),
	[
		'dev_server_url' => 'http://localhost:5173',
		'build_dir'      => 'build',
		'output_pattern' => 'manifest', // or 'static'
		'static_files'   => [],         // for static: e.g. ['admin.js' => 'admin.js']
	]
);

$vite->register_entry( 'my-plugin-admin', 'src/admin.js', [], true );
add_action( 'admin_enqueue_scripts', fn() => $vite->enqueue_entry( 'my-plugin-admin' ) );
```

- **manifest**: Production assets are read from `build/manifest.json` (Vite default).
- **static**: Use `static_files` to map entry keys to built filenames, or rely on `build/assets/{entry}.js` / `build/assets/{entry}.css`.

## Scripts

- `composer lint` — PHPCS
- `composer format` — PHPCS auto-fix
- `composer test` — PHPUnit

## License

MIT
