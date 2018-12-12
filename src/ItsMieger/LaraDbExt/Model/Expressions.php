<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.12.18
	 * Time: 09:14
	 */

	namespace ItsMieger\LaraDbExt\Model;



	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Query\Expression;

	/**
	 * Common SQL expression generation
	 * @package ItsMieger\LaravelExt\Model
	 */
	trait Expressions
	{
		/**
		 * Generates a SQL sum() expression
		 * @param Expression|string $expr The expression to pass as argument. If a string is passed, it will be interpreted as column name of the model's table
		 * @param string|null $alias The alias to use. If omitted and a column is passed, the column name is used as alias.
		 * @return Expression The expression
		 */
		public static function sumExpr($expr, $alias = null) {

			return static::functionExpr('sum', $expr, $alias);
		}

		/**
		 * Generates a SQL avg() expression
		 * @param Expression|string $expr The expression to pass as argument. If a string is passed, it will be interpreted as column name of the model's table
		 * @param string|null $alias The alias to use. If omitted and a column is passed, the column name is used as alias.
		 * @return Expression The expression
		 */
		public static function avgExpr($expr, $alias = null) {

			return static::functionExpr('avg', $expr, $alias);
		}

		/**
		 * Generates a SQL min() expression
		 * @param Expression|string $expr The expression to pass as argument. If a string is passed, it will be interpreted as column name of the model's table
		 * @param string|null $alias The alias to use. If omitted and a column is passed, the column name is used as alias.
		 * @return Expression The expression
		 */
		public static function minExpr($expr, $alias = null) {

			return static::functionExpr('min', $expr, $alias);
		}

		/**
		 * Generates a SQL max() expression
		 * @param Expression|string $expr The expression to pass as argument. If a string is passed, it will be interpreted as column name of the model's table
		 * @param string|null $alias The alias to use. If omitted and a column is passed, the column name is used as alias.
		 * @return Expression The expression
		 */
		public static function maxExpr($expr, $alias = null) {

			return static::functionExpr('max', $expr, $alias);
		}

		/**
		 * Generates a SQL lower() expression
		 * @param Expression|string $expr The expression to pass as argument. If a string is passed, it will be interpreted as column name of the model's table
		 * @param string|null $alias The alias to use. If omitted and a column is passed, the column name is used as alias.
		 * @return Expression The expression
		 */
		public static function lowerExpr($expr, $alias = null) {

			return static::functionExpr('lower', $expr, $alias);
		}

		/**
		 * Generates a SQL upper() expression
		 * @param Expression|string $expr The expression to pass as argument. If a string is passed, it will be interpreted as column name of the model's table
		 * @param string|null $alias The alias to use. If omitted and a column is passed, the column name is used as alias.
		 * @return Expression The expression
		 */
		public static function upperExpr($expr, $alias = null) {

			return static::functionExpr('upper', $expr, $alias);
		}


		/**
		 * Generates a SQL count() expression
		 * @param Expression|Expression[]|string|string[] $expressions The expression(s) to pass as argument(s). Strings will be interpreted as column names of the model's table. '*' will be used as wildcard for all columns
		 * @param string|null $alias The alias to use. If omitted and a single column is passed, the column name is used as alias.
		 * @param bool $distinct True if to count distinct values only
		 * @return Expression The expression
		 */
		public static function countExpr($expressions = '*', $alias = null, $distinct = false) {
			$modelClass = get_called_class();
			/** @var Model $model */
			$model = new $modelClass;

			$connection = $model->getConnection();

			if ($expressions == '*') {
				$expressions = [$connection->raw($expressions)];
			}


			return static::functionExpr('count', $expressions, $alias, ($distinct ? $connection->raw('distinct') : null));
		}

		/**
		 * Generates a SQL count(DISTINCT ...) expression
		 * @param Expression|Expression[]|string|string[] $expressions The expression(s) to pass as argument(s). Strings will be interpreted as column names of the model's table
		 * @param string|null $alias The alias to use. If omitted and a single column is passed, the column name is used as alias.
		 * @return Expression The expression
		 */
		public static function countDistinctExpr($expressions, $alias = null) {

			return static::countExpr($expressions, $alias, true);
		}

		/**
		 * Generates a SQL cast(... AS ...) expression
		 * @param Expression|string $expr The expression to be casted. If a string is passed, it will be interpreted as column name of the model's table
		 * @param string $type The target cast type
		 * @param string|null $alias The alias to use. If omitted and a column is passed, the column name is used as alias.
		 * @return Expression  The expression
		 */
		public static function castExpr($expr, $type, $alias = null) {

			$modelClass = get_called_class();
			/** @var Model $model */
			$model = new $modelClass;

			$connection = $model->getConnection();

			if (!preg_match('/^[A-Za-z0-9() ]+$/', $type))
				throw new \InvalidArgumentException("Invalid cast type \"$type\" passed");

			$castExp = $connection->raw('as ' . strtolower($type));


			return static::functionExpr('cast', $expr, $alias, null, $castExp);
		}

		/**
		 * Generates a SQL function expression
		 * @param string $function The raw function name
		 * @param Expression|Expression[]|string|string[] $arguments The expression(s) to pass as argument(s). Strings will be interpreted as column names of the model's table
		 * @param string|null|boolean $alias The alias to use. If omitted and a single column is passed, the column name is used as alias. If false, the alias is always empty
		 * @param Expression|null $preArgSQL SQL expression to add before argument list, e.g. "DISTINCT"
		 * @param Expression|null $postArgSQL SQL expression to add after argument list, e.g. "AS INTEGER"
		 * @return Expression The expression
		 */
		public static function functionExpr(string $function, $arguments, $alias = null, Expression $preArgSQL = null, Expression $postArgSQL = null) {
			$modelClass = get_called_class();
			/** @var Model $model */
			$model = new $modelClass;

			// make alias the same as field name, if single field passed
			if (!$alias && $alias !== false && !is_array($arguments) && !($arguments instanceof Expression))
				$alias = $arguments;

			// make arguments array
			if (!is_array($arguments))
				$arguments = [$arguments];

			// convert field names to expressions
			$arguments = array_map(function ($value) {

				// convert strings to field names
				if (!($value instanceof Expression))
					$value = static::fieldRaw($value);

				return (string)$value;

			}, $arguments);

			// build SQL fragments
			$preArgSQL  = $preArgSQL ? (string)$preArgSQL . ' ' : '';
			$postArgSQL = $postArgSQL ? ' ' . (string)$postArgSQL : '';
			$argsSql    = implode(', ', $arguments);
			$aliasSql   = $alias ? ' as ' . static::quoteIdentifier($alias) : '';

			return $model->getConnection()->raw("{$function}({$preArgSQL}{$argsSql}{$postArgSQL}){$aliasSql}");
		}
	}