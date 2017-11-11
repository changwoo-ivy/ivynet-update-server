<?php

class IUS_Launcher {
    public function launch() {
        require_once IUS_DIR . '/src/models/custom-posts/project.php';
        require_once IUS_DIR . '/src/models/custom-posts/release.php';
        require_once IUS_DIR . '/src/models/taxonomies/project-status.php';
        require_once IUS_DIR . '/src/functions.php';

        register_activation_hook( IUS_MAIN, array( $this, 'activation' ) );
        register_deactivation_hook( IUS_MAIN, array( $this, 'deactivation' ) );
    }

    public function activation() {
        $this->init_roles_capabilities();
        flush_rewrite_rules();
    }

    public function deactivation() {
        $this->deinit_roles_capabilities();
        flush_rewrite_rules();
    }

    private function init_roles_capabilities() {
        ius_init_roles_caps_project();
        ius_init_roles_caps_release();
        ius_init_taxonomy_project_status();
    }

    private function deinit_roles_capabilities() {
        ius_deinit_roles_caps_project();
        ius_deinit_roles_caps_release();
    }
}
