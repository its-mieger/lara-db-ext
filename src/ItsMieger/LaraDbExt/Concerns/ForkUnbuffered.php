<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 14.12.18
	 * Time: 10:54
	 */

	namespace ItsMieger\LaraDbExt\Concerns;


	use Illuminate\Database\Connection;
	use ItsMieger\LaraDbExt\Query\Builder;

	trait ForkUnbuffered
	{
		/**
		 * Creates a copy of this instance using a forked unbuffered connection
		 * @return Builder The new instance
		 */
		public function forkUnbuffered() {
			/** @var Connection $connection */
			$connection = $this->connection;
			if ($connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'mysql')
				throw new \RuntimeException('Forking with unbuffered connection is only supported for "mysql" connections');

			return $this->forkedConnection([], [\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false]);
		}

		/**
		 * Executes the given callback with a copy of this instance using a forked unbuffered connection. After callback execution the forked
		 * connection is destroyed immediately
		 * @param callable $callback The callback
		 * @return mixed The callback return
		 */
		public function withUnbufferedFork($callback) {
			/** @var Connection $connection */
			$connection = $this->connection;
			if ($connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'mysql')
				throw new \RuntimeException('Forking with unbuffered connection is only supported for "mysql" connections');

			return $this->withForkedConnection($callback, [], [\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false]);
		}
	}