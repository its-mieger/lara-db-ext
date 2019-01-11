<?php
	/** @noinspection SqlNoDataSourceInspection */
	/** @noinspection SqlDialectInspection */

	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.12.18
	 * Time: 09:25
	 */

	namespace ItsMieger\LaraDbExt\Model;


	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Query\Expression;

	/**
	 * Mass inserts for models
	 * Original implementation can be found at: https://github.com/yadakhov/insert-on-duplicate-key by Yada Khov released under MIT license
	 * @package ItsMieger\LaravelExt\Model
	 */
	trait MassInserts
	{

		/**
		 * Insert using ON DUPLICATE KEY UPDATE
		 * @param array $data The data to insert
		 * @param array $updateColumns The columns to update. For numeric keys the value is interpreted as column name and the column is updated with the corresponding field from the dat array. For associative keys, the key is used as column name and the value can be a constant value or an expression which is used to update the column.
		 * @return int The number of affected records
		 */
		public static function insertOnDuplicateKey(array $data, array $updateColumns = []) {
			if (empty($data))
				return false;
			// Case where $data is not an array of arrays.
			if (!isset($data[0]))
				$data = [$data];


			$first = static::getFirstRow($data);

			$bindings = [];
			$sql = 'insert into ' . static::tableRaw() . '(' . static::getColumnList($first) . ') values ';
			$sql .= static::buildDataSQL($data, $bindings) . ' on duplicate key update ';
			if (empty($updateColumns))
				$sql .= static::buildValuesList(array_keys($first), $bindings);
			else
				$sql .= static::buildValuesList($updateColumns, $bindings);


			return static::executeAffectingStatement($sql, $bindings);
		}

		/**
		 * Insert using INSERT IGNORE INTO
		 * @param array $data The data to insert
		 * @return int The number of affected records
		 */
		public static function insertIgnore(array $data) {
			if (empty($data))
				return false;
			// Case where $data is not an array of arrays.
			if (!isset($data[0]))
				$data = [$data];

			$first = static::getFirstRow($data);
			$bindings = [];
			$sql   = 'insert ignore into ' . static::tableRaw() . '(' . static::getColumnList($first) . ') values ' . static::buildDataSQL($data, $bindings);


			return static::executeAffectingStatement($sql, $bindings);
		}

		/**
		 * Insert using REPLACE INTO
		 * @param array $data The data to insert
		 * @return int The number of affected records
		 */
		public static function replace(array $data) {
			if (empty($data))
				return false;
			// Case where $data is not an array of arrays.
			if (!isset($data[0]))
				$data = [$data];

			$first = static::getFirstRow($data);
			$bindings = [];
			$sql   = 'replace into ' . static::tableRaw() . '(' . static::getColumnList($first) . ') values ' . static::buildDataSQL($data, $bindings);

			return static::executeAffectingStatement($sql, $bindings);
		}

		/**
		 * Builds the SQL for the given data
		 * @param array $data The data. Will be not manipulated but using by-reference is faster for large arrays
		 * @param array $bindings Returns the bindings for the query
		 * @return string The generated SQL
		 */
		protected static function buildDataSQL(array &$data, &$bindings = []) {

			$modelClass = get_called_class();
			$lines = [];

			foreach($data as $currRow) {

				// Here we create a new model instance, set the attributes
				// and retrieve the attributes again. This way mutations, casts
				// and so on are applied to the attributes
				/** @var Model $model */
				$model = new $modelClass;

				foreach($currRow as $field => $value) {
					$model->setAttribute($field, $value);
				}
				$attrValues = array_values(array_intersect_key($model->getAttributes(), $currRow));


				$bindings = array_merge($bindings, $attrValues);
				$lines[] = '(' . implode(', ', array_fill(0, count($attrValues), '?')) . ')';
			}

			return implode(', ', $lines);
		}

		/**
		 * Get the first row of the $data array.
		 * @param array $data The data. Will be not manipulated but using by-reference is faster for large arrays
		 * @return array The first row
		 */
		protected static function getFirstRow(array &$data) {
			$firstRow = reset($data);

			if (empty($firstRow))
				throw new \InvalidArgumentException('First data row must not be empty');
			if (!is_array($firstRow))
				throw new \InvalidArgumentException('First data row is not an array');


			return $firstRow;
		}

		/**
		 * Build a column list.
		 * @param array $row The row to obtain columns from. The keys are used as column name
		 * @return string
		 */
		protected static function getColumnList(array $row) {
			return implode(', ', array_map(function($field) {
				return static::quoteIdentifier($field);
			}, array_keys($row)));
		}

		/**
		 * Build a value list
		 * @param array $updatedColumns
		 * @param array $bindings Returns the bindings to be appended
		 * @return string The generated SQL
		 */
		protected static function buildValuesList(array $updatedColumns, &$bindings = []) {
			$out = [];

			$modelClass = get_called_class();

			foreach ($updatedColumns as $key => $value) {
				if (is_numeric($key)) {
					$out[] = static::quoteIdentifier($value) . ' = values(' . static::quoteIdentifier($value) . ')';
				}
				else {
					if ($value instanceof Expression) {
						$out[] = static::quoteIdentifier($key) . " = $value";
					}
					else {
						// Here we create a new model instance, set the attributes
						// and retrieve the attributes again. This way mutations, casts
						// and so on are applied to the attributes
						/** @var Model $model */
						$model = new $modelClass;

						$model->setAttribute($key, $value);

						$out[] = static::quoteIdentifier($key) . ' = ?';
						$bindings[] = $model->getAttributes()[$key];
					}

				}
			}

			return implode(', ', $out);
		}

		/**
		 * Executes the given statement and gets the number of affected rows
		 * @param string $sql The SQL
		 * @param array $bindings The bindings
		 * @return int The number of affected records
		 */
		protected static function executeAffectingStatement($sql, $bindings) {
			$modelClass = get_called_class();
			/** @var Model $model */
			$model = new $modelClass;

			return $model->getConnection()->affectingStatement($sql, $bindings);
		}
	}