<?php
/**
 */

global $post;

?>
<table class="form-table">
  <tr>
    <th>
      <label for="ius_project_id">
          <?php esc_html_e( 'Project', 'ius' ); ?>
      </label>
    </th>
    <td>
        <?php wp_dropdown_pages( array(
            'show_option_none' => __( 'Choose a project', 'ius' ),
            'name'             => 'post_parent',
            'selected'         => $post->post_parent,
            'post_type'        => 'ius_project',
        ) ); ?>
    </td>
  </tr>
  <tr>
    <th>
      <label for="ius_release_version">
          <?php esc_html_e( 'Version', 'ius' ); ?>
      </label>
    </th>
    <td>
      <input type="text"
             class="text"
             id="ius_release_version"
             name="ius_release_version"
             value="<?php echo esc_attr( get_post_meta( $post->ID, 'ius_release_version', TRUE ) ); ?>"/>
      <br/>
      <span class="description">
        <?php esc_html_e(
            'Version number of this release. Must be valid for PHP version_compare() function!',
            'ius' ); ?>
      </span>
    </td>
  </tr>
  <tr>
    <th>
      <label for="ius_release_file">
          <?php esc_html_e( 'File', 'ius' ); ?>
      </label>
    </th>
    <td>
      <input type="file" name="ius_file"/>
      <br/>
      <span class="description">
        <?php esc_html_e( 'Attach a plugin file. For WordPress core update, only .zip files are accepted.', 'ius' ); ?>
      </span>
    </td>
  </tr>
</table>
<?php wp_nonce_field( 'release-properties-nonce-08423', 'release-properties-nonce' ); ?>
