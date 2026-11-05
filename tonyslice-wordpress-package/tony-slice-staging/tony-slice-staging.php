<?php
/**
 * Plugin Name: Tony Slice Staging Pages
 * Description: Adds the Tony Slice website preview and section pages as shortcodes.
 * Version: 2.1.0
 * Author: Tony Slice
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Tony_Slice_Staging_Pages
{
    private const HOME_PATH = '/tony-slice-preview/';
    private const SOURCE_POSTS_URL = 'https://www.tonyslice.com/wp-json/wp/v2/posts';
    private const STORY_META_KEY = '_tony_slice_recreated';
    private const PAGE_TYPES = [
        'home' => 'index.html',
        'stories' => 'stories/index.html',
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
        add_filter('template_include', [$this, 'story_template'], 99);
        add_action('admin_menu', [$this, 'register_story_tools']);
        add_action('admin_post_tony_slice_sync_posts', [$this, 'sync_stories']);
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
        $type = $this->current_type();
        if ($type === null && !$this->is_recreated_story()) {
            return;
        }

        $base_url = plugin_dir_url(__FILE__);
        $base_path = plugin_dir_path(__FILE__);
        $version = '2.1.0';

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

        if ($this->is_recreated_story()) {
            wp_add_inline_script(
                'tony-slice-sections',
                'window.tonySlicePost = ' . wp_json_encode($this->story_data(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ';',
                'before'
            );
        }
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
        if ($type === 'stories') {
            $content .= '<script>window.tonySliceStories = ' .
                wp_json_encode($this->story_archive_data(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) .
                ';</script>';
        }
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
        if ($this->is_recreated_story()) {
            return get_the_title(get_queried_object_id()) . ' | Tony Slice';
        }

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
        if ($this->is_recreated_story()) {
            $description = wp_strip_all_tags(get_the_excerpt(get_queried_object_id()));
            if ($description !== '') {
                printf("<meta name=\"description\" content=\"%s\">\n", esc_attr($description));
            }
            return;
        }

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
        if ($this->current_type() !== null || $this->is_recreated_story()) {
            $classes[] = 'tony-slice-staging-page-active';
        }
        return $classes;
    }

    private function is_recreated_story(): bool
    {
        return is_singular('post') &&
            get_post_meta(get_queried_object_id(), self::STORY_META_KEY, true) === '1';
    }

    public function story_template(string $template): string
    {
        if (!$this->is_recreated_story()) {
            return $template;
        }

        $story_template = plugin_dir_path(__FILE__) . 'templates/single-story.php';
        return is_readable($story_template) ? $story_template : $template;
    }

    private function story_archive_data(): array
    {
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => 100,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        $stories = [];

        foreach ($posts as $post) {
            $stories[] = [
                'title' => get_the_title($post),
                'url' => get_permalink($post),
                'date' => get_the_date('F j, Y', $post),
                'excerpt' => html_entity_decode(wp_strip_all_tags(get_the_excerpt($post)), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'image' => get_the_post_thumbnail_url($post, 'large') ?: '',
            ];
        }

        return $stories;
    }

    private function story_data(): array
    {
        $post_id = get_queried_object_id();
        $content = apply_filters('the_content', get_post_field('post_content', $post_id));

        return [
            'title' => get_the_title($post_id),
            'date' => get_the_date('F j, Y', $post_id),
            'url' => get_permalink($post_id),
            'content' => wp_kses_post($content),
            'image' => get_the_post_thumbnail_url($post_id, 'large') ?: '',
        ];
    }

    public function register_story_tools(): void
    {
        add_management_page(
            'Tony Slice Stories',
            'Tony Slice Stories',
            'manage_options',
            'tony-slice-stories',
            [$this, 'render_story_tools']
        );
    }

    public function render_story_tools(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['synced'])) {
            printf(
                '<div class="notice notice-success is-dismissible"><p>Copied and refreshed %d posts from tonyslice.com.</p></div>',
                absint(wp_unslash($_GET['synced']))
            );
        }
        ?>
        <div class="wrap">
            <h1>Tony Slice Stories</h1>
            <p>Copy published posts from tonyslice.com into staging, preserving their article URLs and updating their content.</p>
            <p>The existing staging post IDs and slugs are retained. The Stories archive uses the staging design header and footer.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="tony_slice_sync_posts">
                <?php wp_nonce_field('tony_slice_sync_posts'); ?>
                <?php submit_button('Copy posts to staging'); ?>
            </form>
        </div>
        <?php
    }

    public function sync_stories(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to sync Tony Slice posts.', 'tony-slice-staging'));
        }
        check_admin_referer('tony_slice_sync_posts');

        $response = wp_remote_get(
            self::SOURCE_POSTS_URL . '?status=publish&per_page=100&_embed=wp:term&_fields=date,date_gmt,slug,title,content,excerpt,categories,tags,featured_media,_embedded',
            ['timeout' => 60, 'redirection' => 3]
        );
        if (is_wp_error($response)) {
            wp_die(esc_html('Could not read posts from tonyslice.com: ' . $response->get_error_message()));
        }

        $status = wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            wp_die(esc_html('tonyslice.com returned HTTP ' . $status . ' while reading posts.'));
        }

        $source_posts = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($source_posts)) {
            wp_die(esc_html__('The post feed from tonyslice.com was not valid JSON.', 'tony-slice-staging'));
        }

        $synced = 0;
        foreach ($source_posts as $source) {
            $slug = sanitize_title($source['slug'] ?? '');
            $title = html_entity_decode(wp_strip_all_tags($source['title']['rendered'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($slug === '' || $title === '' || !isset($source['content']['rendered'])) {
                wp_die(esc_html__('A source post is missing its title, slug, or content; no further posts were updated.', 'tony-slice-staging'));
            }

            $content = str_replace(
                ['https://www.tonyslice.com', 'http://www.tonyslice.com', 'https://tonyslice.com', 'http://tonyslice.com'],
                untrailingslashit(home_url()),
                $source['content']['rendered']
            );
            $existing = get_page_by_path($slug, OBJECT, 'post');
            $post_data = [
                'post_title' => $title,
                'post_name' => $slug,
                'post_content' => wp_kses_post($content),
                'post_excerpt' => html_entity_decode(wp_strip_all_tags($source['excerpt']['rendered'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'post_status' => 'publish',
                'post_type' => 'post',
            ];
            if (!empty($source['date'])) {
                $post_data['post_date'] = str_replace('T', ' ', substr($source['date'], 0, 19));
            }
            if (!empty($source['date_gmt'])) {
                $post_data['post_date_gmt'] = str_replace('T', ' ', substr($source['date_gmt'], 0, 19));
            }
            if ($existing) {
                $post_data['ID'] = $existing->ID;
                $post_id = wp_update_post($post_data, true);
            } else {
                $post_id = wp_insert_post($post_data, true);
            }
            if (is_wp_error($post_id)) {
                wp_die(esc_html('Could not save "' . $title . '": ' . $post_id->get_error_message()));
            }

            $terms = $source['_embedded']['wp:term'] ?? [];
            foreach (['category' => 0, 'post_tag' => 1] as $taxonomy => $group_index) {
                $term_ids = [];
                foreach ($terms[$group_index] ?? [] as $term) {
                    if (empty($term['slug']) || empty($term['name'])) {
                        continue;
                    }
                    $existing_term = get_term_by('slug', sanitize_title($term['slug']), $taxonomy);
                    if ($existing_term) {
                        $term_ids[] = (int) $existing_term->term_id;
                        continue;
                    }
                    $new_term = wp_insert_term(
                        sanitize_text_field($term['name']),
                        $taxonomy,
                        ['slug' => sanitize_title($term['slug'])]
                    );
                    if (is_wp_error($new_term)) {
                        wp_die(esc_html('Could not create the "' . $term['name'] . '" term: ' . $new_term->get_error_message()));
                    }
                    $term_ids[] = (int) $new_term['term_id'];
                }
                $term_result = wp_set_object_terms($post_id, $term_ids, $taxonomy, false);
                if (is_wp_error($term_result)) {
                    wp_die(esc_html('Could not assign post terms: ' . $term_result->get_error_message()));
                }
            }

            $featured_media = absint($source['featured_media'] ?? 0);
            $media = $featured_media ? get_post($featured_media) : null;
            if ($media && $media->post_type === 'attachment') {
                set_post_thumbnail($post_id, $featured_media);
            } else {
                delete_post_thumbnail($post_id);
            }
            update_post_meta($post_id, self::STORY_META_KEY, '1');
            $synced++;
        }

        if ($synced === 0) {
            wp_die(esc_html__('The source feed contained no published posts.', 'tony-slice-staging'));
        }

        $stories_page = get_page_by_path('stories', OBJECT, 'page');
        if (!$stories_page) {
            $page_id = wp_insert_post([
                'post_title' => 'Stories',
                'post_name' => 'stories',
                'post_content' => '[tony_slice type="stories"]',
                'post_status' => 'publish',
                'post_type' => 'page',
            ], true);
            if (is_wp_error($page_id)) {
                wp_die(esc_html('Posts were copied, but the Stories page could not be created: ' . $page_id->get_error_message()));
            }
            update_post_meta($page_id, '_wp_page_template', 'elementor_canvas');
        }

        wp_safe_redirect(add_query_arg(
            ['page' => 'tony-slice-stories', 'synced' => $synced],
            admin_url('tools.php')
        ));
        exit;
    }
}

new Tony_Slice_Staging_Pages();
