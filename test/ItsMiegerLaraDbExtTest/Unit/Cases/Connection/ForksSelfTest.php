<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 14.12.18
	 * Time: 09:52
	 */

	namespace ItsMiegerLaraDbExtTest\Unit\Cases\Connection;


	use Illuminate\Database\Connection;
	use ItsMieger\LaraDbExt\Connection\Forkable;
	use ItsMieger\LaraDbExt\Connection\MySqlConnection;
	use ItsMiegerLaraDbExtTest\Unit\TestCase;
	use PHPUnit\Framework\SkippedTestError;

	class ForksSelfTest extends TestCase
	{

		public function testFork() {

			/** @var Connection|Forkable $connection */
			$connection = \DB::connection();

			$this->assertInstanceOf(Forkable::class, $connection);

			/** @var Connection $fork */
			$fork = $connection->fork();

			$this->assertNotSame($connection->getPdo(), $fork->getPdo());
			$this->assertEquals($connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME), $fork->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME));

		}

		public function testOverrideAttribute() {
			/** @var Connection|Forkable $connection */
			$connection = \DB::connection();

			if (!($connection instanceof MySqlConnection))
				throw new SkippedTestError('This test requires a MySQL connection');

			$fork = $connection->fork([], [\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false]);

			$this->assertEquals(true, $connection->getPdo()->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY));
			$this->assertEquals(false, $fork->getPdo()->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY));

			// do reconnect and check again
			$connection->reconnect();
			$fork->reconnect();
			$this->assertEquals(true, $connection->getPdo()->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY));
			$this->assertEquals(false, $fork->getPdo()->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY));
		}

		public function testOverrideOption() {
			/** @var Connection|Forkable $connection */
			$connection = \DB::connection();

			if (!($connection instanceof MySqlConnection))
				throw new SkippedTestError('This test requires a MySQL connection');

			$fork = $connection->fork(['prefix' => 'pfx_']);

			$this->assertEquals(null, $connection->getTablePrefix());
			$this->assertEquals('pfx_', $fork->getTablePrefix());

			// do reconnect and check again
			$connection->reconnect();
			$fork->reconnect();
			$this->assertEquals(null, $connection->getTablePrefix());
			$this->assertEquals('pfx_', $fork->getTablePrefix());

		}


		public function testDestroyForked() {

			/** @var Connection|Forkable $connection */
			$connection = \DB::connection();

			/** @var Connection|Forkable $fork */
			$fork = $connection->fork();

			$fork->destroyFork();

			$this->assertNull($fork->getPdo());

			// reconnect should not be possible
			$this->expectException(\InvalidArgumentException::class);
			$fork->reconnect();
		}

		public function testDestroyForked_notForkedConnection() {

			/** @var Connection|Forkable $connection */
			$connection = \DB::connection();

			try {
				// this should not work but throw an exception
				$connection->destroyFork();

				$this->assertFalse(true);

			} catch(\RuntimeException $ex) {

				// check that the connection is still intact
				$this->assertNotNull($connection->getPdo());
				$connection->reconnect();
			}



		}
	}