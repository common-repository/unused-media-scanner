<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
?>
<h3><?php esc_html_e('Site Info', 'unused-media-scanner'); ?></h3>

<p><?php esc_html_e('Each time you upload an image to the Media Library, WordPress duplicates and resizes it.<br /><br />
Below is a list of all the versions it creates along with the name of that version and its dimensions (Width x Height) in pixels:', 'unused-media-scanner'); ?></p>
<ul>
    <?php
    $image_sizes = EMSC_site_thumbnail_sizes();
    foreach ($image_sizes as $key => $image_size) {
        echo "<li><strong>" . esc_html($key) . "</strong> - " . esc_html($image_size) . "</li>";
    }
    ?>
</ul>
<?php

function EMSC_site_thumbnail_sizes()
{
    global $_wp_additional_image_sizes;
    $sizes = array();
    $rSizes = array();
    foreach (get_intermediate_image_sizes() as $s) {
        $sizes[$s] = array(0, 0);
        if (in_array($s, array('thumbnail', 'medium', 'medium_large', 'large'))) {
            $sizes[$s][0] = get_option($s . '_size_w');
            $sizes[$s][1] = get_option($s . '_size_h');
        } else {
            if (isset($_wp_additional_image_sizes) && isset($_wp_additional_image_sizes[$s]))
                $sizes[$s] = array($_wp_additional_image_sizes[$s]['width'], $_wp_additional_image_sizes[$s]['height'],);
        }
    }
    foreach ($sizes as $size => $atts) {
        $rSizes[$size] = implode('x', $atts);
    }
    return $rSizes;
}
