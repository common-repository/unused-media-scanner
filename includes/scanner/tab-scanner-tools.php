<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
?>
<h3><?php esc_html_e('Scan for unused images in the media library', 'unused-media-scanner'); ?></h3>
<p><strong><?php esc_html_e('NOTE:', 'unused-media-scanner'); ?></strong>
    <?php esc_html_e('Before using this plugin, please ensure you create a full website backup (database and all files). We also recommend testing the plugin on an offline copy of your website, before running it on Live.', 'unused-media-scanner'); ?>
</p>
<p><?php esc_html_e('Currently the previews will only show images, but other files will still appear without a preview.', 'unused-media-scanner'); ?>
</p>

<fieldset>
    <?php
    $scan_nonce = wp_create_nonce("media_scanner_nonce");
    $del_nonce = wp_create_nonce("media_delete_nonce");
    ?>
    <button id="media_scanner" data-nonce="<?php echo esc_html($scan_nonce); ?>"><?php esc_html_e('Run library scan', 'unused-media-scanner'); ?></button>

    <div class="wrap" id="media_scanner_results">
        <h2 class="nav-tab-wrapper">
            <a href="#unused" class="nav-tab nav-js-tab nav-tab-active"><?php esc_html_e('Unused results', 'unused-media-scanner'); ?></a>
            <a href="#used" class="nav-tab nav-js-tab"><?php esc_html_e('Used results', 'unused-media-scanner'); ?></a>
        </h2>
        <div id="unused">
            <h3><?php esc_html_e('Unused images', 'unused-media-scanner'); ?> - <span class="count_unused"></span></h3>
            <div id="delete_panel">
                <hr />
                <button id="media_remove" data-nonce="<?php echo esc_html($del_nonce); ?>"><?php esc_html_e('Delete selected', 'unused-media-scanner'); ?></button>
                <?php
                if (MEDIA_TRASH) :
                ?>
                    <label for="media_remove_check_perm"><?php esc_html_e('Permanently delete', 'unused-media-scanner'); ?></label>
                    <input type="checkbox" id="media_remove_check_perm" checked="" />
                <?php
                endif;
                ?>
            </div>
            <div id="content_unused"></div>
        </div>
        <div id="used">
            <h3><?php esc_html_e('Used images', 'unused-media-scanner'); ?> - <span class="count_used"></span></h3>
            <div id="content_used"></div>
        </div>
    </div>
</fieldset>