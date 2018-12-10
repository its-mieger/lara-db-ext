<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 28.11.18
	 * Time: 10:42
	 */

	namespace ItsMieger\LaraDbExt\Provider;


	use Illuminate\Database\Connection;

	/**
	 * Adds ability to register a custom query builder for models using ResolvesQueryBuilder
	 * @package ItsMieger\LaravelExt\Concerns
	 */
	trait RegistersQueryBuilder
	{

		/**
		 * Registers a new resolver for Laravel's query builder
		 * @param callable $resolver The function which resolves the query builder. It will receive the connection, the connection's grammar and the connection's post processor as arguments
		 */
		protected function registerQueryBuilder($resolver) {

			// add binding for query builder
			app()->bind(LaraDbExtServiceProvider::PACKAGE_NAME . '.queryBuilder', function (/** @noinspection PhpUnusedParameterInspection */
				$app, $parameters) use ($resolver) {

				/** @var Connection $connection */
				$connection = $parameters['connection'];

				return call_user_func_array($resolver, [
					$connection,
					$parameters['grammar'] ?? $connection->getQueryGrammar(),
					$parameters['processor'] ?? $connection->getPostProcessor(),
				]);
			});
		}
	}