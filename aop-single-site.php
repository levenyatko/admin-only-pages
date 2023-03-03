<?php
/**
 * Plugin settings for single website
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

add_action( 'admin_menu', 'aop_add_singlesite_settings_page' );
add_action( 'admin_init', 'aop_register_settings' );

function aop_add_singlesite_settings_page()
{
    add_submenu_page(
        'options-general.php',
        __('Settings', 'aop'),
        __('Closed Pages', 'aop'),
        'manage_options',
        'aop-settings',
        'aop_settings_page_display'
    );

}

function aop_register_settings()
{
    $section_args = [
        'before_section' => __( 'Select the pages you want to allow only administrators to edit', 'aop' )
    ];

    add_settings_section( 'aop_closed_pages_section', '', null, 'aop_closed_pages_settings', $section_args );

    $args = [
        'id'                => 'closed-pages-list-id',
        'label_for'         => 'closed-pages-list-id',
        'name'              => 'closed-pages-list',
        'section'           => 'aop_closed_pages_section',
        'default'           => [],
        'type'              => 'page_checkbox',
    ];

    add_settings_field( "closed-pages-list-id", __('Pages', 'aop'), 'aop_settings_fields_display', 'aop_closed_pages_settings',
        'aop_closed_pages_section', $args );

    register_setting(
        'aop_closed_pages_settings',
        'closed-pages-list'
    );
}

/*
 * Display the page and settings fields
 */
function aop_settings_page_display()
{
    global $wp_settings_sections;
    ?>
    <div class="wrap">
        <h1><?php echo get_admin_page_title(); ?></h1>
        <?php
        if ( ! empty($wp_settings_sections) ) {
            foreach ($wp_settings_sections as $section_id => $section) {
                if ( false === strpos($section_id, 'aop_') ) {
                    continue;
                }

                ?>
                <form method="POST" action="options.php">
                    <?php
                        settings_fields( $section_id );
                        do_settings_sections( $section_id );
                        submit_button();
                    ?>
                </form>
                <?php
            }
        }
        ?>
    </div>
    <?php
}

function aop_settings_fields_display($args)
{
    $query_args = [
        'posts_per_page' => -1,
        'post_type' => 'page',
        'post_status' => 'any',
        'fields' => 'ids',
    ];

    $pages_query = new WP_Query( $query_args );

    $selected_pages = get_option($args['name']);
    if ( empty($selected_pages) ) {
        $selected_pages = $args['default'];
    }

    if ( $pages_query->have_posts() ) { ?>
        <fieldset>
            <?php
            foreach ( $pages_query->posts as $page_id ) {
                ?>
                <label>
                    <input type="checkbox" name="<?php echo $args['name']; ?>[]" value="<?php echo $page_id ?>" <?php
                    echo
                    (in_array
                    ($page_id,
                        $selected_pages)) ? 'checked="checked"' : ''; ?>>
                    <?php echo get_the_title($page_id); ?>
                </label>
                <br>
                <?php
            }
            ?>
        </fieldset>
    <?php } else { ?>
        <strong><?php _e('There are no pages in this site', 'aop') ?></strong>
        <?php
    }

}

