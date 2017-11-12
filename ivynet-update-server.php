<?php
/**
 * Plugin Name: Ivynet Update Server
 * Description: Keep projects plugins updated!
 * Version:     0.0.0
 * Author:      Ivynet
 * Author URI:  https://ivynet.kr
 * License:     GPLv2+
 */

define( 'IUS_MAIN', __FILE__ );
define( 'IUS_DIR', __DIR__ );
define( 'IUS_VERSION', '0.0.0' );

require_once __DIR__ . '/src/class-ius-launcher.php';

$ius = new IUS_Launcher();
$ius->launch();

$GLOBALS['ius'] = $ius;
