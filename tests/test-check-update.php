<?php

class Test_Check_Update extends WP_UnitTestCase {

    public static function setUpBeforeClass() {
        global $ius;
        $ius->activation();
    }

    public function test_get_request_param() {
        $sample_request_content = file_get_contents( __DIR__ . '/sample-request.json' );
        $sample_request         = json_decode( $sample_request_content, TRUE );

        $value = ius_get_request_param( $sample_request, 'plugins' );

        $this->assertInstanceOf( 'stdClass', $value );
    }

    public function test_ius_check_update_10() {

        // TODO: 테스트 코드 잘못됨. 테스트 코드 릴리즈에 모두 attachment 붙여 줘야 함.

        // prepare ius-fake-4-test/ius-fake-4-test.php plugin.
        $project = $this->factory()->post->create_and_get( array(
            'post_title'  => 'Fake 4 Test',
            'post_status' => 'publish',
            'post_type'   => 'ius_project',
            'meta_input'  => array(
                'ius_plugin_main'    => 'ius-fake-4-test/ius-fake-4-test.php',
                'ius_latest_version' => '1.0.1',
            ),
        ) );

        wp_set_post_terms( $project->ID, 'active', 'project-status' );


        // this project has 2 releases, 1.0.0, and 1.0.1.
        $this->factory()->post->create( array(
            'post_title'  => 'Fake 4 Test Ver 1.0.0',
            'post_status' => 'publish',
            'post_type'   => 'ius_release',
            'post_parent' => $project->ID,
            'meta_input'  => array(
                'ius_release_version' => '1.0.0',
            ),
        ) );

        $this->factory()->post->create( array(
            'post_title'  => 'Fake 4 Test Ver 1.0.1',
            'post_status' => 'publish',
            'post_type'   => 'ius_release',
            'post_parent' => $project->ID,
            'meta_input'  => array(
                'ius_release_version' => '1.0.1',
            ),
        ) );

        // for test: another plugin but the test request does not have this one.
        $unknown = $this->factory()->post->create_and_get( array(
            'post_title'  => 'Unknown Project',
            'post_status' => 'publish',
            'post_type'   => 'ius_project',
            'meta_input'  => array(
                'ius_plugin_main'    => 'unknown/unknown.php',
                'ius_latest_version' => '1.0.0',
            ),
        ) );

        wp_set_post_terms( $unknown->ID, 'active', 'project-status' );

        // this project has 2 releases, 1.0.0, and 1.0.1.
        $this->factory()->post->create( array(
            'post_title'  => 'Unknown Project Ver 1.0.0',
            'post_status' => 'publish',
            'post_type'   => 'ius_release',
            'post_parent' => $unknown->ID,
            'meta_input'  => array(
                'ius_release_version' => '1.0.0',
            ),
        ) );

        // this request has ius-fake-4-test/ius-fake-4-test.php
        $sample_request = json_decode( file_get_contents( __DIR__ . '/sample-request.json' ), TRUE );
        $output         = ius_check_update_10( $sample_request );

        $this->assertArrayHasKey( 'response', $output );
        $this->assertArrayHasKey( 'translations', $output );
        $this->assertArrayHasKey( 'no_update', $output );

        // one project can be updated.
        $this->assertArrayHasKey( 'ius-fake-4-test/ius-fake-4-test.php', $output['response'] );
    }
}