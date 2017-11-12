<?php
/**
 * release.php
 *
 * custom post 'ius_release'
 */


add_action( 'init', 'ius_register_release' );

function ius_register_release() {
    register_post_type( 'ius_release', array(
        'label'                => _x( 'Releases', 'Custom post label', 'ius' ),
        'labels'               => array(),
        'description'          => 'Custom post for keeping release',
        'public'               => TRUE,
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
    add_meta_box( 'release-properties', __( 'Release Properties', 'ius' ), 'ius_output_meta_box_release_properties', NULL, 'advanced', 'default' );
}

function ius_output_meta_box_release_properties( $post ) {
    /**
     * meta keys:
     * ius_release_version: the file's version
     */
}