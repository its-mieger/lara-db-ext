<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 10.12.18
	 * Time: 01:07
	 */

	namespace ItsMieger\LaraDbExt\Connection;


	use ItsMieger\LaraDbExt\Provider\LaraDbExtServiceProvider;
	use ItsMieger\LaraDbExt\Query\Builder;

	trait ResolvesQuery
	{
		/**
		 * Get a new query builder instance for the connection.
		 *
		 * @return Builder
		 */
		public function query() {
			return app(LaraDbExtServiceProvider::PACKAGE_NAME . '.queryBuilder', [
				'connection'    => $this,
				'grammar'       => $this->getQueryGrammar(),
				'postProcessor' => $this->getPostProcessor(),
			]);
		}
	}