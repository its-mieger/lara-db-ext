<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 28.11.18
	 * Time: 10:16
	 */

	namespace ItsMieger\LaraDbExt\Model;
	use Illuminate\Database\Query\Builder;
	use ItsMieger\LaraDbExt\Provider\LaraDbExtServiceProvider;


	/**
	 * Trait which resolves model's query builder and eloquent builder from service container instead of using hardcoded bindings
	 * @package ItsMieger\LaravelExt\Model
	 */
	trait ResolvesBuilders
	{

		/**
		 * Get a new query builder instance for the connection.
		 *
		 * @return Builder
		 */
		protected function newBaseQueryBuilder() {
			$connection = $this->getConnection();

			// we resolve instance here using service container
			return app(LaraDbExtServiceProvider::PACKAGE_NAME . '.queryBuilder', [
				'connection' => $connection,
			]);
		}

		/**
		 * Create a new Eloquent query builder for the model.
		 *
		 * @param  \Illuminate\Database\Query\Builder $query
		 * @return \Illuminate\Database\Eloquent\Builder|static
		 */
		public function newEloquentBuilder($query) {

			// we resolve instance here using service container
			$ret = app(LaraDbExtServiceProvider::PACKAGE_NAME . '.eloquentBuilder', [
				'queryBuilder' => $query,
			]);


			return $ret;
		}
	}