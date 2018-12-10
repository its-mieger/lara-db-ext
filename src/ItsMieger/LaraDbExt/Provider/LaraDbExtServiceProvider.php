<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 29.11.18
	 * Time: 11:58
	 */

	namespace ItsMieger\LaraDbExt\Provider;


	use Illuminate\Support\ServiceProvider;
	use ItsMieger\LaraDbExt\Connection\MySqlConnection;
	use ItsMieger\LaraDbExt\Connection\PostgresConnection;
	use ItsMieger\LaraDbExt\Connection\SQLiteConnection;
	use ItsMieger\LaraDbExt\Connection\SqlServerConnection;
	use ItsMieger\LaraDbExt\Eloquent\Builder as EloquentBuilder;
	use ItsMieger\LaraDbExt\Query\Builder as QueryBuilder;
	use ItsMieger\LaraDbExt\Query\QueryManager;


	class LaraDbExtServiceProvider extends ServiceProvider
	{
		use RegistersQueryBuilder;
		use RegistersEloquentBuilder;
		use RegistersConnection;

		const PACKAGE_NAME = 'laraDbExt';


		public function register() {
			// register the extended connection classes
			$this->registerConnections();


			$this->app->singleton(QueryManager::class);

			// register default query builder for ResolvesQueryBuilder
			$this->registerQueryBuilder(function ($connection, $grammar, $postProcessor) {
				return new QueryBuilder($connection, $grammar, $postProcessor);
			});

			// register default eloquent builder for ResolvesQueryBuilder
			$this->registerEloquentBuilder(function ($queryBuilder) {
				return new EloquentBuilder($queryBuilder);
			});

		}

		/**
		 * This function registers our own connection classes as default connection classes. These classes only extend the original classes so
		 * that they resolve the query builder instance using our own resolving functionality
		 */
		protected function registerConnections() {

			$this->registerConnection('mysql', MySqlConnection::class);
			$this->registerConnection('pgsql', PostgresConnection::class);
			$this->registerConnection('sqlite', SQLiteConnection::class);
			$this->registerConnection('sqlsrv', SqlServerConnection::class);
		}

//		public function provides() {
//			return [
//				self::PACKAGE_NAME . '.eloquentBuilder',
//				self::PACKAGE_NAME . '.queryBuilder',
//				QueryManager::class,
//			];
//		}
	}