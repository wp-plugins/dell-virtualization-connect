<?php

// Define Actions
// ============================================

register_activation_hook(__FILE__,'dell_connect_install');
register_deactivation_hook(__FILE__, 'dell_connect_uninstall');

add_action( 'wp_print_scripts', 'dell_connect_scripts' );
add_action( 'wp_print_styles', 'dell_connect_styles' );
add_action( 'widgets_init', 'dell_connect_load_widgets' );
add_action( 'admin_menu', 'dell_connect_admin_menu' );
add_action( 'wp_ajax_edu_connect_showlink', 'dell_connect_admin_ajax_showlink' );
//add_action( 'admin_notices', 'edu_connect_admin_notice' );

// Define Constants
// ============================================

//Define Environment if not defined
if(!defined('DELL_CONNECT_ENV')) {
    define('DELL_CONNECT_ENV', 'production');
}

//Define constants based on environment
if(DELL_CONNECT_ENV == 'development') {
    define('DELL_CONNECT_SERVICE_URL','http://11.0.0.1:9001');
}
elseif (DELL_CONNECT_ENV == 'staging') {
    define('DELL_CONNECT_SERVICE_URL','http://staging.dell.system-11.com');
}
else {
    define('DELL_CONNECT_SERVICE_URL','http://dell.system-11.com');
}

//Check to see if a client ID already exists, if not, install the widget
$clientId = get_option('dell_connect_clientid', null);
if($clientId === null) {
    dell_connect_install();
}
/**
 * Register the widget
 */
function dell_connect_load_widgets() {
    register_widget( 'Edu_Connect_Widget' );
}


/**
 * Plugin Install/Activation Script
 */
function dell_connect_install() {
    //Check to make sure PHP config supports urls in file_get_contents
    if(!function_exists('file_get_contents') || ini_get('allow_url_fopen') == 0){
        die('Sorry, this plugin requires file_get_contents to grab a URL.');
    }

    //Get option to see if it has already been installed
    $clientId = get_option('dell_connect_clientid', null);
        
    //Set global default option
    $settings = file_get_contents(DELL_CONNECT_SERVICE_URL . '/client/add?division=' . DELL_CONNECT_DIVISION .'&url=' . urlencode(home_url()) . '&ajaxurl=' . urlencode(DELL_CONNECT_PLUGIN_URL . '/ajax.php') . '&clientid=' . $clientId);
    $settings = json_decode($settings);

    if(!$settings->result) {
        file_get_contents(DELL_CONNECT_SERVICE_URL . '/client/error?url=' . urlencode(home_url()) . '&error=' . urlencode($settings->error));
        die("Sorry, there was a problem activating your blog. Please contact support+dell@system-11.com");
    }
    else {
        $clientId = $settings->data->id;
    }
    update_option('dell_connect_clientid', $clientId);
    update_option('edu_connect_showlink', false);
}

function dell_connect_uninstall() {
    $clientId = get_option('dell_connect_clientid', null);
    $settings = file_get_contents(DELL_CONNECT_SERVICE_URL . '/client/deactivate?clientid=' . $clientId);
    $settings = json_decode($settings);
}



/**
 * Widget Class
 */
class Edu_Connect_Widget extends WP_Widget {

    const EDU_CONNECT_TITLE = DELL_CONNECT_WIDGET_TITLE;
    const EDU_CONNECT_DATEFORMAT = 'M/d/Y';
    const EDU_CONNECT_MAX_SHOWN_ITEMS = 6;

    protected $url;

    function Edu_Connect_Widget() {
        //Default data as constants
        
        $this->url = DELL_CONNECT_SERVICE_URL . "/feed";
        //Set defaults
        $widget_ops = array();
        $widget_ops['title' ] = self::EDU_CONNECT_TITLE;
        $widget_ops['url' ] = $this->url;
        $widget_ops['showsponsoredlink'] = false;
        $widget_ops['dateformat'] = self::EDU_CONNECT_DATEFORMAT;
        $widget_ops['count_items'] = self::EDU_CONNECT_MAX_SHOWN_ITEMS;

        $this->WP_Widget('dell-edu-connect', __(DELL_CONNECT_PLUGIN_NAME), $widget_ops);
    }
    /**
     * For function displays the form
     * @param  Object $instance Widget object instance
     */
    function form($instance) {

        $widget_ops = array();
        $widget_ops['title' ] = DELL_CONNECT_WIDGET_TITLE;
        $widget_ops['url' ] = $this->url;
        $widget_ops['showsponsoredlink'] = false;
        $widget_ops['dateformat'] = self::EDU_CONNECT_DATEFORMAT;
        $widget_ops['count_items'] = self::EDU_CONNECT_MAX_SHOWN_ITEMS;
        $widget_ops['show_descriptions'] = true;

        $instance = wp_parse_args( (array) $instance, $widget_ops);

        $title = wp_specialchars($instance['title']);
        //$url = wp_specialchars($instance['widget_url']);
        // $showsponsoredlink = $instance['showsponsoredlink'];
        $dateformat = $instance['dateformat'];
        $count_items = $instance['count_items'];
        $show_descriptions = $instance['show_descriptions'];

      ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:'); ?></label>
            <input style="width: 350px;" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'count_items' ); ?>"><?php _e('Count Items To Show:'); ?></label>
            <input  id="<?php echo $this->get_field_id( 'count_items' ); ?>" name="<?php echo $this->get_field_name( 'count_items' ); ?>" size="4" maxlength="4" type="text" value="<?php echo $count_items?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'show_descriptions' ); ?>"><?php _e('Show article summaries:'); ?> </label>
            <input  id="<?php echo $this->get_field_id( 'show_descriptions' ); ?>" name="<?php echo $this->get_field_name( 'show_descriptions' ); ?>" type="checkbox" <?php if($show_descriptions) echo 'checked'; ?> />

        </p>
        <!-- <p><label for="<?php echo $this->get_field_id( 'showsponsoredlink' ); ?>"><?php _e('Show sponsored link:'); ?> </label>
           <input  id="<?php echo $this->get_field_id( 'showsponsoredlink' ); ?>" name="<?php echo $this->get_field_name( 'showsponsoredlink' ); ?>" type="checkbox" <?php if($showsponsoredlink) echo 'checked'; ?> />
          <br />
          <em>Please, if you like this widget left checked</em> -->
        </p>
        <p align='left'></p>
        <?php
        // outputs the options form on admin
    }

    function update($new_instance, $old_instance) {
        // processes widget options to be saved
        $newoptions = $old_instance;
        $newoptions['title'] = strip_tags(stripslashes($new_instance["title"]));
        $newoptions['count_items'] = (int)$new_instance["count_items"];
        $newoptions['show_descriptions'] = $new_instance['show_descriptions'];
        // $newoptions['showsponsoredlink'] = $new_instance["showsponsoredlink"];

        return $newoptions;
    }

    function widget($args, $instance) {
        // outputs the content of the widget

        extract($args);

        $title = apply_filters('widget_title', $instance['title']);
        $count_items = $instance['count_items'];
        $show_descriptions = $instance['show_descriptions'];
        $clientId = get_option('dell_connect_clientid', null);
        ?>
        <?php echo $before_widget; ?>
        <?php if($title) echo $before_title . $title . $after_title; ?>
        <div id="dell_edu_content">

        </div>
        <script>
            var _dec = _dec || [];
            <?php if(DELL_CONNECT_ENV == 'development' || DELL_CONNECT_ENV == 'staging'):?>
            _dec.push(['enableDebug']);
            <?php endif; ?>
            _dec.push(['setBaseUrl', '<?php echo DELL_CONNECT_SERVICE_URL ?>']);
            _dec.push(['setClientId', '<?php echo get_option("dell_connect_clientid")?>']);
            _dec.push(['setDivision', '<?php echo DELL_CONNECT_DIVISION ?>']);
            _dec.push(['getFeed',<?php echo (int)$count_items; ?>,<?php echo (($show_descriptions)?'true':'false'); ?>]);
        </script>

        <?php echo $after_widget; ?>
        <?php
    }
}


function dell_connect_scripts() {
    if (!is_admin()) {
        if(DELL_CONNECT_ENV == 'production') {
            wp_enqueue_script( $handle = 'edu_connect_ender', $src = 'https://s3.amazonaws.com/system11-dell/ender.min.js', array(), $ver = 1, $in_footer = false );
            wp_enqueue_script( $handle = 'edu_connect_js', $src = 'https://s3.amazonaws.com/system11-dell/dell.min.js', array(), $ver = 1, $in_footer = false );
        }
         else {
            wp_enqueue_script( $handle = 'edu_connect_ender', $src = DELL_CONNECT_PLUGIN_URL . '/assets/js/ender.js', array(), $ver = 1, $in_footer = false );
            wp_enqueue_script( $handle = 'edu_connect_js', $src = DELL_CONNECT_PLUGIN_URL . '/assets/js/dell.js', array(), $ver = 1, $in_footer = false );
        }
    }
}

function dell_connect_styles() {
    if (!is_admin()) {
        wp_enqueue_style( $handle = 'edu_connect_css', $src = DELL_CONNECT_PLUGIN_URL . '/assets/css/styles.css', array(), $ver = 1 );
    }
}

function edu_connect_admin_notice() {
    global $current_screen;
    if ( !get_option('edu_connect_configured') && $current_screen->id != 'dell-services_page_edu-connect' ) {
        $url = admin_url( 'admin.php?page=edu-connect');
        echo '<div class="plugin-update-tr"><p class="update-message"><strong>You haven\'t configured your Connect Widget. <a href="' . $url . '">Click Here</a> to Configure it!</strong></p></div>';
    }
}

function dell_connect_admin_menu() {
    //add_menu_page( 'Dell Services', 'Dell Services', 'manage_options', 'dell-services', null, WP_PLUGIN_URL . '/edu-connect/assets/img/dellecomicon.png', null );
    //$dell_news_page = add_submenu_page( 'dell-services', 'News', 'News', 'manage_options', 'dell-news', 'dell_admin_page' );
    $edu_connect_page = add_options_page(DELL_CONNECT_PLUGIN_NAME, DELL_CONNECT_PLUGIN_NAME, 'manage_options', 'edu-connect', 'edu_connect_admin_page' );
    remove_submenu_page( 'dell-services', 'dell-services' );

    add_action('admin_print_styles-' . $edu_connect_page, 'edu_connect_admin_scripts');
}

function edu_connect_admin_scripts() {
    wp_enqueue_style( $handle = 'edu_connect_admin_css', $src = DELL_CONNECT_PLUGIN_URL . '/assets/css/admin-styles.css', array(), $ver = 1 );
    wp_enqueue_script( 'jquery-ui-core' );
    wp_enqueue_script( 'jquery-ui-tabs' );
    wp_enqueue_script( $handle = 'edu_connect_plugins', $src = DELL_CONNECT_PLUGIN_URL . '/assets/js/plugins.js', array(), $ver = 1, $in_footer = false );
    
    if(DELL_CONNECT_ENV == 'production') {
        wp_enqueue_script( $handle = 'edu_connect_ender', $src = 'https://s3.amazonaws.com/system11-dell/ender.min.js', array(), $ver = 1, $in_footer = false );
            wp_enqueue_script( $handle = 'edu_connect_js', $src = 'https://s3.amazonaws.com/system11-dell/dell.min.js', array(), $ver = 1, $in_footer = false );
    }
    else {
        wp_enqueue_script( $handle = 'edu_connect_ender', $src = DELL_CONNECT_PLUGIN_URL . '/assets/js/ender.js', array(), $ver = 1, $in_footer = false );
        wp_enqueue_script( $handle = 'edu_connect_js', $src = DELL_CONNECT_PLUGIN_URL . '/assets/js/dell.js', array(), $ver = 1, $in_footer = false );
    }
   
}

function edu_connect_admin_page() {
    $blogRoll = get_bookmarks();
    $urlArray = array();
    foreach($blogRoll as $blog) {
        $urlArray[] = $blog->link_url;
    }
?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2><?php echo DELL_CONNECT_PLUGIN_NAME ?></h2>
<div id="tabs">
    <h2 class="nav-tab-wrapper">
    <ul>
        <li><a class="nav-tab" href="#tab1">Blog List</a></li>
        <li><a class="nav-tab" href="#tab2">More Options</a></li>
    </ul>
    <div class="clear"></div>
    </h2>

    <div id="tab1">
        <h3>Blog Selection</h3>
        <p>This section allows you to choose which blogs to include in your Edu Connect Widget. Un-check the checkbox next to any blogs you don't want to include in your widget.</p>
        <div id="dec_blogs">
        Loading Blog List...
        </div>
        <script>
            var _dec = _dec || [];
            <?php if(DELL_CONNECT_ENV == 'development' || DELL_CONNECT_ENV == 'staging'):?>
            _dec.push(['enableDebug']);
            <?php endif; ?>
            _dec.push(['setBaseUrl', '<?php echo DELL_CONNECT_SERVICE_URL ?>']);
            _dec.push(['setClientId', '<?php echo get_option("dell_connect_clientid")?>']);
            _dec.push(['setDivision', '<?php echo DELL_CONNECT_DIVISION ?>']);
            _dec.push(['getBlogList']);
        </script>
    </div>
    <div id="tab2">
        <div class="dbn_error" style="display:none;"></div>
        <?php $showLink = get_option('edu_connect_showlink'); ?>
        <h3>User Blogs</h3>
        <div>
            <label for="edu_connect_text_addblog">Add a custom url to your own feed here</label></br>
            <input type="text" placeholder="Enter URL Here" name="edu_connect_text_addblog" id="edu_connect_text_addblog" /><button class="button" id="edu_connect_btn_addblog" onclick="_dec.push(['addBlog'])">Submit</button>
        </div>
        <h3>Blog Roll Sync</h3>
        <button id="edu_connect_btn_blogroll" class="button" onclick="_dec.push(['addBlogRoll']);">Sync Blogroll</button> This adds all the sites from your blogroll to the edu connect widget. You can remove these blogs in the custom section of the blog selection tab.
        <script>
            _dec.push(['setBlogRoll', <?php echo JSON_encode($urlArray); ?>]);
        </script>
        

        <!-- <div><p style="float:left;margin-right:10px;">
        <input onclick="_dec.push(['setBacklinkActive']);" type="checkbox" id="dec_backlink_active" name="backlinkstatus" value="active" <?php if($showLink) echo "checked";?> /></p>
        <p><strong>Show Sponsor Link</strong><br />Please help support the development of this plugin by keeping this checked.</p> -->
        </div>
    </div>
</div>
<script type="text/javascript" charset="utf8" >
    jQuery(document).ready(function($) {
        $("#tabs").tabs({ selected: 0 });
    });
</script>
<?php
}

function dell_connect_admin_ajax_showlink() {
    global $wpdb;
    $showSponsoredLink = isset($_POST['backlinkActive'])?true:false;

    if($showSponsoredLink) {
        update_option('edu_connect_showlink', true);
    }
    echo '{"result":true}';
    die('');
}

function dell_admin_page() {
?>

<div class="wrap">
    <h2>Dell WordPress News</h2>


</div>

<?php
}