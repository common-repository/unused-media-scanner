<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
?>
<?php
// Include wordpress bootstrap
//$parse_uri = explode('wp-content', $_SERVER['SCRIPT_FILENAME']);
//require_once($parse_uri[0] . 'wp-load.php');

add_action('wp_ajax_EMSC_media_scanner', 'EMSC_media_scanner');

function EMSC_media_scanner()
{

    if (!isset($_POST['media_scanner_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['media_scanner_nonce'])), 'media_scanner_nonce')) {
        exit("No naughty business please");
    }

    //global $wpdb; // this is how you get access to the database

    $include_drafts = intval(sanitize_text_field($_REQUEST['include_drafts']));
    $include_revision = intval(sanitize_text_field($_REQUEST['include_revision']));

    $media_found = EMSC_media_scanner_results($include_drafts, $include_revision);
    echo wp_json_encode($media_found);

    wp_die(); // this is required to terminate immediately and return a proper response
}

add_action('wp_ajax_nopriv_EMSC_media_scanner', 'EMSC_media_scanner_login');

function EMSC_media_scanner_login()
{
    esc_html_e('You must log in to scan', 'unused-media-scanner');
    wp_die();
}


function EMSC_media_scanner_results($include_drafts, $include_revision)
{
    $media_no_attach = 0;

    // 1. get uploaded media ids
    $attach_args = array(
        'post_type' => 'attachment',
        'numberposts' => -1,
        'post_status' => null,
        'post_parent' => null, // any parent
    );
    $attachments = get_posts($attach_args);
    $attachment_list = [];


    if ($attachments) {
        foreach ($attachments as $post) {
            setup_postdata($post);
            $post_id = $post->ID;

            $attachment_url = wp_get_attachment_url($post->ID);
            $attachment_url_local = explode(site_url(), $attachment_url)[1];

            //echo $attachment_url_local . "<br />";

            $path_info = pathinfo($attachment_url_local);
            $extension = $path_info['extension'];
            $attachment_url_bare = $path_info['dirname'] . '/' . $path_info['filename'];

            $attachment_item = array(
                'id' => $post_id,
                'url' => $attachment_url_local,
                'url_bare' => $attachment_url_bare,
                'extension' =>  $extension
            );

            array_push($attachment_list, $attachment_item);
        }
    }

    //$attachment_list = EMSC_unique_multidim_array($attachment_list, 'url');

    // 2. get post ids which we will scan through    
    $post_args = array(
        'posts_per_page'   => -1,
        'post_type'        => get_post_types('', 'names'),
        'post_status' => 'any, trash, auto-draft',
        'orderby'          => 'date',
        'order'            => 'ASC',
    );

    $posts = get_posts($post_args);
    $post_ids = array();

    if ($posts) {

        foreach ($posts as $post) {
            setup_postdata($post);
            $post_id = $post->ID;
            $post_type = get_post_type($post);
            $post_parent_id = $post->post_parent;
            $post_title = $post->post_title;
            $post_edit_link = get_edit_post_link($post);

            array_push($post_ids, $post_id);

            // Scan content for attachments
            $content = $post->post_content;

            foreach ($attachment_list as $k => $attachment) {

                if (isset($attachment_list[$k]) && isset($attachment['url_bare'])) {

                    //if (str_contains(strtolower($content), strtolower($attachment['url_bare']))) {
                    if (str_contains($content, $attachment['url_bare'])) {


                        if ($post_type == 'revision') {
                            $attach_ref = array('id' => $post_id, 'parent_id' => $post_parent_id, 'type' => $post_type, 'title' => $post_title, 'edit_link' => $post_edit_link);
                        } else {
                            $attach_ref = array('id' => $post_id, 'type' => $post_type, 'title' => $post_title, 'edit_link' => $post_edit_link);
                        }

                        if (!isset($attachment_list[$k]['ref'])) {
                            $attachment_list[$k]['ref'] = [];
                        }
                        array_push($attachment_list[$k]['ref'], $attach_ref);
                    }
                }
            }
        }
    }

    // 3. scan post meta for _thumbnail_id matches and remove  
    global $wpdb;
    $meta_results = $wpdb->query("SELECT * FROM {$wpdb->postmeta}"); //db call ok; no-cache ok

    foreach ($meta_results as $meta) {
        $meta_key = $meta->meta_key;
        $meta_value = $meta->meta_value;
        $post_id = intval($meta->post_id);
        $post_type = get_post_type($post_id);
        $post_parent_id = wp_get_post_parent_id($post_id);
        $meta_type = $meta_key;

        $post_title = get_the_title($post_id);
        $post_edit_link = get_edit_post_link($post_id);

        // is this metadata valid for checking
        if (in_array($post_id, $post_ids)) {
            // is this key what we want
            if ($meta_key == '_thumbnail_id' || str_contains($meta_key, 'gallery') || str_contains($meta_key, 'ids')) {
                if (in_array($meta_value, array_column($attachment_list, "id"))) {
                    if (($k = array_search($meta_value, array_column($attachment_list, 'id'))) !== false) {

                        if ($post_type == 'revision') {
                            $attach_ref = array('id' => $post_id, 'parent_id' => $post_parent_id, 'type' => $post_type, 'meta_type' => $meta_type, 'title' => $post_title, 'edit_link' => $post_edit_link);
                        } else {
                            $attach_ref = array('id' => $post_id, 'type' => $post_type, 'meta_type' => $meta_type, 'title' => $post_title, 'edit_link' => $post_edit_link);
                        }

                        if (!isset($attachment_list[$k]['ref'])) {
                            $attachment_list[$k]['ref'] = [];
                        }
                        array_push($attachment_list[$k]['ref'], $attach_ref);
                    }
                }
            }
        }
    }



    return array_filter($attachment_list);
    wp_die();
}

function EMSC_unique_multidim_array($array, $key)
{
    $temp_array = array();
    $i = 0;
    $key_array = array();

    foreach ($array as $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
        }
        $i++;
    }

    return $temp_array;
}

/*error_reporting(E_ALL);
ini_set("display_errors", 1);*/

// DELETE MEDIA
add_action('wp_ajax_EMSC_media_delete', 'EMSC_media_delete');
function EMSC_media_delete()
{
    if (!isset($_POST['media_delete_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['media_delete_nonce'])), 'media_delete_nonce')) {
        exit("No naughty business please");
    }

    //global $wpdb; // this is how you get access to the database

    $media_ids = sanitize_text_field($_REQUEST['media_ids']);
    $perm_delete = sanitize_text_field($_REQUEST['perm_delete']);
    $media_ids = array_map('sanitize_text_field', $media_ids);

    //$media_ids_array = explode(', ', $media_ids); 
    $deleted_ids_array = array();

    if (is_array($media_ids) || is_object($media_ids)) {
        foreach ($media_ids as $media_id) //loop over values
        {
            if (wp_delete_attachment($media_id, $perm_delete)) {
                array_push($deleted_ids_array, $media_id);
                //echo sprintf(__('Attachment ID [%s] has been deleted!', 'unused-media-scanner'), $media_id);
            }
        }
    }

    echo esc_html(implode(',', $deleted_ids_array));
    wp_die();
}

add_action('wp_ajax_nopriv_EMSC_media_delete', 'EMSC_media_delete_login');

function EMSC_media_delete_login()
{
    esc_html_e('You must log in to delete', 'unused-media-scanner');
    wp_die();
}
