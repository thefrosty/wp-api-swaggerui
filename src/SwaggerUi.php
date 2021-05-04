<?php declare(strict_types=1);

namespace TheFrosty\WpApiSwaggerUi;

use TheFrosty\WpApiSwaggerUi\Models\Swagger;
use TheFrosty\WpUtilities\Plugin\HooksTrait;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;

/**
 * Class SwaggerUi
 *
 * @package TheFrosty\WpApiSwaggerUi
 */
class SwaggerUi implements WpHooksInterface
{

    use HooksTrait;

    public const DOCS = 'docs';
    public const QUERY_VAR = 'swagger_api';
    public const SCHEMA = 'schema';

    public static function getRewriteBaseApi(): string
    {
        return \strval(\apply_filters('swagger_api_rewrite_api_base', 'rest-api'));
    }

    public static function getNameSpace(): string
    {
        return '/' . \trim(\get_option('swagger_api_basepath', '/wp/v2'), '/');
    }

    public static function getSecurityDefinitions(): ?array
    {
        return \apply_filters('swagger_api_security_definitions', null);
    }

    public function addHooks(): void
    {
        $this->addAction('init', [$this, 'registerRoutes']);
        $this->addAction('wp', [$this, 'loadSwaggerSchema']);
    }

    protected function registerRoutes(): void
    {
        \add_rewrite_tag(\sprintf('%1$s%2$s%1$s', '%', self::QUERY_VAR), '([^&]+)');
        $this->addRewriteRule(self::DOCS);
        $this->addRewriteRule(self::SCHEMA);
    }

    protected function loadSwaggerSchema(): void
    {
        if (\get_query_var(self::QUERY_VAR) !== self::SCHEMA) {
            return;
        }

        $fields = [
            Swagger::FIELD_SWAGGER_VERSION => '2.0',
            Swagger::FIELD_INFO => [
                'title' => \esc_html(get_option('blogname')),
                'description' => \esc_html(\get_option('blogdescription')),
                'version' => \esc_html($GLOBALS['wp_version']),
                'contact' => [
                    'email' => \sanitize_email(\get_option('admin_email')),
                ],
            ],
            Swagger::FIELD_HOST => \home_url(),
            Swagger::FIELD_BASE_PATH => \home_url(),
            Swagger::FIELD_TAGS => \array_filter(\apply_filters('swagger_api_swagger_tags', [])),
            Swagger::FIELD_SCHEMAS => \array_filter(\apply_filters('swagger_api_swagger_schemas', ['http'])),
            Swagger::FIELD_PATHS => null,
            Swagger::FIELD_SECURITY_DEFINITIONS => self::getSecurityDefinitions(),
        ];

        \wp_send_json((new Swagger($fields))->toArray());
    }

    /**
     * @param string $query_var
     */
    private function addRewriteRule(string $query_var): void
    {
        \add_rewrite_rule(
            \sprintf('^%1$s/%2$s/?', self::getRewriteBaseApi(), $query_var),
            \sprintf('index.php?%s=%s', self::QUERY_VAR, $query_var),
            'top'
        );
    }
}
