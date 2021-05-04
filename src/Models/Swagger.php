<?php declare(strict_types=1);

namespace TheFrosty\WpApiSwaggerUi\Models;

use TheFrosty\WpUtilities\Models\BaseModel;

/**
 * Class Swagger
 * @package TheFrosty\WpApiSwaggerUi\Models
 */
class Swagger extends BaseModel
{

    use SwaggerApi;

    public const DEFAULT_OPEN_API_VERSION = '2.0';
    public const FIELD_BASE_PATH = 'basePath';
    public const FIELD_HOST = 'host';
    public const FIELD_INFO = 'info';
    public const FIELD_PATHS = 'paths';
    public const FIELD_SCHEMAS = 'schemas';
    public const FIELD_SECURITY_DEFINITIONS = 'securityDefinitions';
    public const FIELD_SWAGGER_VERSION = 'swagger';
    public const FIELD_TAGS = 'tags';

    private ?string $swagger;
    private SwaggerInfo $swagger_info;
    private string $host;
    private ?string $base_path;
    private array $schemas;
    private array $paths;

    public function getSwagger(): string
    {
        return $this->swagger;
    }

    protected function setSwagger(?string $swagger = null): void
    {
        $this->swagger = $swagger ?? self::DEFAULT_OPEN_API_VERSION;
    }

    /**
     * @return SwaggerInfo
     */
    public function getInfo(): SwaggerInfo
    {
        return $this->swagger_info;
    }

    /**
     * @param array $fields
     */
    protected function setInfo(array $fields): void
    {
        $this->swagger_info = new SwaggerInfo($fields);
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    protected function setHost(string $url): void
    {
        $host = \parse_url($url, \PHP_URL_HOST);
        $port = \parse_url($url, \PHP_URL_PORT);

        if ($port && $port !== 80 && $port !== 443) {
            $this->host = \sprintf('%1$s:%2$s', $host, $port);
        }
    }

    public function getBasePath(): string
    {
        return $this->base_path;
    }

    protected function setBasePath(?string $base_path = null): void
    {
        $path = \parse_url($base_path ?? $this->getHost(), \PHP_URL_PATH);

        $this->base_path = \rtrim($path, '/') . '/' . \ltrim(\rest_get_url_prefix(), '/');
    }

    /**
     * @return array
     */
    public function getSchemes(): array
    {
        return \array_unique($this->schemas);
    }

    /**
     * @param array $schemas
     */
    protected function setSchemas(array $schemas): void
    {
        $this->schemas = $schemas;
        if (\is_ssl() && !\array_key_exists('https', \array_flip($schemas))) {
            $this->schemas[] = 'https';
        }
    }

    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * @param array|null $paths
     */
    protected function setPaths(?array $paths = null): void
    {
        $paths = $paths ?? [];
        $raw = $this->getRawPaths();
        foreach ($raw as $endpoint => $args) {
            $ep = $this->convertEndpoint($endpoint);
            $paths[$ep] = $this->getMethodsFromArgs($ep, $endpoint, $args);
        }
        $this->paths = $paths;
    }

    /**
     * @return string[]
     */
    protected function getSerializableFields(): array
    {
        return [
            self::FIELD_BASE_PATH,
            self::FIELD_HOST,
            self::FIELD_INFO,
            self::FIELD_PATHS,
            self::FIELD_SCHEMAS,
            self::FIELD_SECURITY_DEFINITIONS,
            self::FIELD_SWAGGER_VERSION,
            self::FIELD_TAGS,
        ];
    }
}
