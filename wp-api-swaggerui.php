<?php declare(strict_types=1);

/**
 * WP API SwaggerUI
 *
 * @package WP API SwaggerUI
 * @author Austin Passy
 * @copyright 2021 Austin Passy
 * @license GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: WP API SwaggerUI
 * Description: WordPress REST API with Swagger UI.
 * Author: Austin Passy
 * Author URI: https://github.com/thefrosty
 * Version: 2.0.0
 * Requires at least: 5.6
 * Tested up to: 5.7.1
 * Requires PHP: 7.4
 * Plugin URI: https://github.com/thefrosty/wp-api-swaggerui
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace TheFrosty;

\defined('ABSPATH') || exit('These are not the docs you are looking for.');

const SLUG = 'wp-api-swagger-ui';

use TheFrosty\WpApiSwaggerUi\Auth;
use TheFrosty\WpApiSwaggerUi\ServiceProvider;
use TheFrosty\WpApiSwaggerUi\SwaggerUi;
use TheFrosty\WpApiSwaggerUi\Template;
use TheFrosty\WpUtilities\Plugin\PluginFactory;

$plugin = PluginFactory::create(SLUG);
/** Container object. @var \TheFrosty\WpUtilities\Plugin\Container $container */
$container = $plugin->getContainer();
$container->register(new ServiceProvider($plugin));
$plugin
    ->add(new Auth())
    ->add(new SwaggerUi())
    ->add(new Template())
    ->initialize();

\register_activation_hook(__FILE__, static function () use ($plugin): void {
    $hooks = $plugin->getInit()->getWpHooks();
    $swaggerUi = $hooks[\array_search(SwaggerUi::class, $hooks, true)] ?? null;
    if (!$swaggerUi) {
        return;
    }
    $routes = (new \ReflectionClass($swaggerUi))->getMethod('registerRoutes');
    $routes->setAccessible(true);
    $routes->invoke($swaggerUi);
    \flush_rewrite_rules();
});

\register_deactivation_hook(__FILE__, static function () use ($plugin): void {
    \flush_rewrite_rules();
});
