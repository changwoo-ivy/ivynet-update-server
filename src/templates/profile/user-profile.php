<?php
/**
 * @var WP_User $profileuser
 */
?>
  <h2><?php _e( 'IUS Profile', 'ius' ); ?></h2>
  <table class="form-table">
    <tbody>
    <tr>
      <th>
        <label for="ius_github_user">
            <?php _e( 'Github User', 'ius' ); ?>
        </label>
      </th>
      <td>
        <input type="text"
               class="text"
               id="ius_github_user"
               name="ius_github_user"
               value="<?php echo esc_attr(
                   get_user_meta( $profileuser->ID, 'ius_github_user', TRUE ) );
               ?>"/>
        <br/>
        <span class="description">
        <?php _e( 'Your Github user name.', 'ius' ); ?>
        </span>
      </td>
    </tr>
    <tr>
      <th>
        <label for="ius_github_personal_access_token">
            <?php _e( 'Github Personal Access Token', 'ius' ); ?>
        </label>
      </th>
      <td>
        <input type="password"
               class="text"
               id="ius_github_personal_access_token"
               name="ius_github_personal_access_token"
               value="<?php echo esc_attr(
                   get_user_meta( $profileuser->ID, 'ius_github_personal_access_token', TRUE ) );
               ?>"/>
        <br/>
        <span class="description">
          <?php _e( 'Settings &gt; Developer settings &gt; <a href="https://github.com/settings/tokens" target="_blank">Personal access tokens</a>.',
              'ius' ); ?>
      </span>
      </td>
    </tr>
    </tbody>
  </table>
<?php wp_nonce_field( 'ius_profile_98^25bse$#', 'ius-nonce', FALSE );
