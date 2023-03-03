<?php
/**
 * Plugin Name:       Admin only pages
 * Description:       The plugin allows you to select pages that can only be edited by administrator.
 * Version:           1.0.0
 * Author:            Daria Levchenko
 * Author URI:        https://github.com/levenyatko
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Add actions with settings
 */
if ( is_multisite() ) {
    require_once plugin_dir_path( __FILE__ ) . 'aop-multisite.php';
} else {
    require_once plugin_dir_path( __FILE__ ) . 'aop-single-site.php';
}

/**
 * Add statuses to pages and check user capabilities
 */
add_action( 'init', 'aop_maybe_add_user_caps_check' );

function aop_maybe_add_user_caps_check()
{
    global $aopPagesSettings;

    $aopPagesSettings = get_option('closed-pages-list');

    if ( empty($aopPagesSettings) ) {
        $aopPagesSettings = [];
    }

    if ( ! current_user_can('manage_options') && current_user_can('edit_pages') ) {
        add_filter( 'display_post_states', 'aop_add_admin_only_state', 10, 2 );
        add_filter( 'user_has_cap', 'aop_maybe_grant_pages_capability_cap', 100, 4 );
    }

}


function aop_add_admin_only_state( $post_states, $post )
{
    global $aopPagesSettings;
    if ( ! empty($aopPagesSettings) && in_array($post->ID, $aopPagesSettings) ) {
        $post_states['admin-only'] = '<span style="color: #b32d2e;">' . __('Admins only', 'aop') . '</span>';
    }
    return $post_states;
}

function aop_maybe_grant_pages_capability_cap( $allcaps, $caps, $args, $user )
{
    global $aopPagesSettings;

    if ( ! empty($args[2]) ) {
        if ( ! empty($aopPagesSettings) && in_array($args[0], ['edit_post', 'delete_post']) ) {
            if ( in_array($args[2], $aopPagesSettings) ) {
                $allcaps['edit_published_pages'] = false;
                $allcaps['delete_pages'] = false;
                $allcaps['delete_published_pages'] = false;
            }
        }
    }

    return $allcaps;
}

