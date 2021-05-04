<?php declare(strict_types=1);

namespace TheFrosty\WpApiSwaggerUi\Models;

use TheFrosty\WpApiSwaggerUi\SwaggerUi;

/**
 * Trait SwaggerApi
 * @package TheFrosty\WpApiSwaggerUi\Models
 */
trait SwaggerApi
{

    protected function getRawPaths(): array
    {
        $routes = \rest_get_server()->get_routes();
        $basepath = SwaggerUi::getNameSpace();

        $raw_paths = [];
        foreach ($routes as $route => $value) {
            if (\mb_strpos($route, $basepath) === 0 && $basepath !== $route) {
                $raw_paths[$route] = $value;
            }
        }

        return $raw_paths;
    }

    protected function convertEndpoint($endpoint)
    {
        if (\mb_strpos($endpoint, '(?P<') !== false) {
            $endpoint = \preg_replace_callback(
                '/\(\?P<(.*?)>(.*)\)+/',
                fn($match): string => '{' . $match[1] . '}',
                $endpoint
            );
        }

        return $endpoint;
    }

    protected function getMethodsFromArgs($ep, $endpoint, $args): array
    {
        $methods = [];
        $path_parameters = $this->getParametersFromEndpoint($endpoint);
        $tags = $this->getDefaultTagsFromEndpoint($endpoint);

        foreach ($args as $arg) {
            $all_parameters = $this->getParametersFromArgs($ep, $arg['args'] ?? [], $arg['methods'] ?? []);
            foreach ($arg['methods'] as $method => $bool) {
                $mtd = \mb_strtolower($method);
                $methodEndpoint = $mtd . \str_replace('/', '_', $ep);
                $parameters = $all_parameters[$mtd] ?? [];

                // Building parameters.
                $existing_names = \array_map(fn($param): string => (string)$param['name'], $parameters);
                foreach ($path_parameters as $path_params) {
                    if (!\in_array($path_params['name'], $existing_names, true)) {
                        $parameters[] = $path_params;
                    }
                }

                $produces = ['application/json'];
                if (isset($arg['produces'])) {
                    $produces = (array)$arg['produces'];
                }

                $consumes = [
                    'application/x-www-form-urlencoded',
                    'multipart/form-data',
                ];

                if (isset($arg['consumes'])) {
                    $consumes = (array)$arg['consumes'];
                }

                if ($arg['accept_json']) {
                    $consumes[] = ['application/json'];
                }

                if (isset($args['tags']) && \is_array($args['tags'])) {
                    $tags = $args['tags'];
                }

                $conf = [
                    'tags' => $tags,
                    'summary' => $arg['summary'] ?? '',
                    'description' => $arg['description'] ?? '',
                    'consumes' => $consumes,
                    'produces' => $produces,
                    'parameters' => $parameters,
                    'security' => $this->getSecurity(),
                    'responses' => $this->getResponses($methodEndpoint),
                ];

                $methods[$mtd] = $conf;
            }
        }

        return $methods;
    }

    protected function getParametersFromEndpoint($endpoint): array
    {
        $path_params = [];
        if (
            \mb_strpos($endpoint, '(?P<') !== false &&
            \preg_match_all('/\(\?P<(.*?)>(.*)\)/', $endpoint, $matches)
        ) {
            foreach ($matches[1] as $order => $match) {
                $type = \strpos(\mb_strtolower($matches[2][$order]), '\d') !== false ? 'integer' : 'string';
                $params = [
                    'name' => $match,
                    'in' => 'path',
                    'description' => '',
                    'required' => true,
                    'type' => $type,
                ];
                if ($type === 'integer') {
                    $params['format'] = 'int64';
                }
                $path_params[$match] = $params;
            }
        }

        return $path_params;
    }

    protected function getDefaultTagsFromEndpoint($endpoint): array
    {
        $namespace = SwaggerUi::getNameSpace();
        $endpoint = \preg_replace_callback(
            '/^' . \preg_quote($namespace, '/') . '/',
            fn(): string => '',
            $endpoint
        );
        $parts = \explode('/', \trim($endpoint, '/'));

        return isset($parts[0]) ? [$parts[0]] : [];
    }

    protected function detectIn(string $param, string $mtd, string $endpoint): string
    {
        switch ($mtd) {
            case \strpos($endpoint, '{' . $param . '}') !== false:
                $in = 'path';
                break;
            case 'post':
                $in = 'formData';
                break;
            default:
                $in = 'query';
                break;
        }

        return $in;
    }

    protected function buildParams(string $param, string $mtd, string $endpoint, array $detail): array
    {
        $type = $detail['type'] === 'object' ? 'string' : $detail['type'];

        if (is_array($type) && isset($type[0])) {
            $type = $type[0];
        }

        if (empty($type)) {
            if (\strpos($param, '_id') !== false) {
                $type = 'integer';
            } elseif (\strtolower($param) === 'id') {
                $type = 'integer';
            } else {
                $type = 'string';
            }
        }

        $in = $this->detectIn($param, $mtd, $endpoint);
        $required = !empty($detail['required']);

        if ('path' === $in) {
            $required = true;
        }

        $params = [
            'name' => $param,
            'in' => $in,
            'description' => $detail['description'] ?? '',
            'required' => $required,
            'type' => $type,
        ];

        if (isset($detail['items']['type'])) {
            $params['items'] = [
                'type' => $detail['items']['type'],
            ];
        } elseif (isset($detail['enum'])) {
            $params['type'] = 'array';
            $items = [
                'type' => $detail['type'],
                'enum' => $detail['enum'],
            ];
            if (isset($detail['default'])) {
                $items['default'] = $detail['default'];
            }
            $params['items'] = $items;
            $params['collectionFormat'] = 'multi';
        }

        if (isset($detail['maximum'])) {
            $params['maximum'] = $detail['maximum'];
        }

        if (isset($detail['minimum'])) {
            $params['minimum'] = $detail['minimum'];
        }

        if (isset($detail['format'])) {
            $params['format'] = $detail['format'];
        } elseif ($detail['type'] === 'integer') {
            $params['format'] = 'int64';
        }

        return $params;
    }

    protected function getParametersFromArgs(string $endpoint, array $args, array $methods = []): array
    {
        $parameters = [];
        foreach ($args as $param => $detail) {
            foreach ($methods as $method => $bool) {
                $mtd = \mb_strtolower($method);
                if (!isset($parameters[$mtd])) {
                    $parameters[$mtd] = [];
                }
                $parameters[$mtd][] = $this->buildParams(
                    $param,
                    $mtd,
                    $endpoint,
                    \array_merge(['type' => 'string'], (array)$detail)
                );
            }
        }

        return $parameters;
    }

    private function getSecurity(): array
    {
        $raw = SwaggerUi::getSecurityDefinitions() ?? [];
        $securities = [];
        foreach ($raw as $key => $name) {
            $securities[] = [
                $key => [],
            ];
        }

        return $securities;
    }

    private function getResponses(string $method): array
    {
        return \apply_filters('swagger_api_responses_' . $method, [
            \WP_Http::OK => ['description' => 'OK'],
            \WP_Http::BAD_REQUEST => ['description' => 'Bad Request'],
            \WP_Http::NOT_FOUND => ['description' => 'Not Found'],
        ]);
    }
}
