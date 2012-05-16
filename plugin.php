<?php
/*
Plugin Name: Dell Virtualizaton Connect
Plugin URI: http://marketing.dell.com/virtualization-widget
Description: Adds a widget to display the latest news and information from the most influential virtualization blogs.
Version: 0.2.1
Author: Dell
Author URI: http://www.dell.com


Installing
1. Copy virtualization-connect folder to your plugins folder /wp-content/plugins/
2. Activate it through the plugin management screen.
3. Go to Appearance->Widgets and drag and drop the widget to wherever you want to show it.
*/


define('DELL_CONNECT_DIVISION', 'virtualization');
define('DELL_CONNECT_PLUGIN_NAME', 'Dell Virtualization Connect');
define('DELL_CONNECT_SETTINGS_MENU_TITLE', 'Virtualization Connect Setting');
define('DELL_CONNECT_WIDGET_TITLE', 'The Latest From Virtualization');

define('DELL_CONNECT_PLUGIN_URL',  WP_PLUGIN_URL.'/'.plugin_basename(dirname(__FILE__)));
define('DELL_CONNECT_PLUGIN_PATH', WP_CONTENT_DIR.'/plugins/'.plugin_basename(dirname(__FILE__)));

require(DELL_CONNECT_PLUGIN_PATH . '/connect.php');