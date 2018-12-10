<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 10.12.18
	 * Time: 01:20
	 */

	namespace ItsMieger\LaraDbExt\Provider;


	use Illuminate\Database\Connection;

	trait RegistersConnection
	{
		/**
		 * Registers a new connection class for the given driver
		 * @param string $driver The driver name
		 * @param string|\Closure $connection The connection class name or a closure resolving a new connection class
		 */
		protected function registerConnection($driver, $connection) {

			// If a string is passed, we assume a class name. We build a closure which creates
			// a new instance of the given class
			if (!($connection instanceof \Closure)) {
				$connectionClass = $connection;
				$connection = function($connection, $database, $prefix, $config) use ($connectionClass) {
					return new $connectionClass($connection, $database, $prefix, $config);
				};
			}

			Connection::resolverFor($driver, $connection);
		}
	}