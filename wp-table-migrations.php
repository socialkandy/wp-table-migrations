<?php
/**
 * Plugin Name: WP Table Migrations
 * Plugin URI: https://github.com/cuisine-wp/wp-table-migrations
 * Description: Create database migrations for WordPress
 * Version: 0.1.0
 * Author: Luc Princen
 * Author URI: http://www.chefduweb.nl/
 * License: GPLv3
 *
 * @package TableMigrations
 * @category Core
 * @author Chef du Web
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// The directory separator.
defined('DS') ? DS : define('DS', DIRECTORY_SEPARATOR);


/**
 * Main class that bootstraps the framework.
 */
if (!class_exists('TableMigrations')) {

    class TableMigrations {

        /**
         * Plugin bootstrap instance.
         *
         * @var \TableMigrations
         */
        private static $instance = null;


        /**
         * Plugin directory name.
         *
         * @var string
         */
        private static $dirName = '';


        /**
         * Constructor
         */
        private function __construct(){

            static::$dirName = static::setDirName(__DIR__);

            // Load plugin.
            $this->load();
        }


        /**
         * Load the plugin classes.
         *
         * @return void
         */
        private function load(){
            if ( ! defined( 'WP_CLI' ) || defined( 'WP_CLI' ) && ! WP_CLI ) {
                return;
            } else if ( defined( 'SITE_ID_CURRENT_SITE') && SITE_ID_CURRENT_SITE !== get_current_blog_id() ) {
                return;
            }

            //load text-domain:
            $path = dirname( plugin_basename( __FILE__ ) ).'/Languages/';
            load_plugin_textdomain( 'tablemigrations', false, $path );


            //auto-loads all .php files in these directories.
            $includes = array(
                'Classes/Wrappers',
                'Classes/Utilities',
                'Classes/Contracts',
                'Classes/Database/Grammars',
                'Classes/Database',
                'Classes/Migrations',
            );

            if ( defined('WP_CLI') && WP_CLI )
                $includes[] = 'Classes/Cli';


            $includes = apply_filters( 'table_migrations_autoload_dirs', $includes );

            foreach( $includes as $inc ){

                $root = static::getPluginPath();
                $files = glob( $root.$inc.'/*.php' );

                foreach ( $files as $file ){

                    require_once( $file );

                }
            }

            //TableMigrations is fully loaded
            do_action( 'table_migrations_loaded' );
        }



        /*=============================================================*/
        /**             Getters & Setters                              */
        /*=============================================================*/


        /**
         * Init the plugin classe
         *
         * @return \TableMigrations
         */
        public static function getInstance(){

            if ( is_null( static::$instance ) ){
                static::$instance = new static();
            }
            return static::$instance;
        }

        /**
         * Set the plugin directory property. This property
         * is used as 'key' in order to retrieve the plugins
         * informations.
         *
         * @param string
         * @return string
         */
        private static function setDirName($path) {

            $parent = static::getParentDirectoryName(dirname($path));

            $dirName = explode($parent, $path);
            $dirName = substr($dirName[1], 1);

            return $dirName;
        }

        /**
         * Check if the plugin is inside the 'mu-plugins'
         * or 'plugin' directory.
         *
         * @param string $path
         * @return string
         */
        private static function getParentDirectoryName($path) {

            // Check if in the 'mu-plugins' directory.
            if (WPMU_PLUGIN_DIR === $path) {
                return 'mu-plugins';

            }

            // Install as a classic plugin.
            return 'plugins';
        }


        public static function getPluginPath(){
            return __DIR__.DS;
        }

        /**
         * Returns the directory name.
         *
         * @return string
         */
        public static function getDirName(){
            return static::$dirName;
        }
    }
}

/**
 * Load the main class.
 *
 */
add_action('plugins_loaded', function(){

    TableMigrations::getInstance();

});

/**
 * Load Factory Migrations.
 *
 */
function load_factory_migrations() {
	if ( ! defined( 'WP_CLI' ) || defined( 'WP_CLI' ) && ! WP_CLI ) {
		return;
	}

	$elements = glob( ABSPATH . '/migrations/*.php' , 0 );

	foreach ( $elements as $migration_path ) {
		require $migration_path;
	}
}

add_action( 'table_migrations_loaded', 'load_factory_migrations' );

/**
 * Wrapper for the switch_to_blog function that applies a blog switch based
 * on a unique meta field saved for each site of the factory.
 *
 * @param string $blog_name Tag value used to search for the site.
 * @param function $callback Callback function to be executed after switch to blog.
 *
 * @return bool
 */
function collabra_switch_to_blog( $blog_name, $callback ) {
    $sites = get_sites( array(
        'meta_key'     => 'site_tag',
        'meta_value'   => $blog_name,
        'meta_compare' => '=',
    ) );
    if ( 0 < count( $sites ) ) {
        foreach ( $sites as $site ) {
            switch_to_blog( $site->blog_id );
            call_user_func( $callback );
        }
    }
	return false;
}
