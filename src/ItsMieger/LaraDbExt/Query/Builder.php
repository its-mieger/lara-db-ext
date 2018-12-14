<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 29.11.18
	 * Time: 07:16
	 */

	namespace ItsMieger\LaraDbExt\Query;



	use ItsMieger\LaraDbExt\Concerns\AutoDetectWhereIn;
	use ItsMieger\LaraDbExt\Concerns\ForkedConnection;
	use ItsMieger\LaraDbExt\Concerns\ForkUnbuffered;
	use ItsMieger\LaraDbExt\Concerns\WhereMultiColumns;
	use ItsMieger\LaraDbExt\Concerns\WhereMultiIn;
	use ItsMieger\LaraDbExt\Concerns\SelectPrefixed;
	use ItsMieger\LaraDbExt\Concerns\WhereNotNested;

	class Builder extends \Illuminate\Database\Query\Builder
	{
		use AutoDetectWhereIn;
		use SelectPrefixed;
		use WhereMultiColumns;
		use WhereMultiIn;
		use WhereNotNested;
		use ForkedConnection;
		use ForkUnbuffered;

		/**
		 * @inheritDoc
		 */
		public function where($column, $operator = null, $value = null, $boolean = 'and') {
			return $this->detectWhereIn(function () {
				return parent::where(...func_get_args());
			}, ...func_get_args());
		}


		/**
		 * @inheritDoc
		 */
		public function whereIn($column, $values, $boolean = 'and', $not = false) {

			return $this->wrapWhereMultiIn(function () {
				return parent::whereIn(...func_get_args());
			}, ...func_get_args());
		}

		/**
		 * @inheritDoc
		 */
		public function whereColumn($first, $operator = null, $second = null, $boolean = 'and') {
			return $this->wrapWhereColumns(function () {
				return parent::whereColumn(...func_get_args());
			}, ...func_get_args());
		}


	}