<?php
/**
 * Plugin Name: Tony Slice Staging Pages
 * Description: Adds the Tony Slice website preview and section pages as shortcodes.
 * Version: 2.0.1
 * Author: Tony Slice
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Tony_Slice_Staging_Pages
{
    private const HOME_PATH = '/tony-slice-preview/';
    private const PAGE_TYPES = [
        'home' => 'index.html',
        'social-media' => 'social-media/index.html',
        'social-media-content-visits' => 'social-media/content-visits/index.html',
        'social-media-monthly-management' => 'social-media/monthly-management/index.html',
        'social-media-campaigns' => 'social-media/campaigns/index.html',
        'events' => 'events.html',
        'events-hub' => 'events/index.html',
        'events-hosting' => 'events/hosting/index.html',
        'collaborate' => 'collaborate/index.html',
        'collaborate-restaurants' => 'collaborate/restaurants/index.html',
        'collaborate-hotels' => 'collaborate/hotels/index.html',
        'collaborate-brands' => 'collaborate/brands/index.html',
        'businesses' => 'businesses/index.html',
        'businesses-fresh-fold-altoona' => 'businesses/fresh-fold-altoona/index.html',
        'watch' => 'watch/index.html',
        'food' => 'food/index.html',
        'comedy' => 'comedy/index.html',
        'about' => 'about/index.html',
        'press' => 'press/index.html',
        'book' => 'book/index.html',
        'contact' => 'contact/index.html',
        'sitemap' => 'sitemap/index.html',
        'restaurants' => 'restaurants.html',
        'brands' => 'brands.html',
    ];

    public function __construct()
    {
        add_shortcode('tony_slice', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('pre_get_document_title', [$this, 'filter_document_title']);
        add_action('wp_head', [$this, 'print_meta_description'], 1);
        add_filter('body_class', [$this, 'add_body_class']);
    }

    private function current_type(): ?string
    {
        if (!is_singular()) {
            return null;
        }

        $post = get_post();
        if (!$post || !has_shortcode($post->post_content, 'tony_slice')) {
            return null;
        }

        if (preg_match('/\[tony_slice(?:\s+([^\]]*))?\]/', $post->post_content, $matches)) {
            $attributes = shortcode_parse_atts($matches[1] ?? '');
            $type = sanitize_key($attributes['type'] ?? 'home');
            return isset(self::PAGE_TYPES[$type]) ? $type : 'home';
        }

        return null;
    }

    public function enqueue_assets(): void
    {
        if ($this->current_type() === null) {
            return;
        }

        $base_url = plugin_dir_url(__FILE__);
        $base_path = plugin_dir_path(__FILE__);
        $version = '2.0.1';

        wp_enqueue_style(
            'tony-slice-staging',
            $base_url . 'assets/styles.css',
            [],
            file_exists($base_path . 'assets/styles.css') ? (string) filemtime($base_path . 'assets/styles.css') : $version
        );
        wp_enqueue_script(
            'tony-slice-sections',
            $base_url . 'assets/section-pages.js',
            [],
            file_exists($base_path . 'assets/section-pages.js') ? (string) filemtime($base_path . 'assets/section-pages.js') : $version,
            true
        );
        wp_enqueue_script(
            'tony-slice-staging',
            $base_url . 'assets/app.js',
            ['tony-slice-sections'],
            file_exists($base_path . 'assets/app.js') ? (string) filemtime($base_path . 'assets/app.js') : $version,
            true
        );
    }

    public function render_shortcode(array $attributes = []): string
    {
        $attributes = shortcode_atts(['type' => 'home'], $attributes, 'tony_slice');
        $type = sanitize_key($attributes['type']);
        if (!isset(self::PAGE_TYPES[$type])) {
            return '<p>Unknown Tony Slice page type.</p>';
        }

        $file = plugin_dir_path(__FILE__) . 'pages/' . self::PAGE_TYPES[$type];
        if (!is_readable($file)) {
            return '<p>The Tony Slice page file is missing. Reinstall the plugin package.</p>';
        }

        $document = file_get_contents($file);
        if ($document === false || !preg_match('/<body[^>]*>(.*?)<\/body>/is', $document, $matches)) {
            return '<p>The Tony Slice page could not be loaded.</p>';
        }

        $content = $matches[1];
        $content = preg_replace_callback(
            '/\bhref=(["\'])(.*?)\1/i',
            static function (array $matches): string {
                $quote = $matches[1];
                $href = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                if (preg_match('#^(?:\.\./)*index\.html([?#].*)?$#', $href, $relative_home)) {
                    $target = home_url(self::HOME_PATH) . ($relative_home[1] ?? '');
                    return 'href=' . $quote . esc_url($target) . $quote;
                }

                if (preg_match('#^(?:\.\./)*([a-z0-9-]+(?:/[a-z0-9-]+)*/)([?#].*)?$#i', $href, $relative_route)) {
                    $target = home_url('/' . $relative_route[1] . ($relative_route[2] ?? ''));
                    return 'href=' . $quote . esc_url($target) . $quote;
                }

                if (substr($href, 0, 1) === '/' && substr($href, 0, 2) !== '//') {
                    $path = parse_url($href, PHP_URL_PATH);
                    if ($path === '/' || $path === '/index.html') {
                        $suffix = substr($href, strlen($path));
                        $target = home_url(self::HOME_PATH) . $suffix;
                    } else {
                        $target = home_url($href);
                    }
                    return 'href=' . $quote . esc_url($target) . $quote;
                }

                return $matches[0];
            },
            $content
        );

        return '<div class="tony-slice-staging-page" data-home-url="' . esc_url(home_url(self::HOME_PATH)) . '">' . $content . '</div>';
    }

    public function filter_document_title(string $title): string
    {
        $type = $this->current_type();
        if ($type === null) {
            return $title;
        }

        $document = $this->page_document($type);
        if ($document && preg_match('/<title[^>]*>(.*?)<\/title>/is', $document, $matches)) {
            return html_entity_decode(trim(strip_tags($matches[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $title;
    }

    public function print_meta_description(): void
    {
        $type = $this->current_type();
        if ($type === null) {
            return;
        }

        $document = $this->page_document($type);
        if ($document && preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\'][^>]*>/is', $document, $matches)) {
            printf(
                "<meta name=\"description\" content=\"%s\">\n",
                esc_attr(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            );
        }
    }

    private function page_document(string $type): ?string
    {
        if (!isset(self::PAGE_TYPES[$type])) {
            return null;
        }

        $file = plugin_dir_path(__FILE__) . 'pages/' . self::PAGE_TYPES[$type];
        if (!is_readable($file)) {
            return null;
        }

        $document = file_get_contents($file);
        return $document === false ? null : $document;
    }

    public function add_body_class(array $classes): array
    {
        if ($this->current_type() !== null) {
            $classes[] = 'tony-slice-staging-page-active';
        }
        return $classes;
    }
}

new Tony_Slice_Staging_Pages();
