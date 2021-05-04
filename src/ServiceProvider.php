<?php declare(strict_types=1);

namespace TheFrosty\WpApiSwaggerUi;

use Pimple\Container as PimpleContainer;
use Pimple\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\WpUtilities\Plugin\Plugin;

/**
 * Class ServiceProvider
 * @package TheFrosty\WpApiSwaggerUi
 */
class ServiceProvider implements ServiceProviderInterface
{

    public const HTTP_FOUNDATION_REQUEST = 'http.request';

    /**
     * Plugin object passed by reference, since
     * this isn't initiated until a later hook by calling initiate().
     * @var Plugin $plugin
     */
    private Plugin $plugin;

    /**
     * ServiceProvider constructor.
     * @param Plugin $plugin
     */
    public function __construct(Plugin &$plugin)
    {
        $this->plugin = &$plugin;
    }

    /**
     * Register services.
     * @param PimpleContainer $pimple Container instance.
     */
    public function register(PimpleContainer $pimple): void
    {
        $pimple[self::HTTP_FOUNDATION_REQUEST] = fn(): Request => Request::createFromGlobals();
    }
}
