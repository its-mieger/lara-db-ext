<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 29.11.18
	 * Time: 10:58
	 */

	namespace ItsMieger\LaraDbExt\Concerns;


	trait WhereMultiColumns
	{
		protected function wrapWhereColumns($callback, $first, $operator = null, $second = null, $boolean = 'and') {

			$args = func_get_args();

			// If the given operator is not found in the list of valid operators we will
			// assume that the developer is just short-cutting the '=' operators and
			// we will set the operators to '=' and set the values appropriately.
			if (is_array($operator) || $this->invalidOperator($operator))
				[$second, $operator] = [$operator, '='];



			if (is_array($first) && is_array($second) && in_array($operator, ['=', '<>', '!='])) {
				$wheres = [];

				foreach (array_map(null, $first, $second) as $curr) {
					$wheres[] = [$curr[0], '=', $curr[1]];
				}

				return call_user_func($callback, $wheres, null, null, $boolean . ($operator != '=' ? ' not' : ''));

			}

			// call the parent function
			array_shift($args);
			return call_user_func_array($callback, $args);
		}

		/**
		 * Add a "where" clause comparing multiple columns to the query.
		 *
		 * @param  array $first
		 * @param  string|null $operator
		 * @param  array|null $second
		 * @param  string|null $boolean
		 * @return \Illuminate\Database\Query\Builder|static
		 */
		public function whereMultiColumns($first, $operator = null, $second = null, $boolean = 'and') {
			return $this->wrapWhereColumns(function () {
				return parent::whereColumn(...func_get_args());
			}, ...func_get_args());
		}

		/**
		 * Add a "where" clause comparing multiple columns to the query.
		 *
		 * @param  array $first
		 * @param  string|null $operator
		 * @param  array|null $second
		 * @return \Illuminate\Database\Query\Builder|static
		 */
		public function orWhereMultiColumns($first, $operator = null, $second = null) {
			return $this->whereMultiColumns($first, $operator, $second, 'or');
		}
	}