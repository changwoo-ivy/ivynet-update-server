<?php
/**
 * admin/projects.php
 */

/**
 * Add custom columns to the list header.
 */
add_filter( 'manage_ius_project_posts_columns', 'ius_custom_columns_project' );

function ius_custom_columns_project( $columns ) {

    $date_idx = NULL;

    foreach ( array_keys( $columns ) as $idx => $key ) {
        if ( $key == 'date' ) {
            $date_idx = $idx;
            break;
        }
    }

    if ( ! is_null( $date_idx ) ) {
        $new_columns                   = array_slice( $columns, 0, $date_idx );
        $new_columns['plugin_main']    = __( 'Plugin Main', 'ius' );
        $new_columns['latest_version'] = __( 'Latest Version', 'ius' );
        $new_columns['num_releases']   = __( 'Num. of Releases', 'ius' );
        $new_columns                   += array_slice( $columns, $date_idx );

        return $new_columns;
    }

    $columns['plugin_main']    = __( 'Plugin Main', 'ius' );
    $columns['latest_version'] = __( 'Latest Version', 'ius' );
    $columns['num_releases']   = __( 'Latest Version', 'ius' );

    return $columns;
}


/**
 * Add custom field values to the list.
 * 프로젝트 각 칼럼에 값을 추가
 */
add_action( 'manage_ius_project_posts_custom_column', 'ius_custom_column_project_values', 10, 2 );

function ius_custom_column_project_values( $column_name, $post_id ) {
    switch ( $column_name ) {
        case 'plugin_main':
            echo esc_html( get_post_meta( $post_id, 'ius_plugin_main', TRUE ) );
            break;

        case 'latest_version':
            echo esc_html( get_post_meta( $post_id, 'ius_latest_version', TRUE ) );
            break;

        case 'num_releases':
            /** @var wpdb $wpdb */
            global $wpdb;
            $count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_parent=%d AND post_type=%s AND post_status='publish'",
                $post_id,
                'ius_release'
            ) );
            echo intval( $count );
            break;
    }
}


/**
 * Filter for project status.
 */
add_action( 'restrict_manage_posts', 'ius_manage_project', 10, 2 );

function ius_manage_project( $post_type, $which ) {
    if ( $post_type == 'ius_project' && $which == 'top' ) {
        wp_dropdown_categories(
            array(
                'show_option_all' => __( 'All Statuses', 'ius' ),
                'taxonomy'        => 'project-status',
                'hide_if_empty'   => FALSE,
                'name'            => 'project-status',
                'value_field'     => 'slug',
            )
        );
    }
}


/**
 * Quick edit
 */
add_action( 'quick_edit_custom_box', 'ius_quick_edit_custom_box_project', 10, 3 );

function ius_quick_edit_custom_box_project( $column_name, $post_type, $taxonomy ) {
    if ( $column_name == 'taxonomy-project-status' && $post_type == 'ius_project' ) {
        ius_render_template( 'quick-edit/project-status.php' );
    }
}


