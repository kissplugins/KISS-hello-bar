<?php
/**
 * Plugin Name: KISS Hello Bar Plugin
 * Description: Displays a customizable hello bar with a message and a CTA button on the front end.
 * Version: 1.0.7
 * Author: Hypercart
 * Author URI: https://kissplugins.com
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class HelloBarPlugin {
    const OPTION_NAME = 'hello_bar_settings';

    public function __construct() {
        add_action('admin_menu', [$this, 'create_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_head', [$this, 'enqueue_hello_bar_styles']);
        add_action('wp_body_open', [$this, 'display_hello_bar']);
        add_action('wp_footer', [$this, 'display_footer_hello_bar']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_settings_link']);
    }

    public function create_settings_page() {
        add_options_page(
            'Hello Bar Settings',
            'Hello Bar',
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
            <h1>Hello Bar Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields(self::OPTION_NAME); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="display_top">Display Hello Bar at Top of Pages</label>
                        </th>
                        <td>
                            <input type="checkbox" name="hello_bar_settings[display_top]" id="display_top" value="1" <?php checked(1, $settings['display_top'] ?? 0); ?>>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="display_footer">Display Also on Footer</label>
                        </th>
                        <td>
                            <input type="checkbox" name="hello_bar_settings[display_footer]" id="display_footer" value="1" <?php checked(1, $settings['display_footer'] ?? 0); ?>>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="message">Message</label>
                        </th>
                        <td>
                            <input type="text" name="hello_bar_settings[message]" id="message" value="<?php echo esc_attr($settings['message'] ?? 'This is your KISS Hello Bar Plugin message'); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bg_color">Message Bar Background Color</label>
                        </th>
                        <td>
                            <input type="color" name="hello_bar_settings[bg_color]" id="bg_color" value="<?php echo esc_attr($settings['bg_color'] ?? '#000000'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cta_label">CTA Button Label</label>
                        </th>
                        <td>
                            <input type="text" name="hello_bar_settings[cta_label]" id="cta_label" value="<?php echo esc_attr($settings['cta_label'] ?? 'Click Me'); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cta_link">CTA Button Link</label>
                        </th>
                        <td>
                            <input type="url" name="hello_bar_settings[cta_link]" id="cta_link" value="<?php echo esc_attr($settings['cta_link'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cta_color">CTA Button Color</label>
                        </th>
                        <td>
                            <input type="color" name="hello_bar_settings[cta_color]" id="cta_color" value="<?php echo esc_attr($settings['cta_color'] ?? '#0000ff'); ?>">
                        </td>
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
        $output['message'] = sanitize_text_field($input['message']);
        $output['bg_color'] = sanitize_hex_color($input['bg_color']);
        $output['cta_label'] = sanitize_text_field($input['cta_label']);
        $output['cta_link'] = esc_url_raw($input['cta_link']);
        $output['cta_color'] = sanitize_hex_color($input['cta_color']);
        return $output;
    }

    public function enqueue_hello_bar_styles() {
        $settings = get_option(self::OPTION_NAME, []);
        $contrast_color = $this->get_contrast_color($settings['bg_color'] ?? '#000000');
        $cta_contrast = $this->get_contrast_color($settings['cta_color'] ?? '#0000ff');
        echo "<style>
            .hello-bar {
                width: 100%;
                position:sticky;
                background-color: {$settings['bg_color']};
                color: {$contrast_color};
                text-align: center;
                padding: 10px;
                z-index: 4;
            }
            .hello-bar .cta-button {
                background-color: {$settings['cta_color']};
                color: {$cta_contrast};
                padding: 5px 10px;
                border: none;
                text-decoration: none;
                border-radius: 3px;
            }
            body.admin-bar .hello-bar-top {
                top: 32px;
            }
            .hello-bar-top {
                top: 0px;
            }
            .hello-bar-footer {
                bottom: 0px;
            }
        </style>";
    }

    public function display_hello_bar() {
        $settings = get_option(self::OPTION_NAME, []);
        if (!empty($settings['display_top'])) {
            echo $this->get_hello_bar_html('hello-bar-top');
        }
        // if (!empty($settings['display_footer'])) {
        //     echo $this->get_hello_bar_html('hello-bar-footer');
        // }
    }

    public function display_footer_hello_bar() {
        $settings = get_option(self::OPTION_NAME, []);
        if (!empty($settings['display_footer'])) {
            echo $this->get_hello_bar_html('hello-bar-footer');
        }
    }

    private function get_hello_bar_html($class) {
        $settings = get_option(self::OPTION_NAME, []);
        $cta_label = esc_html($settings['cta_label'] ?? 'Click Me');
        $cta_link = esc_url($settings['cta_link'] ?? '#');
        $message = esc_html($settings['message'] ?? 'This is your KISS Plugin Hello Bar message');
        $admin_bar_offset = is_admin_bar_showing() && $class === 'hello-bar-top' ? ' style="top: 32px;"' : '';
        return "<div class='hello-bar {$class}'{$admin_bar_offset}>
            <span>{$message}</span>
            <a href='{$cta_link}' class='cta-button'>{$cta_label}</a>
        </div>";
    }

    private function get_contrast_color($hex) {
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));
        return ($r * 0.299 + $g * 0.587 + $b * 0.114) > 186 ? '#000000' : '#ffffff';
    }

    public function add_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=hello-bar-settings">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

new HelloBarPlugin();
