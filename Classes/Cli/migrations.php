<?php
	namespace TableMigrations\Cli;

	use WP_CLI;
	use WP_CLI_Command;
	use WP_CLI\Utils;
	use TableMigrations\Migrations\Migrator;


	class MigrateCommands extends WP_CLI_Command{
	
		/**
		 * Run migrations
		 * 
		 * @param  array $args
		 * @param  array $assoc_args
		 * 
		 * @return WP_CLI::success message
		 */
    	function migrate( $args, $assoc_args ){
			if ( $this->has_url_param() ) {
				return;
			}
			global $wp_filter;
    		$migrator = new Migrator( );

			if( isset( $assoc_args['steps'] ) && is_numeric( $assoc_args['steps'] ) ){
				$migrator->set_steps( $assoc_args['steps'] );
			}
    		if( isset( $assoc_args['rollback'] ) ){
    			$migrator->down();
    		}else{
    			$migrator->up();
    		}

    		// Print a success message
    		WP_CLI::success( "All migrations ran" );

		}
		
		/**
		 * List migrations
		 * 
		 * @param  array $args
		 * @param  array $assoc_args
		 * 
		 * @return WP_CLI::success message
		 */
    	function list( $args, $assoc_args ){
			if ( $this->has_url_param() ) {
				return;
			}
			$migrator = new Migrator( );
			$items    = array();
			foreach( $migrator->list() as $key=>$value ) {
				$items[] = array(
					'ran?' => ( $value === false ? 'N' : 'Y'),
					'migration' => $key, 
				);
			}
			WP_CLI\Utils\format_items( 'table', $items, array( 'ran?', 'migration' ) );
		}

		/**
		 * Scaffold
		 * 
		 * @param  array $args
		 * @param  array $assoc_args
		 * 
		 * @return WP_CLI::success message
		 */
    	function scaffold( $args, $assoc_args ){
			global $wp_filesystem;
			if ( $this->has_url_param() ) {
				return;
			}
			WP_Filesystem();
			if ( ! isset( $args[0] ) ) {
				WP_CLI::error( "Missing ClassName" );
			}
			if ( ! class_exists( '\\Factory\\Migrations\\' . $args[0] ) ) {
				$vars = array(
					'class_name' => $args[0],
				);
				$raw_template = 'Migration.mustache';
				$raw_output = self::mustache_render( $raw_template, $vars );
				$filename = ABSPATH . '/migrations/' . time() . '_' . $args[0] . '.php';
				$wp_filesystem->mkdir( dirname( $filename ) );
				if ( ! $wp_filesystem->put_contents( $filename, $raw_output ) ) {
					WP_CLI::error( "Error creating file: {$filename}" );
				} else {
					WP_CLI::success( "Migration created: {$filename}" );
				}
			} else {
				WP_CLI::error( $args[0] . " already exists" );
			}
		}
		
		/**
		 * Localizes the template path.
		 */
		private static function mustache_render( $template, $data = array() ) {
			return Utils\mustache_render( dirname( dirname( __FILE__ ) ) . "/Templates/{$template}", $data );
		}

		/**
		 * Checks if --url param is set.
		 */
		private function has_url_param() {
			if ( isset( $_SERVER['argv'] ) && 0 < count( array_filter( $_SERVER['argv'], function( $check ) { return 0 === strpos( $check, '--url' ); } ) ) ) {
				WP_CLI::error( "Migrations was called with --url argument. Migrations should only run on base site." );
				return true;
			}
			return false;
		}
	}


	WP_CLI::add_command( 'migration', 'TableMigrations\Cli\MigrateCommands' );

