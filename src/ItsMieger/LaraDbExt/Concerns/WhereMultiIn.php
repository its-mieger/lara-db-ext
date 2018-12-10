<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 29.11.18
	 * Time: 07:25
	 */

	namespace ItsMieger\LaraDbExt\Concerns;

	use Illuminate\Database\Grammar;
	use Illuminate\Database\Query\Expression as Raw;


	trait WhereMultiIn
	{
		protected function wrapWhereMultiIn($callback, $column, $values, $boolean = 'and', $not = false) {
			$bindings = [];

			// Multiple columns specified? If this is the case we generate a raw expression for the column
			// parameter which references all specified columns
			if (is_array($column)) {
				/** @var Grammar $grammar */
				$grammar = $this->grammar;

				$colCount = count($column);
				$column   = new Raw('(' . implode(', ', $grammar->wrapArray($column)) . ')');

				// Also values array given? If this is the case, we have to handle the values on our own, since parent
				// methods do not support multi dimensional values array
				if (is_array($values)) {

					// we have to transform each row to a raw expression, so it can be handled by the parent method
					$values = array_map(function ($value, $key) use ($colCount, &$bindings, $grammar) {

						// if we got a values array for multiple columns, it must be multidimensional and each row must
						// contain as many fields as columns are specified
						if (!is_array($value))
							throw new \InvalidArgumentException("Values must contain an array of values for each column. No array passed at index $key");
						elseif (($valueCount = count($value)) !== $colCount)
							throw new \InvalidArgumentException("Values must contain an array of values for each column. No got $valueCount value(s) for $colCount column(s) at index $key");


						// extract bindings from current value set
						foreach ($value as $columnValue) {
							if (!$grammar->isExpression($columnValue))
								$bindings[] = $columnValue;
						}

						// convert value set to SQL
						return new Raw('(' . $grammar->parameterize($value) . ')');
					}, $values, array_keys($values));
				}
			}

			// call the parent function
			$ret = call_user_func($callback, $column, $values, $boolean, $not);

			// add value list bindings
			foreach ($bindings as $curr) {
				$this->addBinding($curr);
			}

			return $ret;
		}

		/**
		 * Add a "where in" clause to the query with multiple columns
		 *
		 * @param  string[] $columns
		 * @param  mixed $values
		 * @param  string $boolean
		 * @param  bool $not
		 * @return $this
		 */
		public function whereMultiIn($columns, $values, $boolean = 'and', $not = false) {

			return $this->wrapWhereMultiIn(function () {
				return parent::whereIn(...func_get_args());
			}, ...func_get_args());
		}

		/**
		 * Add a "where in" clause to the query with multiple columns
		 *
		 * @param  string[] $columns
		 * @param  mixed $values
		 * @param  bool $not
		 * @return $this
		 */
		public function orWhereMultiIn($columns, $values, $not = false) {

			return $this->whereMultiIn($columns, $values, 'or', $not);
		}

		/**
		 * Add a "where not in" clause to the query with multiple columns
		 *
		 * @param  string[] $columns
		 * @param  mixed $values
		 * @param  string $boolean
		 * @return $this
		 */
		public function whereMultiNotIn($columns, $values, $boolean = 'and') {

			return $this->whereMultiIn($columns, $values, $boolean, true);
		}

		/**
		 * Add a "where not in" clause to the query with multiple columns
		 *
		 * @param  string[] $columns
		 * @param  mixed $values
		 * @return $this
		 */
		public function orWhereMultiNotIn($columns, $values) {

			return $this->whereMultiNotIn($columns, $values, 'or');
		}


	}