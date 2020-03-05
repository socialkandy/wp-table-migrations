<?php

	namespace TableMigrations\Migrations;

	class Migrator{

		/**
		 * Direction of the migrator
		 * 
		 * @var string
		 */
		public $direction;


		/**
		 * Current timestamp
		 * 
		 * @var string
		 */
		public $timestamp;

		/**
		 * Migration list
		 * 
		 * @var array
		 */
		private static $migration_list;

		/**
		 * Steps
		 * 
		 * @var int
		 */
		private $steps = null;

		/**
		 * Constructor
		 */
		public function __construct()
		{
			return $this;
		}


		/**
		 * Handle new migrations
		 * 
		 * @return void
		 */
		public function up()
		{
			$this->direction = 'up';
			$this->run();
		}

		/**
		 * Set direction to down and run
		 * 
		 * @return void
		 */
		public function down()
		{
			$this->direction = 'down';
			$this->run();
		}


		/**
		 * Run migrations
		 * 
		 * @return void
		 */
		public function run()
		{
			$this->timestamp = time();
			while ( count( self::$migration_list ) ) {
				if ( 'up' === $this->direction ) {
					$migration = array_shift( self::$migration_list );
				} else {
					$migration = array_pop( self::$migration_list );
				}
				$migration->run( $this );
			}
		}

		/**
		 * Enqueue migration
		 * 
		 * @return void
		 */
		public static function enqueue_migration( $migration ) {
			self::$migration_list[] = $migration;
		}

		/**
		 * Set steps
		 * 
		 * @return void
		 */
		public function set_steps( $value )
		{
			$this->steps = $value;
		}

		/**
		 * Get steps
		 * 
		 * @return int
		 */
		public function get_steps()
		{
			return $this->steps;
		}

		/**
		 * Dec steps
		 * 
		 * @return void
		 */
		public function dec_steps()
		{
			$this->steps--;
		}

		/**
		 * List
		 * 
		 * @return void;
		 */
		public function list() {
			$list = array();
			$this->direction = 'up';
			while ( count( self::$migration_list ) ) {
				$migration = array_shift( self::$migration_list );
				$list[$migration->getName()] = $migration->ran( $this );	
			}
			return $list;
		}
	}