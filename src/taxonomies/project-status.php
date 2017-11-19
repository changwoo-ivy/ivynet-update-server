<?php
/**
 * project-status.php
 *
 * file for taxonomy 'project-status'.
 */

add_action( 'init', 'ius_register_taxonomy_project_status' );

function ius_register_taxonomy_project_status() {
    register_taxonomy( 'project-status',
        'ius_project',
        array(
            'label'             => _x( 'Project Statuses', 'taxonomy label', 'ius' ),
            'public'            => FALSE,
            'hierarchical'      => FALSE,
            'show_admin_column' => TRUE,
        ) );
}

function ius_init_taxonomy_project_status() {

    ius_register_taxonomy_project_status();

    if ( ! term_exists( 'active', 'project-status' ) ) {
        wp_insert_term( 'Active', 'project-status', array( 'slug' => 'active' ) );
    }

    if ( ! term_exists( 'closed', 'project-status' ) ) {
        wp_insert_term( 'Closed', 'project-status', array( 'slug' => 'closed' ) );
    }
}
