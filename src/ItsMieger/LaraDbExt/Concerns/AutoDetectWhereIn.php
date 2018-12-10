<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 29.11.18
	 * Time: 09:52
	 */

	namespace ItsMieger\LaraDbExt\Concerns;


	use Illuminate\Contracts\Support\Arrayable;

	trait AutoDetectWhereIn
	{
		protected function detectWhereIn($callback, $column, $operator = null, $value = null, $boolean = 'and') {

			$args = func_get_args();
			$numArgs = func_num_args();

			// Here we will make some assumptions about the operator. If only 2 values are
			// passed to the method, we will assume that the operator is an equals sign
			// and keep going. Otherwise, we'll require the operator to be passed in.
			[$value, $operator] = $this->prepareValueAndOperator(
				$value, $operator, $numArgs === 3
			);


			if (in_array($operator, ['=', '<>', '!='])) {

				// make native array
				if ($value instanceof Arrayable)
					$value = $value->toArray();


				// if we got a value array, we will use whereIn
				if (is_array($value))
					return $this->whereIn($column, $value, $boolean, $operator !== '=');
			}



			array_shift($args);
			return call_user_func_array($callback, $args);
		}
	}