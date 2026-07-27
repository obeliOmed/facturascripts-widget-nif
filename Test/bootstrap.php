<?php

declare(strict_types=1);

/**
 * Bootstrap for PHPUnit tests — WidgetNif.
 *
 * Autoloads FacturaScripts core (env FS_ROOT or default XAMPP path) plus a
 * lightweight PSR-4 autoloader for this plugin's own classes.
 *
 * PHPUnit itself is provided by the /GitHub workspace root
 * (../vendor/bin/phpunit). Run: `composer test` from this plugin folder,
 * or `../vendor/bin/phpunit -c phpunit.xml`.
 */

// 1. Workspace-root PHPUnit autoload
$rootAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (file_exists($rootAutoload)) {
    require_once $rootAutoload;
}

// 2. FacturaScripts core autoload (production runtime deps)
$fsRoot = getenv('FS_ROOT') ?: 'C:\\xampp\\htdocs\\facturas';
$fsAutoload = $fsRoot . '/vendor/autoload.php';

if (!file_exists($fsAutoload)) {
    fwrite(STDERR, "\n[bootstrap] FS autoload not found at: {$fsAutoload}\n");
    fwrite(STDERR, "[bootstrap] Set FS_ROOT env var to your FacturaScripts install path.\n\n");
    exit(1);
}

require_once $fsAutoload;

// 3. Plugin PSR-4 autoload (production classes)
$pluginRoot = dirname(__DIR__);
$pluginName = 'WidgetNif';
$pluginNamespace = 'FacturaScripts\\Plugins\\' . $pluginName . '\\';

spl_autoload_register(function (string $class) use ($pluginRoot, $pluginNamespace): void {
    if (!str_starts_with($class, $pluginNamespace)) {
        return;
    }
    $relative = substr($class, strlen($pluginNamespace));
    $path = $pluginRoot . '/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

// 4. Test PSR-4 autoload (Test\ namespace under plugin)
$testNamespace = 'FacturaScripts\\Plugins\\' . $pluginName . '\\Test\\';

spl_autoload_register(function (string $class) use ($pluginRoot, $testNamespace): void {
    if (!str_starts_with($class, $testNamespace)) {
        return;
    }
    $relative = substr($class, strlen($testNamespace));
    $path = $pluginRoot . '/Test/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});
