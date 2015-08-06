<?php
/*
Plugin Name: Bettor Plugin
Plugin URI:  http://www.sportwetten-blogger.de/2015-06-28/allgemein/wetten-plugin-fuer-wordpress/
Description: For bettors to post there bets and show bet statistics.
Version:     0.1
Author:      Benjamin Becker
Author URI:  http://www.sportwetten-blogger,de
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /translation
Text Domain: BettorPlugin
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

global $wpdb;
global $bets_db_version;

$bets_db_version = '1.0';
$installed_ver = get_option( "bets_db_version" );
add_image_size( 'sportsimage', 80, 80, true );
add_filter( 'image_size_names_choose', 'bettor_custom_sizes' );

/** Widget einbinden Start **/
add_action('widgets_init', 'bettor_load_widgets');

function bettor_load_widgets() {
    include_once (dirname(__FILE__) .'/bettorWidget.php');
    register_widget('bettor_auswertung');
}
/** Widget einbinden Ende **%

/** Activate Plugin Start **/
include_once dirname( __FILE__ ) . '/activate_plugin.php';
register_activation_hook( __FILE__, 'activate_function');
/** Activate Plugin Ende **/

/** Plugin Load CSS Start **/
add_filter('wp_enqueue_scripts', 'loadCSSifNeeded');
/** Plugon Load CSS Ende **/

/** Plugin load Languages Start **/
add_action('plugins_loaded', 'bettorplugin_init');
/** Plugin load Languages Ende **/    

/** Plugin Functions Start **/
include_once (dirname(__FILE__) . '/bettorActions.php');
bettorActions();
/** Plugin Functions Ende **/

/** Plugin Editor Changes Start **/
include_once (dirname(__FILE__) . '/bettorAdminEditor.php');
bettorAdminEditor();
/** Plugin Editor Changes Ende **/

/** Admin Page Start **/
include_once (dirname(__FILE__) . '/bettorAdmin.php');
bettorAdmin();
/** Admin Page Ende **/

/** Functions **/
function loadCSSifNeeded(){
    wp_register_style('bettorPluginCSS', plugins_url('/css/bettorPlugin.css',__FILE__ ));
    wp_register_style('bettordataTables', plugins_url('/css/jquery.dataTables.min.css',__FILE__ ));
    wp_register_style('bettordataTablesResponsive', plugins_url('/css/dataTables.responsive.css',__FILE__ ));
    wp_register_style('bettorjQueryAccordion', plugins_url('/css/jquery-ui-accordion.min.css',__FILE__ ));
    
    wp_enqueue_style('bettorPluginCSS');
    wp_enqueue_style('bettordataTables');
    wp_enqueue_style('bettordataTablesResponsive');
    wp_enqueue_style('bettorjQueryAccordion');
    
    wp_register_script( 'datatables',plugins_url('/js/jquery.dataTables.min.js',__FILE__ ),array('jquery'), false, null, true );
    wp_register_script( 'datatables-responsive',plugins_url('/js/dataTables.responsive.min.js',__FILE__ ),array('datatables'), false, null, true );    
    wp_register_script( 'bettorchart',plugins_url('/js/Chart.min.js',__FILE__ ),array('jquery'), false, null, true ); 
    wp_register_script( 'bettor',plugins_url('/js/bettor.js',__FILE__ ),array('bettorchart'), false, null, true );
    wp_enqueue_script('datatables');
    wp_enqueue_script('datatables-responsive');
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-accordion');
    wp_enqueue_script('bettorchart');
    wp_enqueue_script( 'bettor' );
}

if(is_admin()){
    add_action( 'admin_enqueue_scripts', 'load_admin_style' );
    function load_admin_style() {
        wp_register_style('bettorAdminPluginCSS', plugins_url('/css/bettorPlugin.css',__FILE__ ));
        wp_enqueue_style('bettorAdminPluginCSS');
        wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-slider');
        wp_enqueue_script('jquery-ui-datepicker');
        
        wp_register_script( 'time',plugins_url('/js/jquery-ui-timepicker-addon.js',__FILE__ ),array('jquery-ui-datepicker'), false, null, true );
        wp_enqueue_script( 'time' );
        wp_register_script( 'bettorAdmin',plugins_url('/js/bettor_admin.js',__FILE__ ),array('time'), false, null, true );
        wp_enqueue_script( 'bettorAdmin' );
    }
}


/**
 * Load language
 */
function bettorplugin_init() { 
    $plugin_dir = basename(dirname(__FILE__))."/translation";
    load_plugin_textdomain( 'BettorPlugin', false, $plugin_dir );
}

function bettor_custom_sizes( $sizes ) {
    return array_merge( $sizes, array(
        'sportsimage' => 'Sports Image',
    ) );
}

add_action('wp_head', 'bettor_set_ajax_url');
function bettor_set_ajax_url() {
?>
    <script type="text/javascript">
        var ajax_object = {};
        ajax_object.ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';
    </script>
<?php
}