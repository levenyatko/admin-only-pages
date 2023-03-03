<?php
/**
 * Plugin settings for multisite
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/*
* Add Custom Tabs with Options on “Edit Site” Multisite Settings page
* see: https://rudrastyh.com/wordpress-multisite/custom-tabs-with-options.html
*/
add_filter( 'network_edit_site_nav_links', 'aop_network_settings_tab' );
add_action( 'network_admin_menu', 'aop_add_settings_page' );
add_action( 'admin_head', 'aop_hide_added_submenu' );
add_action( 'network_admin_edit_adminpagesupdate', 'aop_save_subsite_pages_options' );
add_action( 'network_admin_notices', 'aop_show_settings_notices' );
add_action( 'current_screen', 'aop_check_redirects_to_settings' );

function aop_network_settings_tab( $tabs )
{
    $tabs['admin-only-pages'] = [
        'label' => __('Closed Pages', 'aop'),
        'url' => 'sites.php?page=admin-only-pages',
        'cap' => 'manage_sites'
    ];
    return $tabs;
}

/*
 * Add submenu page under Sites
 */
function aop_add_settings_page()
{
    add_submenu_page(
        'sites.php',
        __('Edit website', 'aop'),
        __('Edit website', 'aop'),
        'manage_network_options',
        'admin-only-pages',
        'aop_network_website_settings_display'
    );

}

/*
 * Some CSS tricks to hide the link to our custom submenu page
 */
function aop_hide_added_submenu()
{
    echo '<style>#menu-site .wp-submenu li:last-child{display:none;}</style>';
}

/*
 * Display the page and settings fields
 */
function aop_network_website_settings_display()
{
    // current blog id
    $id = $_REQUEST['id'];

    ?>
    <div class="wrap">
        <h1 id="edit-site"><?php _e('Closed Pages', 'aop'); ?></h1>
        <p class="edit-site-actions"><a href="<?php echo esc_url( get_home_url( $id, '/' ) ); ?>"><?php _e( 'Visit' ); ?></a> | <a href="<?php echo esc_url( get_admin_url( $id ) ); ?>"><?php _e( 'Dashboard' ); ?></a></p>

    <?php
    // navigation tabs
    network_edit_site_nav( [
        'blog_id'  => $id,
        'selected' => 'admin-only-pages' // current tab
    ]);

    // change blog to get pages list only for selected website
    switch_to_blog($id);

    // get all blog pages list
    $args = [
        'posts_per_page' => -1,
        'post_type' => 'page',
        'post_status' => 'any',
        'fields' => 'ids',
    ];

    $pages_query = new WP_Query( $args );

    $selected_pages = get_blog_option( $id, 'closed-pages-list');
    if ( empty($selected_pages) ) {
        $selected_pages = [];
    }

    ?>
    <style>
        #menu-site .wp-submenu li.wp-first-item{
            font-weight:600;
        }
        #menu-site .wp-submenu li.wp-first-item a{
            color:#fff;
        }
    </style>
    <form method="post" action="edit.php?action=adminpagesupdate">
        <?php wp_nonce_field( 'aop-closed-pages-' . $id ); ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>" />
        <table class="form-table">
            <tr>
                <th scope="row">
                    <?php _e('Select the pages you want to allow only administrators to edit','aop'); ?>
                </th>
            </tr>
            <tr>
                <td>
                    <?php if ( $pages_query->have_posts() ) { ?>
                        <fieldset>
                            <?php
                                foreach ( $pages_query->posts as $page_id ) {
                                    ?>
                                    <label>
                                        <input type="checkbox" name="closed-pages-list[]" value="<?php echo $page_id ?>" <?php echo (in_array($page_id, $selected_pages)) ? 'checked="checked"' : ''; ?>>
                                        <?php echo get_the_title($page_id); ?>
                                    </label>
                                    <br>
                                    <?php
                                }
                            ?>
                        </fieldset>
                    <?php } else { ?>
                        <strong><?php _e('There are no pages in this site', 'aop'); ?></strong>
                    <?php } ?>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
    <?php

    // restore previous blog after query
    restore_current_blog();

    echo '</div>';
}

/*
 * Save settings
 */
function aop_save_subsite_pages_options()
{
    $blog_id = $_POST['id'];

    check_admin_referer('aop-closed-pages-' . $blog_id);

    update_blog_option( $blog_id, 'closed-pages-list', $_POST['closed-pages-list'] );

    wp_redirect( add_query_arg( [
        'page' => 'admin-only-pages',
        'id' => $blog_id,
        'updated' => 'true'],
        network_admin_url('sites.php')
    ));
    // redirect to /wp-admin/sites.php?page=admin-only-pages&blog_id=ID&updated=true

    exit;
}

function aop_show_settings_notices()
{
    if ( isset( $_GET['updated'] ) && isset( $_GET['page'] ) && $_GET['page'] == 'admin-only-pages' ) {
        ?>
        <div id="message" class="updated notice is-dismissible">
			<p><?php _e('Closed pages settings updated.', 'aop');?></p>
		</div>
        <?php
    }

}

function aop_check_redirects_to_settings()
{
    // do nothing if we are on another page
    $screen = get_current_screen();

    if( $screen->id !== 'sites_page_admin-only-pages-network' ) {
        return;
    }

    // $id is a blog ID
    $id = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;

    if ( ! $id ) {
        wp_die( __('Invalid site ID.', 'aop') );
    }

    $details = get_site( $id );
    if ( ! $details ) {
        wp_die( __( 'The requested site does not exist.', 'aop' ) );
    }

}
