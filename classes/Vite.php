<?php

namespace Theme_base;

class Vite
{
    private string $hmrHost;
    private string $assetsPath;
    private string $assetsUri;
    private string $version;
    private string $handlePrefix;

    /**
     * JS entry points (Vite input keys → handle suffix).
     *
     * @var array<string, string>
     */
    private array $jsEntries = [
        'main' => 'resources/scripts/main.js',
        'popover' => 'resources/scripts/popover.js',
        'slider' => 'resources/scripts/slider.js',
    ];

    /**
     * Bundles conditionnels (compte client). Non chargés par enqueueAssets :
     * Personalisation::enqueueForm() les demande via enqueueNamed()
     * uniquement sur l'endpoint natif `view-order`.
     *
     * @var array<string, string>
     */
    private array $lazyJsEntries = [
        'account' => 'resources/scripts/account.js',
    ];

    /**
     * @var array<string, string>
     */
    private array $lazyCssEntries = [
        'account' => 'resources/styles/account.scss',
    ];

    private static ?self $instance = null;

    public function __construct(
        string $hmrHost,
        string $assetsPath,
        string $assetsUri,
        string $version = '0.1.0',
        string $handlePrefix = 'lumiboo'
    ) {
        $this->hmrHost = $hmrHost;
        $this->assetsPath = $assetsPath;
        $this->assetsUri = $assetsUri;
        $this->version = $version;
        $this->handlePrefix = $handlePrefix;
        self::$instance = $this;
    }

    public static function instance(): ?self
    {
        return self::$instance;
    }

    /**
     * Handle of the main JS/CSS bundle, e.g. for wp_localize_script().
     */
    public function mainHandle(): string
    {
        return $this->scriptHandle('main');
    }

    public function scriptHandle(string $name): string
    {
        return $this->handlePrefix . '-' . $name;
    }

    public function isRunning(): bool
    {
        $response = wp_remote_get($this->hmrHost, ['timeout' => 1]);
        return !is_wp_error($response);
    }

    public function enqueue(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets'], 10);
        add_filter('script_loader_tag', [$this, 'addModuleType'], 10, 3);
    }

    /**
     * Charge un bundle lazy (JS et/ou CSS du même nom) sur la page courante.
     */
    public function enqueueNamed(string $name): void
    {
        if ($this->isRunning()) {
            $this->enqueueNamedDev($name);
            return;
        }

        $this->enqueueNamedProduction($name);
    }

    public function enqueueAssets(): void
    {
        if ($this->isRunning()) {
            $this->enqueueDev();
        } else {
            $this->enqueueProduction();
        }
    }

    private function enqueueDev(): void
    {
        wp_enqueue_script(
            $this->handlePrefix . '-vite-client',
            $this->hmrHost . '/@vite/client',
            [],
            null
        );
        foreach ($this->jsEntries as $name => $src) {
            wp_enqueue_script(
                $this->scriptHandle($name),
                $this->hmrHost . '/' . $src,
                [],
                null
            );
        }
        wp_enqueue_style(
            $this->mainHandle(),
            $this->hmrHost . '/resources/styles/main.scss',
            [],
            null
        );
    }

    private function enqueueProduction(): void
    {
        $manifest_path = $this->assetsPath . '/.vite/manifest.json';
        if (!file_exists($manifest_path)) {
            wp_die('Run <code>npm run dev</code> or <code>npm run build</code> in the theme directory.');
        }
        $manifest = json_decode(file_get_contents($manifest_path), true);
        $css_file = $manifest['resources/styles/main.scss']['file'] ?? null;
        if ($css_file) {
            wp_enqueue_style($this->mainHandle(), $this->assetsUri . '/' . $css_file, [], $this->version);
        }
        foreach ($this->jsEntries as $name => $src) {
            $js_file = $manifest[$src]['file'] ?? null;
            if ($js_file) {
                wp_enqueue_script($this->scriptHandle($name), $this->assetsUri . '/' . $js_file, [], $this->version);
            }
        }
    }

    public function addModuleType(string $tag, string $handle, string $src): string
    {
        $module_handles = [
            $this->handlePrefix . '-vite-client',
        ];
        foreach (array_merge(array_keys($this->jsEntries), array_keys($this->lazyJsEntries)) as $name) {
            $module_handles[] = $this->scriptHandle($name);
        }
        if (in_array($handle, $module_handles, true)) {
            return str_replace('<script ', '<script type="module" ', $tag);
        }
        return $tag;
    }

    private function enqueueNamedDev(string $name): void
    {
        if (isset($this->lazyJsEntries[$name])) {
            wp_enqueue_script(
                $this->scriptHandle($name),
                $this->hmrHost . '/' . $this->lazyJsEntries[$name],
                [],
                null
            );
        }

        if (isset($this->lazyCssEntries[$name])) {
            wp_enqueue_style(
                $this->scriptHandle($name),
                $this->hmrHost . '/' . $this->lazyCssEntries[$name],
                [],
                null
            );
        }
    }

    private function enqueueNamedProduction(string $name): void
    {
        $manifest_path = $this->assetsPath . '/.vite/manifest.json';
        if (! file_exists($manifest_path)) {
            return;
        }

        $manifest = json_decode((string) file_get_contents($manifest_path), true);
        if (! is_array($manifest)) {
            return;
        }

        if (isset($this->lazyJsEntries[$name])) {
            $js_file = $manifest[$this->lazyJsEntries[$name]]['file'] ?? null;
            if ($js_file) {
                wp_enqueue_script(
                    $this->scriptHandle($name),
                    $this->assetsUri . '/' . $js_file,
                    [],
                    $this->version
                );
            }
        }

        if (isset($this->lazyCssEntries[$name])) {
            $css_file = $manifest[$this->lazyCssEntries[$name]]['file'] ?? null;
            if ($css_file) {
                wp_enqueue_style(
                    $this->scriptHandle($name),
                    $this->assetsUri . '/' . $css_file,
                    [],
                    $this->version
                );
            }
        }
    }
}
