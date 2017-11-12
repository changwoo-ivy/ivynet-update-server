<?php
/**
 * project.php
 *
 * custom post 'ius_project'
 */

add_action( 'init', 'ius_register_project' );

function ius_register_project() {
    register_post_type( 'ius_project', array(
        'label'                => _x( 'Projects', 'Custom post label', 'ius' ),
        'labels'               => array(),
        'description'          => 'Custom post for keeping projects',
        'public'               => TRUE,
        'menu_icon'            => 'dashicons-feedback',
        'capability_type'      => array( 'project', 'projects' ),
        'map_meta_cap'         => TRUE,
        'hierarchical'         => TRUE,
        'supports'             => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
        'register_meta_box_cb' => 'ius_add_meta_boxes_project',
        'taxonomies'           => array( 'project-status' ),
        'has_archive'          => TRUE,
        'can_export'           => TRUE,
        'show_in_rest'         => TRUE,
    ) );
}

function ius_init_roles_caps_project() {

    global $wp_post_types;

    ius_register_project();

    $admin = get_role( 'administrator' );
    foreach ( $wp_post_types['ius_project']->cap as $cap ) {
        $admin->add_cap( $cap );
    }
}


function ius_deinit_roles_caps_project() {

    global $wp_post_types;

    ius_register_project();

    $admin = get_role( 'administrator' );
    foreach ( $wp_post_types['ius_project']->cap as $cap ) {
        $admin->remove_cap( $cap );
    }
}


function ius_add_meta_boxes_project() {
    /**
     * meta keys:
     * ius_plugin_main
     * ius_latest_version
     */
    add_meta_box( 'project-status', __( 'Project Status', 'ius' ), 'ius_output_meta_box_project_status', NULL, 'side', 'default' );
}


function ius_output_meta_box_project_status( $post ) {
    $selected = wp_get_post_terms( $post->ID, 'project-status', array( 'number' => 1 ) );
    ius_render_template( 'meta-boxes/project-status.php', array(
        'options'  => get_terms( array( 'taxonomy' => 'project-status', 'hide_empty' => FALSE ) ),
        'selected' => $selected ? $selected[0]->slug : FALSE,
    ) );
}


add_action( 'save_post_ius_project', 'ius_save_post_ius_project', 10, 3 );

function ius_save_post_ius_project( $post_id, $post, $updated ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) || ! $post || ! $updated ) {
        return;
    }

    if ( ! wp_verify_nonce( $_POST['project-status-nonce'], 'nonce-project-status-1876' ) ) {
        return;
    }

    $project_status = ius_from_request( 'project-status' );
    if ( $project_status ) {
        $terms = wp_get_post_terms( $post_id, 'project-status' );
        if ( count( $terms ) == 1 ) {
            if ( $terms[0]->slug == $project_status ) {
                return;
            }
        }
        wp_delete_object_term_relationships( $post_id, 'project-status' );
        wp_add_object_terms( $post_id, $project_status, 'project-status' );
    } else {
        wp_delete_object_term_relationships( $post_id, 'project-status' );
    }
}