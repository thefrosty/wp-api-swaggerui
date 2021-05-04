<?php declare(strict_types=1);

namespace TheFrosty\WpApiSwaggerUi;

use TheFrosty\WpUtilities\Plugin\AbstractHookProvider;
use const TheFrosty\SLUG;

/**
 * Class Template
 * @package TheFrosty\WpApiSwaggerUi
 */
class Template extends AbstractHookProvider
{

    protected const HANDLE = SLUG;

    public function addHooks(): void
    {
        $this->addFilter('template_include', [$this, 'templateInclude'], 99);
        $this->addAction('wp_enqueue_scripts', [$this, 'removeQueuedScripts'], 99);
        $this->addAction('wp_enqueue_scripts', [$this, 'enqueueScripts'], 99);
    }

    /**
     * @param string $template
     * @return string
     */
    protected function templateInclude(string $template): string
    {
        if (\get_query_var(SwaggerUi::QUERY_VAR) !== SwaggerUi::DOCS) {
            return $template;
        }

        return $this->getPlugin()->getPath('template/single.php');
    }

    protected function removeQueuedScripts(): void
    {
        if (\get_query_var(SwaggerUi::QUERY_VAR) !== SwaggerUi::DOCS) {
            return;
        }
        // Remove all default styles.
        $wp_styles = \wp_styles();
        $style_whitelist = ['admin-bar', 'dashicons'];
        foreach (\array_merge($wp_styles->registered, $wp_styles->queue) as $handle => $data) {
            if (\in_array($handle, $style_whitelist, true)) {
                continue;
            }
            \wp_dequeue_style($handle);
        }


        // Remove all default scripts;
        $wp_scripts = \wp_scripts();
        $script_whitelist = ['admin-bar'];
        foreach (\array_merge($wp_scripts->registered, $wp_scripts->queue) as $handle => $data) {
            if (\in_array($handle, $script_whitelist, true)) {
                continue;
            }
            \wp_dequeue_script($handle);
        }
    }

    protected function enqueueScripts(): void
    {
        \wp_register_style(
            self::HANDLE,
            $this->getPlugin()->getPath('assets/css/app.css'),
            [],
            $this->getPlugin()->getFileTime('assets/css/app.css')
        );
        \wp_register_script(
            self::HANDLE,
            $this->getPlugin()->getPath('assets/js/app.js'),
            [],
            $this->getPlugin()->getFileTime('assets/js/app.js'),
            true
        );
        $l10n = [
            'schema_url' => home_url(SwaggerUi::getRewriteBaseApi() . '/schema'),
        ];
        \wp_localize_script(self::HANDLE, 'swagger_ui_app', $l10n);

        if (\get_query_var(SwaggerUi::QUERY_VAR) !== SwaggerUi::DOCS) {
            return;
        }

        \wp_enqueue_style(self::HANDLE);
        \wp_enqueue_script(self::HANDLE);
    }
}
