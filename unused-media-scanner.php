<?php
/*
Plugin Name: Unused Media Scanner
Plugin URI: https://wordpress.org/plugins/unused-media-scanner/
Description: The Unused Media Scanner Plugin scans your Media Library and content to highlight all the assets that are not currently being used. You can then select the ones you wish to delete.
Author: 1wl.agency
Author URI: https://1wl.agency/unused-media-scanner/
Author Email: dev@1wl.agency
Version: 1.0.6
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: unused-media-scanner
Domain Path: /languages
Network: true
/*  Copyright 2024  1WL Agency  (email : contact@1wl.agency)

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!function_exists('add_action')) {
    exit;
}

function EMSC_Unused_Media_Scanner_add_management_page()
{
    add_management_page(__('Unused Media Scanner', 'unused-media-scanner'), __('Unused Media Scanner', 'unused-media-scanner'), "manage_options", "unused-media-scanner", "EMSC_Unused_Media_Scanner_management_page");
}

function EMSC_Unused_Media_Scanner_load_textdomain()
{
    load_plugin_textdomain('unused-media-scanner', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

function EMSC_Unused_Media_Scanner_management_page()
{
?>
    <div class="wrap">
        <h2><?php esc_html_e('Unused Media Scanner', 'unused-media-scanner'); ?></h2>
        <?php
        if (!isset($_POST['media_scanner_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['media_scanner_nonce'])), 'media_scanner_nonce')) {
            if (isset($_GET['tab'])) {
                $active_tab = sanitize_text_field($_GET['tab']);
            } else {
                $active_tab = 'scanner-tab';
            }
        } else {
            $active_tab = 'scanner-tab';
        }

        $EMSC_tab_nonce = wp_create_nonce("EMSC_tab_nonce");
        ?>

        <h2 class="nav-tab-wrapper">
            <a href="?page=<?php echo esc_html(sanitize_text_field($_GET['page'])); ?>&tab=scanner-tab&EMSC_tab_nonce=<?php echo esc_html(sanitize_text_field($EMSC_tab_nonce)); ?>" class="nav-tab <?php echo $active_tab == 'scanner-tab' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Scanner', 'unused-media-scanner'); ?></a>
            <!--<a href="?page=<?php echo esc_html(sanitize_text_field($_GET['page'])); ?>&tab=fsscanner-tab&EMSC_tab_nonce=<?php echo esc_html(sanitize_text_field($EMSC_tab_nonce)); ?>" class="nav-tab <?php echo $active_tab == 'fsscanner-tab' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Filesystem Scanner', 'unused-media-scanner'); ?></a>-->
            <a href="?page=<?php echo esc_html(sanitize_text_field($_GET['page'])); ?>&tab=info-tab&EMSC_tab_nonce=<?php echo esc_html(sanitize_text_field($EMSC_tab_nonce)); ?>" class="nav-tab <?php echo $active_tab == 'info-tab' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Info', 'unused-media-scanner'); ?></a>
            <a href="?page=<?php echo esc_html(sanitize_text_field($_GET['page'])); ?>&tab=other-tab&EMSC_tab_nonce=<?php echo esc_html(sanitize_text_field($EMSC_tab_nonce)); ?>" class="nav-tab <?php echo $active_tab == 'other-tab' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Help', 'unused-media-scanner'); ?></a>
        </h2>

        <?php
        if ($active_tab == 'scanner-tab') {
            require 'includes/scanner/tab-scanner-tools.php';
        } elseif ($active_tab == 'fsscanner-tab') {
            require 'includes/scanner/tab-fsscanner-tools.php';
        } elseif ($active_tab == 'info-tab') {
            require 'includes/scanner/tab-info.php';
        } elseif ($active_tab == 'other-tab') {
            require 'includes/scanner/tab-help.php';
        }

        ?>
    </div>
<?php
}

add_action('admin_menu', 'EMSC_Unused_Media_Scanner_add_management_page');
add_action('admin_init', 'EMSC_Unused_Media_Scanner_load_textdomain');

require 'includes/scanner/scanner-tools-functions.php';

// Add resources
add_action('init', 'EMSC_register_scripts');
function EMSC_register_scripts()
{
    if (is_admin()) :
        $plugin_dir = WP_PLUGIN_URL . '/unused-media-scanner';

        wp_register_style('unused-media-scanner', $plugin_dir . '/assets/style.css', null, '1.0.3');
        wp_enqueue_style('unused-media-scanner');
        wp_enqueue_script('unused-media-scanner', $plugin_dir . '/assets/script.js', array('wp-i18n', 'jquery'), '1.0.3', false);
        wp_localize_script('unused-media-scanner', 'EMSC_media_scanner_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
        wp_enqueue_script('jquery');
        wp_enqueue_script('unused-media-scanner');
        wp_set_script_translations('unused-media-scanner', 'unused-media-scanner');
    endif;
}
?>