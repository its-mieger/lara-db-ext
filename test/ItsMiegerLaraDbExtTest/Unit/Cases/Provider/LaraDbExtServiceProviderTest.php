<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 10.12.18
	 * Time: 01:33
	 */

	namespace ItsMiegerLaraDbExtTest\Unit\Cases\Provider;


	use Illuminate\Database\ConnectionInterface;
	use Illuminate\Database\Connectors\ConnectionFactory;
	use Illuminate\Database\Query\Grammars\Grammar;
	use Illuminate\Database\Query\Processors\Processor;
	use Illuminate\Support\Facades\DB;
	use ItsMieger\LaraDbExt\Connection\MySqlConnection;
	use ItsMieger\LaraDbExt\Connection\PostgresConnection;
	use ItsMieger\LaraDbExt\Connection\SQLiteConnection;
	use ItsMieger\LaraDbExt\Connection\SqlServerConnection;
	use ItsMieger\LaraDbExt\Eloquent\Builder as EloquentBuilder;
	use ItsMieger\LaraDbExt\Provider\LaraDbExtServiceProvider;
	use ItsMieger\LaraDbExt\Provider\RegistersQueryBuilder;
	use ItsMieger\LaraDbExt\Query\Builder as QueryBuilder;
	use ItsMieger\LaraDbExt\Query\QueryManager;
	use ItsMiegerLaraDbExtTest\Unit\TestCase;


	class LaraDbExtServiceProviderTest extends TestCase
	{
		use RegistersQueryBuilder;

		public function testConnectionsResolveQueryBuilder() {

			$queryBuilderMock = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();

			$this->registerQueryBuilder(function () use ($queryBuilderMock) {
				return $queryBuilderMock;
			});

			$this->assertSame($queryBuilderMock, DB::connection()->query());

		}

		public function testQueryManagerRegistered() {

			$instance = app(QueryManager::class);

			$this->assertInstanceOf(QueryManager::class, $instance);
			$this->assertSame($instance, app(QueryManager::class));

		}

		public function testQueryBuilderRegistered() {

			$builder = app(LaraDbExtServiceProvider::PACKAGE_NAME . '.queryBuilder', [
				'connection' => $this->getMockBuilder(ConnectionInterface::class)->getMock(),
				'grammar' => $this->getMockBuilder(Grammar::class)->getMock(),
				'processor' => $this->getMockBuilder(Processor::class)->getMock(),
			]);

			$this->assertInstanceOf(QueryBuilder::class, $builder);

		}

		public function testEloquentBuilderRegistered() {

			$builder = app(LaraDbExtServiceProvider::PACKAGE_NAME . '.eloquentBuilder', [
				'queryBuilder' => $this->getMockBuilder(\Illuminate\Database\Query\Builder::class)->disableOriginalConstructor()->getMock(),
			]);

			$this->assertInstanceOf(EloquentBuilder::class, $builder);

		}

		public function testConnectionsRegistered() {
			/** @var ConnectionFactory $factory */
			$factory = app('db.factory');

			$this->assertInstanceOf(MySqlConnection::class, $factory->make([
				'driver' => 'mysql',
				'database' => 'test',
			]));
			$this->assertInstanceOf(PostgresConnection::class, $factory->make([
				'driver' => 'pgsql',
				'database' => 'test',
			]));
			$this->assertInstanceOf(SQLiteConnection::class, $factory->make([
				'driver' => 'sqlite',
				'database' => 'test',
			]));
			$this->assertInstanceOf(SqlServerConnection::class, $factory->make([
				'driver' => 'sqlsrv',
				'database' => 'test',
			]));
		}

	}