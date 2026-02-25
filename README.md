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
	]
);

$vite->register_entry( 'my-plugin-admin', 'src/admin.js', [], true );
add_action( 'admin_enqueue_scripts', fn() => $vite->enqueue_entry( 'my-plugin-admin' ) );
```

- **manifest**: Production assets are read from `build/manifest.json` (Vite default).
- **static**: Production assets are loaded from `build/assets/{key}.js` and `build/assets/{key}.css`. Pass an optional 5th argument (static key) so dev uses the entry path (e.g. `src/admin.js`) and prod uses the short key (e.g. `admin`):

```php
// Static mode with short keys (matches common Vite input keys).
$vite->register_entry( 'my-plugin-admin', 'src/admin.js', [], true, 'admin' );
$vite->register_entry( 'my-plugin-front', 'src/front.js', [], true, 'front' );
```

Vite config for static with short keys:

```js
import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
  build: {
    outDir: 'build',
    rollupOptions: {
      input: {
        admin: resolve(__dirname, 'src/admin.js'),
        front: resolve(__dirname, 'src/front.js'),
      },
      output: {
        entryFileNames: 'assets/[name].js',
        chunkFileNames: 'assets/[name].js',
        assetFileNames: 'assets/[name].[ext]',
      },
    },
  },
});
```

## Scripts

- `composer lint` — PHPCS
- `composer format` — PHPCS auto-fix
- `composer test` — PHPUnit

## License

MIT
