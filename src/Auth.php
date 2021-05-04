<?php declare(strict_types=1);

namespace TheFrosty\WpApiSwaggerUi;

use TheFrosty\WpUtilities\Plugin\HooksTrait;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestInterface;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestTrait;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;

/**
 * Class Auth
 *
 * @package TheFrosty\WpApiSwaggerUi
 */
class Auth implements HttpFoundationRequestInterface, WpHooksInterface
{

    use HooksTrait, HttpFoundationRequestTrait;

    /**
     * @var mixed
     */
    private $error;

    public function addHooks(): void
    {
        $this->addFilter('determine_current_user', [$this, 'determineCurrentUser'], 14);
        $this->addFilter('rest_authentication_errors', [$this, 'maybeSetAuthError']);
        $this->addFilter('swagger_api_security_definitions', [$this, 'appendSwaggerAuth']);
    }

    protected function determineCurrentUser($user_id)
    {
        // Don't authenticate twice
        if (!empty($user_id)) {
            return $user_id;
        }

        $server = $this->getRequest()->server;

        // Check that we're trying to authenticate
        if (!$server->has('PHP_AUTH_USER')) {
            $user_pass = $server->get('REDIRECT_HTTP_AUTHORIZATION');
            if ($server->has('REDIRECT_HTTP_AUTHORIZATION') && !empty($user_pass)) {
                [$username, $password] = \explode(':', \base64_decode(\substr($user_pass, 6)));
                $server->set('PHP_AUTH_USER', $username);
                $server->set('PHP_AUTH_PW', $password);
            } else {
                return $user_id;
            }
        }

        $username = $server->get('PHP_AUTH_USER');
        $password = $server->get('PHP_AUTH_PW');
        $this->removeFilter('determine_current_user', [$this, 'determineCurrentUser'], 14);
        $user = \wp_authenticate($username, $password);
        $this->addFilter('determine_current_user', [$this, 'determineCurrentUser'], 14);

        if (\is_wp_error($user)) {
            $this->error = $user;

            return null;
        }

        $this->error = true;

        return $user->ID;
    }

    protected function maybeSetAuthError($error)
    {
        if (!empty($error)) {
            return $error;
        }

        return $this->error;
    }

    /**
     * @param mixed $auth
     * @return array
     */
    protected function appendSwaggerAuth($auth): array
    {
        if (!\is_array($auth)) {
            $auth = [];
        }

        $auth['basic'] = [
            'type' => 'basic',
        ];

        return $auth;
    }
}
