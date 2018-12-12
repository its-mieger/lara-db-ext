<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.12.18
	 * Time: 07:15
	 */

	namespace ItsMieger\LaraDbExt\Model;

	use Illuminate\Database\Eloquent\Model;

	/**
	 * Easy access to model identifiers
	 * @package ItsMieger\LaravelExt\Model
	 */
	trait Identifiers
	{

		/**
		 * Gets the model's table name
		 * @param bool $prefixed True if to get table name with prefix
		 * @return string The table name
		 */
		public static function table($prefixed = true) {
			$modelClass = get_called_class();
			/** @var Model $model */
			$model = new $modelClass;

			return ($prefixed ? $model->getConnection()->getTablePrefix() : '') . $model->getTable();
		}

		/**
		 * Gets the model's table name (with prefix) for use in raw SQL expressions
		 * @return string The table name
		 */
		public static function tableRaw() {
			return static::quoteIdentifier(static::table(true));
		}

		/**
		 * Gets the model's qualified field name (with the table name)
		 * @param string $field The model field name
		 * @param bool $tablePrefixed True if to prefix table name
		 * @return string The model field, eg. "table.field"
		 */
		public static function field(string $field, $tablePrefixed = true) {
			return static::table($tablePrefixed) . ".{$field}";
		}

		/**
		 * Gets the model's field name prefixed with the table name for use in raw SQL expressions
		 * @param string $field The model field name
		 * @return string The model field, eg. "`table`.`field`"
		 */
		public static function fieldRaw(string $field) {
			return static::quoteIdentifier(static::table(true) . ".{$field}");
		}

		/**
		 * Gets the given identifier for use in raw SQL expressions of the model's database connection
		 * @param string $identifier The identifier name. May contain multiple segments separated by '.'
		 * @return string The wrapped identifier eg. "`table`.`field`" or "`table`"
		 */
		public static function quoteIdentifier(string $identifier) {
			$modelClass = get_called_class();
			/** @var Model $model */
			$model = new $modelClass;

			return $model->getConnection()->getQueryGrammar()->wrap($identifier);
		}


	}