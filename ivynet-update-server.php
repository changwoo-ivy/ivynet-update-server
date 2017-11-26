<?php
/**
 * Plugin Name: Ivynet Update Server
 * Description: Keep projects plugins updated!
 * Version:     1.1.0-alpha.3
 * Author:      Ivynet
 * Author URI:  https://ivynet.kr
 * License:     GPLv2+
 * Text Domain: ius
 */

define( 'IUS_MAIN', __FILE__ );
define( 'IUS_DIR', __DIR__ );
define( 'IUS_VERSION', '1.1.0-alpha.3' );

require_once __DIR__ . '/src/class-ius-launcher.php';

$ius = new IUS_Launcher();
$ius->launch();

$GLOBALS['ius'] = $ius;
