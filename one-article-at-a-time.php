<?php
/*
Plugin Name: One Article at a Time
Description: Allows contributors to submit only one article at a time and waits for approval before submitting another.
Version: 1.0
Author: Your Name
*/

// Register custom post status 'Awaiting Approval'
function oaat_register_custom_post_status() {
    register_post_status('awaiting_approval', array(
        'label' => 'Awaiting Approval',
        'public' => false,
        'exclude_from_search' => true,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Awaiting Approval <span class="count">(%s)</span>', 'Awaiting Approval <span class="count">(%s)</span>')
    ));
}
add_action('init', 'oaat_register_custom_post_status');

// Limit the number of articles a contributor can submit to one
function oaat_limit_contributor_submissions($can_publish, $post_data) {
    $current_user = wp_get_current_user();
    if ($post_data['post_type'] === 'post' && $post_data['post_status'] !== 'draft' && $current_user->ID !== 0 && in_array('contributor', $current_user->roles)) {
        $published_posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => array('publish', 'awaiting_approval'),
            'author' => $current_user->ID,
            'fields' => 'ids'
        ));
        if (!empty($published_posts)) {
            $can_publish = false;
        }
    }
    return $can_publish;
}
add_filter('wp_insert_post_empty_content', 'oaat_limit_contributor_submissions', 10, 2);

// Set 'Awaiting Approval' status for submitted articles
function oaat_set_post_status_awaiting_approval($post_id, $post) {
    if ($post->post_type === 'post' && $post->post_status === 'pending') {
        $current_user = wp_get_current_user();
        if (in_array('contributor', $current_user->roles)) {
            wp_update_post(array('ID' => $post_id, 'post_status' => 'awaiting_approval'));
        }
    }
}
add_action('pending_to_awaiting_approval', 'oaat_set_post_status_awaiting_approval', 10, 2);

// Add a notice for contributors when trying to submit another article
function oaat_contributor_notice() {
    $current_user = wp_get_current_user();
    if (in_array('contributor', $current_user->roles) && $current_user->ID !== 0) {
        $published_posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => array('publish', 'awaiting_approval'),
            'author' => $current_user->ID,
            'fields' => 'ids'
        ));
        if (!empty($published_posts)) {
            echo '<div class="notice notice-warning"><p>You can only submit one article at a time. Please wait for the previous article to be approved before submitting another.</p></div>';
        }
    }
}
add_action('admin_notices', 'oaat_contributor_notice');
