<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 28.11.18
	 * Time: 10:51
	 */

	namespace ItsMieger\LaraDbExt\Provider;



	/**
	 * Adds ability to register a custom eloquent builder for models using ResolvesQueryBuilder
	 * @package ItsMieger\LaravelExt\Concerns
	 */
	trait RegistersEloquentBuilder
	{
		/**
		 * Registers a new resolver for Eloquent's query builder
		 * @param callable $resolver The function which resolves the query builder. It will receive the base query builder as argument
		 */
		protected function registerEloquentBuilder($resolver) {

			// add binding for query builder
			app()->bind(LaraDbExtServiceProvider::PACKAGE_NAME . '.eloquentBuilder', function (/** @noinspection PhpUnusedParameterInspection */
				$app, $parameters) use ($resolver) {

				return call_user_func_array($resolver, [
					$parameters['queryBuilder'],
				]);
			});
		}
	}