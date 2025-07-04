<?php
/**
 * Plugin Name: KISS - Hello Bar
 * Description: Displays a customizable hello bar with a message and a CTA button on the front end.
 * Version: 1.0.9
 * Author: Hypercart
 * Author URI: https://kissplugins.com
 * Text Domain: kiss-hello-bar
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include the Plugin Update Checker
require plugin_dir_path(__FILE__) . 'lib/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/kissplugins/KISS-hello-bar',
    __FILE__,
    'kiss-hello-bar'
);
// Optional: Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

class HelloBarPlugin {
    const OPTION_NAME = 'hello_bar_settings';
    const POST_TYPE = 'hello_bar';

    public function __construct() {
        add_action('init', [$this, 'register_hello_bar_post_type']);
        add_action('admin_menu', [$this, 'create_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_hello_bar_scripts']);
        add_action('wp_head', [$this, 'display_hello_bar_slider']);
        add_action('wp_footer', [$this, 'display_footer_hello_bar_slider']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_settings_link']);
        add_action('add_meta_boxes', [$this, 'add_hello_bar_meta_box']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_hello_bar_meta']);
    }

    public function register_hello_bar_post_type() {
        $labels = [
            'name' => 'Hello Bars',
            'singular_name' => 'Hello Bar',
            'menu_name' => 'Hello Bars',
            'add_new' => 'Add New Hello Bar',
            'add_new_item' => 'Add New Hello Bar',
            'edit_item' => 'Edit Hello Bar',
            'new_item' => 'New Hello Bar',
            'view_item' => 'View Hello Bar',
            'all_items' => 'All Hello Bars',
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title'],
            'capability_type' => 'post',
            'menu_icon' => 'dashicons-megaphone',
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    public function add_hello_bar_meta_box() {
        add_meta_box(
            'hello_bar_settings',
            'Hello Bar Settings',
            [$this, 'render_hello_bar_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_hello_bar_meta_box($post) {
        wp_nonce_field('hello_bar_meta_box', 'hello_bar_meta_box_nonce');
        $meta = get_post_meta($post->ID, '_hello_bar_settings', true) ?: [];
        ?>
        <table class="form-table">
            <tr>
                <th><label for="bg_color">Background Color</label></th>
                <td><input type="color" name="hello_bar_settings[bg_color]" id="bg_color" value="<?php echo esc_attr($meta['bg_color'] ?? '#000000'); ?>"></td>
            </tr>
            <tr>
                <th><label for="text_color">Text Color</label></th>
                <td><input type="color" name="hello_bar_settings[text_color]" id="text_color" value="<?php echo esc_attr($meta['text_color'] ?? '#0000ff'); ?>"></td>
            </tr>
            <tr>
                <th><label for="message_desktop">Desktop Message</label></th>
                <td><input type="text" name="hello_bar_settings[message_desktop]" id="message_desktop" value="<?php echo esc_attr($meta['message_desktop'] ?? 'Default Desktop Message'); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="message_mobile">Mobile Message</label></th>
                <td><input type="text" name="hello_bar_settings[message_mobile]" id="message_mobile" value="<?php echo esc_attr($meta['message_mobile'] ?? 'Default Mobile Message'); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="cta_label">CTA Button Label</label></th>
                <td><input type="text" name="hello_bar_settings[cta_label]" id="cta_label" value="<?php echo esc_attr($meta['cta_label'] ?? 'Click Me'); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="cta_link">CTA Button Link</label></th>
                <td><input type="url" name="hello_bar_settings[cta_link]" id="cta_link" value="<?php echo esc_attr($meta['cta_link'] ?? ''); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="cta_enabled">Show as button</label></th>
                <td><input type="checkbox" name="hello_bar_settings[cta_enabled]" id="cta_enabled" value="1" <?php checked($meta['cta_enabled'] ?? 0, 1); ?>></td>
            </tr>
            <tr>
                <th><label for="cta_bg_color">CTA button background color</label></th>
                <td><input type="color" name="hello_bar_settings[cta_bg_color]" id="cta_bg_color" value="<?php echo esc_attr($meta['cta_bg_color'] ?? '#0000ff'); ?>"></td>
            </tr>
            <tr>
                <th><label for="cta_text_color">CTA button text color</label></th>
                <td><input type="color" name="hello_bar_settings[cta_text_color]" id="cta_text_color" value="<?php echo esc_attr($meta['cta_text_color'] ?? '#0000ff'); ?>"></td>
            </tr>
        </table>
        <?php
    }

    public function save_hello_bar_meta($post_id) {
        if (!isset($_POST['hello_bar_meta_box_nonce']) || !wp_verify_nonce($_POST['hello_bar_meta_box_nonce'], 'hello_bar_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $input = $_POST['hello_bar_settings'] ?? [];
        $output = [
            'bg_color' => esc_url_raw($input['bg_color']),
            'text_color' => esc_url_raw($input['text_color']),
            'message_desktop' => sanitize_textarea_field($input['message_desktop']),
            'message_mobile' => sanitize_textarea_field($input['message_mobile']),
            'cta_label' => sanitize_text_field($input['cta_label']),
            'cta_link' => esc_url_raw($input['cta_link']),
            'cta_text_color' => esc_url_raw($input['cta_text_color']),
            'cta_bg_color' => esc_url_raw($input['cta_bg_color']),
            'cta_enabled' => isset($input['cta_enabled']) ? 1 : 0,
        ];
        update_post_meta($post_id, '_hello_bar_settings', $output);
    }

    public function enqueue_hello_bar_scripts() {
        wp_enqueue_style(
            'hello-bar-swiper-css',
            plugin_dir_url(__FILE__) . 'assets/swiper/swiper-bundle.min.css',
            [],
            '8.4.5'
        );
        wp_enqueue_script(
            'hello-bar-swiper-js',
            plugin_dir_url(__FILE__) . 'assets/swiper/swiper-bundle.min.js',
            [],
            '8.4.5',
            true
        );
        // Enqueue Swiper
        wp_enqueue_style('hello-bar-swiper-css');
        wp_enqueue_script('hello-bar-swiper-js');

        wp_enqueue_style('cpt-hello-bar-css', plugin_dir_url(__FILE__)  . '/assets/css/cpt-hello-bar.css');
        wp_enqueue_script('cpt-hello-bar-js', plugin_dir_url(__FILE__) . '/assets/js/cpt-hello-bar.js', array('hello-bar-swiper-js'), null, true);

    }

    public function display_hello_bar_slider() {
        $global_settings = get_option(self::OPTION_NAME, []);
        if (!empty($global_settings['display_top'])) {
            $hello_bars = $this->get_hello_bars();
            if (!empty($hello_bars)) {
                echo '<div class="swiper-container hello-bar-slider hello-bar-slider-top hello-bar-top">';
                echo '<div class="swiper-wrapper">';
                foreach ($hello_bars as $bar) {
                    echo '<div class="swiper-slide">' . $this->get_hello_bar_html($bar, 'hello-bar') . '</div>';
                }
                echo '</div>';
                echo '<div class="swiper-button-prev swiper-button-prev-top"></div>';
                echo '<div class="swiper-button-next swiper-button-next-top"></div>';
                echo '</div>';
            }
        }
    }

    public function display_footer_hello_bar_slider() {
        $global_settings = get_option(self::OPTION_NAME, []);
        $is_footer_displayed = !empty($global_settings['display_footer']);
        echo '<script>var helloBarfooterDisplay = ' . ($is_footer_displayed ? 'true' : 'false') . ';</script>';
        if (!empty($global_settings['display_footer'])) {
            $hello_bars = $this->get_hello_bars();
            if (!empty($hello_bars)) {
                echo '<div class="swiper-container hello-bar-slider hello-bar-slider-footer hello-bar-footer">';
                echo '<div class="swiper-wrapper">';
                foreach ($hello_bars as $bar) {
                    echo '<div class="swiper-slide">' . $this->get_hello_bar_html($bar, 'hello-bar') . '</div>';
                }
                echo '</div>';
                echo '<div class="swiper-button-prev-footer swiper-button-prev-top"></div>';
                echo '<div class="swiper-button-next-footer swiper-button-next-top"></div>';
                echo '</div>';
            }
        }
    }

    private function get_hello_bars() {
        $args = [
            'post_type' => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ];
        $query = new WP_Query($args);
        $hello_bars = [];

        while ($query->have_posts()) {
            $query->the_post();
            $meta = get_post_meta(get_the_ID(), '_hello_bar_settings', true);
            $hello_bars[] = $meta;
        }
        wp_reset_postdata();
        return $hello_bars;
    }

    private function get_hello_bar_html($settings, $class) {
        //$global_settings = get_option(self::OPTION_NAME, []);
        $bg_color = $settings['bg_color'] ?? '#000000';
        $text_color = $settings['text_color'] ?? '#0000ff';
        $cta_text_color = $settings['cta_text_color'] ?? '#0000ff';
        $cta_bg_color = $settings['cta_bg_color'] ?? '#0000ff';
        $contrast_color = $this->get_contrast_color($bg_color);
        $cta_contrast = $this->get_contrast_color($cta_color);

        $cta_label = esc_html($settings['cta_label'] ?? 'Click Me');
        $cta_link = esc_url($settings['cta_link'] ?? '#');
        $message_desktop = esc_html($settings['message_desktop'] ?? 'Default Desktop Message');
        $message_mobile = esc_html($settings['message_mobile'] ?? 'Default Mobile Message');
        $cta_enabled = $settings['cta_enabled'] ?? 0;

        // Initialize the HTML output
        $html = "<div class='{$class}' style='background-color: {$bg_color}; color: {$text_color}'>";

        // Add desktop and mobile messages with conditional underlining
        $text_style = $cta_enabled ? '' : 'text-decoration: underline;';
        $html .= "<span class='hello-bar-desktop'>{$message_desktop}</span>";
        $html .= "<span class='hello-bar-mobile'>{$message_mobile}</span>";

        // Add CTA button only if enabled
        if ($cta_enabled) {
            $html .= "<a href='{$cta_link}' class='cta-button' style='background-color: {$cta_bg_color}; color: {$cta_text_color};'>{$cta_label}</a>";
        } else {
            $html .= "<a href='{$cta_link}' class='cta-button underline-link' style='padding:0; {$text_style}; color: {$cta_text_color};'>{$cta_label}</a>";
        }

        $html .= "</div>";

        return $html;
    }

    private function get_contrast_color($hex) {
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));
        return ($r * 0.299 + $g * 0.587 + $b * 0.114) > 186 ? '#000000' : '#ffffff';
    }

    public function create_settings_page() {
        add_submenu_page(
            'edit.php?post_type=hello_bar',
            'Hello Bar Settings',
            'Settings',
            'manage_options',
            'hello-bar-settings',
            [$this, 'settings_page_content']
        );
    }

    public function register_settings() {
        register_setting(self::OPTION_NAME, self::OPTION_NAME, [$this, 'sanitize_settings']);
    }

    public function settings_page_content() {
        $settings = get_option(self::OPTION_NAME, []);
        ?>
        <div class="wrap">
            <h1>Hello Bar Global Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields(self::OPTION_NAME); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="display_top">Display Hello Bars at Top</label></th>
                        <td><input type="checkbox" name="hello_bar_settings[display_top]" id="display_top" value="1" <?php checked(1, $settings['display_top'] ?? 0); ?>></td>
                    </tr>
                    <tr>
                        <th><label for="display_footer">Display Hello Bars at Footer</label></th>
                        <td><input type="checkbox" name="hello_bar_settings[display_footer]" id="display_footer" value="1" <?php checked(1, $settings['display_footer'] ?? 0); ?>></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function sanitize_settings($input) {
        $output = [];
        $output['display_top'] = !empty($input['display_top']) ? 1 : 0;
        $output['display_footer'] = !empty($input['display_footer']) ? 1 : 0;
        return $output;
    }

    public function add_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=hello-bar-settings">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

new HelloBarPlugin();
