<?php
/**
 * admin/release.php
 */

/**
 * Add custom columns to the list header.
 */
add_filter( 'manage_ius_release_posts_columns', 'ius_custom_columns_release' );

function ius_custom_columns_release( $columns ) {

    $date_idx = NULL;

    foreach ( array_keys( $columns ) as $idx => $key ) {
        if ( $key == 'date' ) {
            $date_idx = $idx;
            break;
        }
    }

    if ( ! is_null( $date_idx ) ) {
        $new_columns            = array_slice( $columns, 0, $date_idx );
        $new_columns['project'] = __( 'Project', 'ius' );
        $new_columns['version'] = __( 'Version', 'ius' );
        $new_columns['file']    = __( 'File', 'ius' );
        $new_columns            += array_slice( $columns, $date_idx );

        return $new_columns;
    }

    $columns['project'] = __( 'Project', 'ius' );
    $columns['version'] = __( 'Version', 'ius' );
    $columns['file']    = __( 'File', 'ius' );

    return $columns;
}


/**
 * Add custom field values to the list.
 * 프로젝트 각 칼럼에 값을 추가
 */
add_action( 'manage_ius_release_posts_custom_column', 'ius_custom_column_release_values', 10, 2 );

function ius_custom_column_release_values( $column_name, $post_id ) {
    switch ( $column_name ) {
        case 'project':
            $project_post = get_post( wp_get_post_parent_id( $post_id ) );
            if ( $project_post ) {
                printf( '<a href="%s">%s</a>',
                    esc_url( get_edit_post_link( $project_post->ID ) ),
                    esc_html( $project_post->post_title ) );
            }
            break;

        case 'version':
            echo esc_html( get_post_meta( $post_id, 'ius_release_version', TRUE ) );
            break;

        case 'file':
            $attachments = get_attached_media( 'application/zip', $post_id );
            if ( $attachments ) {
                $file_url = wp_get_attachment_url( key( $attachments ) );
                if ( $file_url ) {
                    printf( '<a href="%s">%s</a>', esc_url( $file_url ), esc_html( basename( $file_url ) ) );
                }
            }

            break;
    }
}


/**
 * Quick edit
 */
add_action( 'quick_edit_custom_box', 'ius_quick_edit_custom_box_release', 10, 3 );

function ius_quick_edit_custom_box_release( $column_name, $post_type, $taxonomy ) {
    if ( $column_name == 'taxonomy-project-status' && $post_type == 'ius_project' ) {
        ius_render_template( 'quick-edit/project-status.php' );
    }
}


