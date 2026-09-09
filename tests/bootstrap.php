<?php

/**
 * Test bootstrap for the plugin's minimal unit test suite.
 *
 * Deliberately does NOT boot a Shopware kernel and does NOT depend on
 * shopware/core or shopware/storefront being installed anywhere. The tests
 * in tests/Unit only exercise pure PHP logic (Helper\Transaction) or a
 * single private method of HeyLightApiService reached via reflection
 * (see tests/Unit/Service/HeyLightApiServiceStatusParsingTest.php), so all
 * that's needed here is:
 *  - PHPUnit + psr/log, installed standalone into tests/vendor (see
 *    tests/composer.json) so this never touches/require the main
 *    plugin composer.json or the parent Shopware project's vendor tree.
 *  - The plugin's own PSR-4 autoloading (Go2FlowHeyLightPayment\ => src/),
 *    replicated manually below instead of running a full `composer install`
 *    for the plugin itself (which would otherwise try to resolve
 *    shopware/core and shopware/storefront from the plugin's composer.json
 *    "require" section).
 */

require __DIR__ . '/vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'Go2FlowHeyLightPayment\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});
