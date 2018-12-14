<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 14.12.18
	 * Time: 10:36
	 */

	namespace ItsMieger\LaraDbExt\Concerns;


	use Illuminate\Database\Connection;
	use ItsMieger\LaraDbExt\Connection\Forkable;
	use ItsMieger\LaraDbExt\Query\Builder;

	trait ForkedConnection
	{
		/**
		 * Creates a copy of this instance using a forked connection
		 * @param array $options Allows to override connection options
		 * @param array $attributes Allows to set PDO attributes
		 * @return Builder The new instance
		 */
		public function forkedConnection(array $options = [], array $attributes = []) {
			/** @var Connection $connection */
			$connection = $this->connection;
			if (!($connection instanceof Forkable))
				throw new \RuntimeException('Cannot fork connection because it does not implement forking');

			// clone this instance and set forked connection
			$ret             = clone $this;
			$ret->connection = $connection->fork($options, $attributes);

			/** @noinspection PhpIncompatibleReturnTypeInspection */
			return $ret;
		}

		/**
		 * Executes the given callback with a copy of this instance using a forked connection. After callback execution the forked
		 * connection is destroyed immediately
		 * @param callable $callback The callback
		 * @param array $options Allows to override connection options
		 * @param array $attributes Allows to set PDO attributes
		 * @return mixed The callback return
		 */
		public function withForkedConnection($callback, array $options = [], array $attributes = []) {
			$query = $this->forkedConnection($options, $attributes);

			$ret = call_user_func($callback, $query);

			/** @var Connection|Forkable $connection */
			$connection = $query->connection;
			$connection->destroyFork();

			return $ret;
		}
	}