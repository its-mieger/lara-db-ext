<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 29.11.18
	 * Time: 09:35
	 */

	namespace ItsMiegerLaraDbExtTest\Unit;


	use Carbon\Carbon;
	use Illuminate\Database\Connection;
	use ItsMieger\LaraDbExt\Provider\LaraDbExtServiceProvider;

	abstract class TestCase extends \Orchestra\Testbench\TestCase
	{
		use CreatesTestingDatabase;

		protected $connectionsToTransact = [null, 'adapt-timezone-connection'];

		/**
		 * @inheritDoc
		 */
		protected function setUp() {

			parent::setUp();

			// rest test now
			Carbon::setTestNow();

			$this->withFactories(__DIR__ . '/../../database/factories');


			Connection::resolverFor('dummyMocked', function ($connection, $database, $prefix, $config) {
				return new Connection($connection, $database, $prefix, $config);
			});
		}

		/**
		 * @inheritDoc
		 */
		protected function setUpTraits() {
			$this->setupTestingMigrations(__DIR__ . '/../../database/migrations');

			return parent::setUpTraits(); // TODO: Change the autogenerated stub
		}


		/**
		 * Load package service provider
		 * @param  \Illuminate\Foundation\Application $app
		 * @return array
		 */
		protected function getPackageProviders($app) {
			return [
				LaraDbExtServiceProvider::class,
			];
		}

		/**
		 * Define environment setup.
		 *
		 * @param  \Illuminate\Foundation\Application $app
		 * @return void
		 */
		protected function getEnvironmentSetUp($app) {

			$app['config']->set('database.connections.dummyMocked', [
				'driver'   => 'dummyMocked',
				'database' => 'db',
				'prefix'   => '',
			]);

			$app['config']->set('database.connections.dummyMockedPrefixed', [
				'driver'   => 'dummyMocked',
				'database' => 'db',
				'prefix'   => 'myPfx_',
			]);

			// create testing connection with adapt_timezone
			$defaultConnectionName = config('database.default');
			$config = config("database.connections.$defaultConnectionName");
			$config['adapt_timezone'] = true;
			config()->set("database.connections.adapt-timezone-connection", $config);
		}


	}