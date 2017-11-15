<?php
/**
 */

global $post;

?>
<table class="form-table">
  <tr>
    <th>
      <label for="ius_plugin_main">
          <?php esc_html_e( 'Plugin Main File', 'ius' ); ?>
      </label>
    </th>
    <td>
      <input type="text"
             class="text"
             id="ius_plugin_main"
             name="ius_plugin_main"
             value="<?php echo esc_attr( get_post_meta( $post->ID, 'ius_plugin_main', TRUE ) ); ?>"/>
      <br/>
      <span class="description">
        <?php esc_html_e(
            'Input the plugin\'s main file path. It is a relative path. For example, if your plugin\'s absolute path is \'/home/some/path/public_html/wp-content/plugins/foo/bar.php\', then please input \'foo/bar.php\' here.',
            'ius' ); ?>
      </span>
    </td>
  </tr>
  <tr>
    <th>
      <label for="ius_latest_version">
          <?php esc_html_e( 'Latest Version', 'ius' ); ?>
      </label>
    </th>
    <td>
        <?php $releases = ius_get_project_releases( $post->ID ); ?>
        <?php $selected = get_post_meta( $post->ID, 'ius_latest_version', TRUE ); ?>
        <?php if ( $releases ) : ?>
          <select id="ius_latest_version" name="ius_latest_version">
            <option value="">
                <?php esc_html_e( 'Choose a version', 'ius' ); ?>
            </option>
              <?php foreach ( $releases as $release ) : ?>
                <option value="<?php echo esc_attr( $release['version'] ); ?>"
                    <?php selected( $selected, $release['version'] ); ?>>
                    <?php echo esc_html( $release['version'] ); ?>
                </option>
              <?php endforeach; ?>
          </select>
        <?php else: ?>
            <?php esc_html_e( 'No releases yet.', 'ius' ); ?>
        <?php endif; ?>
      <br/>
      <span class="description">
        <?php esc_html_e( 'You can override the project latest version here.', 'ius' ); ?>
      </span>
    </td>
  </tr>
</table>
<?php wp_nonce_field( 'project-properties-nonce-9234', 'project-properties-nonce' ); ?>
