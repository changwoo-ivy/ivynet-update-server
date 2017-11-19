<?php
/**
 * release.php
 *
 * custom post 'ius_release'
 */


add_action( 'init', 'ius_register_release' );

function ius_register_release() {
    register_post_type( 'ius_release',
        array(
            'label'                => _x( 'Releases', 'Custom post label', 'ius' ),
            'labels'               => array(
                'add_new'      => _x( 'Add New Release', 'Custom post label', 'ius' ),
                'add_new_item' => _x( 'Add New Release', 'Custom post label', 'ius' ),
                'edit_item'    => _x( 'Edit Release', 'Custom post label', 'ius' ),
            ),
            'description'          => 'Custom post for keeping release',
            'public'               => FALSE,
            'exclude_from_search'  => FALSE,
            'publicly_queryable'   => FALSE,
            'show_ui'              => TRUE,
            'show_in_nav_menus'    => FALSE,
            'show_in_menu'         => TRUE,
            'show_in_admin_bar'    => TRUE,
            'menu_icon'            => 'dashicons-archive',
            'capability_type'      => array( 'release', 'releases' ),
            'map_meta_cap'         => TRUE,
            'hierarchical'         => FALSE,
            'supports'             => array( 'title', 'excerpt' ),
            'register_meta_box_cb' => 'ius_add_meta_boxes_release',
            'taxonomies'           => array(),
            'has_archive'          => FALSE,
            'can_export'           => TRUE,
            'show_in_rest'         => TRUE,
        ) );
}

function ius_init_roles_caps_release() {

    global $wp_post_types;

    ius_register_release();

    $admin = get_role( 'administrator' );
    foreach ( $wp_post_types['ius_release']->cap as $cap ) {
        $admin->add_cap( $cap );
    }
}


function ius_deinit_roles_caps_release() {

    global $wp_post_types;

    ius_register_release();

    $admin = get_role( 'administrator' );
    foreach ( $wp_post_types['ius_release']->cap as $cap ) {
        $admin->remove_cap( $cap );
    }
}

function ius_add_meta_boxes_release() {
    add_meta_box( 'release-properties',
        __( 'Release Properties', 'ius' ),
        'ius_output_meta_box_release_properties',
        NULL,
        'advanced',
        'default' );
}

function ius_output_meta_box_release_properties() {
    /**
     * meta keys:
     * ius_release_version: the file's version
     */
    ius_render_template( 'meta-boxes/release-properties.php' );
}


add_action( 'save_post_ius_release', 'ius_save_post_ius_release', 10, 3 );

function ius_save_post_ius_release( $post_id, $post, $updated ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) || ! $post || ! $updated ) {
        return;
    }

    if ( wp_verify_nonce( $_POST['release-properties-nonce'], 'release-properties-nonce-08423' ) ) {
        $project_id = intval( ius_from_post( 'post_parent', '' ) );
        remove_action( 'save_post_ius_release', 'ius_save_post_ius_release' );
        wp_update_post( array(
            'ID'          => $post_id,
            'post_parent' => $project_id,
        ) );
        add_action( 'save_post_ius_release', 'ius_save_post_ius_release', 10, 3 );

        $release_version = sanitize_text_field( ius_from_post( 'ius_release_version', '' ) );
        update_post_meta( $post_id, 'ius_release_version', $release_version );

        $current_latest_version = get_post_meta( $project_id, 'ius_latest_version', TRUE );
        if ( ! $current_latest_version || version_compare( $current_latest_version, $release_version, '<' ) ) {
            update_post_meta( $project_id, 'ius_latest_version', $release_version );
        }

        $attach_id = ius_save_release_file( $post_id, $release_version );
        if ( is_wp_error( $attach_id ) ) {
            error_log( 'attachment failed: ' . $attach_id->get_error_message() );
        }
    }
}

function ius_save_release_file( $post_id, $version ) {

    $file = &ius_from_assoc_ref( $_FILES, 'ius_file', array() );
    if ( ! $file ) {
        return FALSE;
    }

    if ( class_exists( 'finfo' ) ) {
        $info    = new \finfo( FILEINFO_MIME_TYPE );
        $mime    = $info->file( $file['tmp_name'] );
        $allowed = array(
            'zip' => 'application/zip',
        );
        if ( FALSE === array_search( $mime, $allowed, TRUE ) ) {
            return new \WP_Error( 'invalid_file_type', "Invalid file type \'{$mime}\'" );
        }
    }

    $the_post = get_post( $post_id );
    if ( ! $the_post ) {
        return new WP_Error( 'invalid_post_id', 'Invalid release post ID: ' . $post_id );
    }

    $project_post = get_post( $the_post->post_parent );
    if ( ! $project_post ) {
        return new WP_Error( 'invalid_post_id', 'Invalid project post ID: ' . $project_post );
    }

    $slug        = ius_get_plugin_identifier( get_post_meta( $project_post->ID, 'ius_plugin_main', TRUE ) );
    $upload_dir  = wp_upload_dir();
    $release_dir = $upload_dir['basedir'] . '/ius/' . $slug;

    if ( ! file_exists( $release_dir ) ) {
        mkdir( $release_dir, 0777, TRUE );
    }

    $file_name = "{$release_dir}/{$slug}-{$version}.zip";
    $file_type = wp_check_filetype( basename( $file_name ) );

    $attachments = get_attached_media( $file_type['type'], $post_id );
    if ( $attachments ) {
        foreach ( $attachments as $attach ) {
            wp_delete_attachment( $attach->ID );
        }
    }

    if ( ! move_uploaded_file( $file['tmp_name'], $file_name ) ) {
        return new WP_Error( 'file_move_error', 'Moving uploaded file error' );
    }

    $attach_id = wp_insert_attachment(
        array(
            'guid'           => "{$upload_dir['baseurl']}/ius/{$slug}/" . basename( $file_name ),
            'post_mime_type' => $file_type['type'],
            'post_title'     => basename( $file_name ),
            'post_status'    => 'inherit',
        ),
        $file_name,
        $post_id
    );

    if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
        /** @noinspection PhpIncludeInspection */
        require_once( get_home_path() . '/wp-admin/includes/image.php' );
    }

    wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $file_name ) );

    return $attach_id;
}


add_action( 'post_edit_form_tag', 'ius_post_edit_form_tag' );

function ius_post_edit_form_tag( $post ) {
    if ( $post->post_type == 'ius_release' ) {
        echo ' enctype="multipart/form-data"';
    }
}


add_action( 'transition_post_status', 'ius_transition_post_status', 10, 3 );

function ius_transition_post_status( $new_status, $old_status, $post ) {
    if ( $post->post_type != 'ius_release' ) {
        return;
    }

    if ( $new_status == 'trash' || $new_status == 'publish' ) {
        $project = get_post( $post->post_parent );
        if ( $project ) {
            $releases = ius_get_project_releases( $project->ID );
            if ( $releases ) {
                update_post_meta( $project->ID, 'ius_latest_version', $releases[0]['version'] );
            }
        }
    }
}