<?php

class IUS_Launcher {
    public function launch() {
        require_once IUS_DIR . '/src/custom-posts/project.php';
        require_once IUS_DIR . '/src/custom-posts/release.php';
        require_once IUS_DIR . '/src/taxonomies/project-status.php';
        require_once IUS_DIR . '/src/rewrites/rewrite.php';
        require_once IUS_DIR . '/src/functions.php';

        if ( is_admin() ) {
            require_once IUS_DIR . '/src/admin/projects.php';
            require_once IUS_DIR . '/src/admin/release.php';
        }

        register_activation_hook( IUS_MAIN, array( $this, 'activation' ) );
        register_deactivation_hook( IUS_MAIN, array( $this, 'deactivation' ) );

        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
    }

    public function activation() {
        $this->init_roles_capabilities();
        $this->init_rewrites();
        $this->init_taxonomies();
        flush_rewrite_rules();
    }

    public function deactivation() {
        $this->deinit_roles_capabilities();
        $this->deinit_rewrites();
        flush_rewrite_rules();
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'ius', FALSE, basename( IUS_DIR ) . '/languages' );
    }

    private function init_roles_capabilities() {
        ius_init_roles_caps_project();
        ius_init_roles_caps_release();
    }

    private function init_taxonomies() {
        ius_init_taxonomy_project_status();
    }

    private function deinit_roles_capabilities() {
        ius_deinit_roles_caps_project();
        ius_deinit_roles_caps_release();
    }

    private function init_rewrites() {
        ius_rewrite_rules();
    }

    private function deinit_rewrites() {

    }
}
