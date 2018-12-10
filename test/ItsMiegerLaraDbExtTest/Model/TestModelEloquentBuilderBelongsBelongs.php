<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 03.12.18
	 * Time: 09:46
	 */

	namespace ItsMiegerLaraDbExtTest\Model;


	class TestModelEloquentBuilderBelongsBelongs extends BaseTestModel
	{
		protected $table = 'belongs_belongs_table';

		public function test() {
			return $this->belongsTo(TestModelEloquentBuilderBelongs::class, 'belongs_table_id');
		}
	}