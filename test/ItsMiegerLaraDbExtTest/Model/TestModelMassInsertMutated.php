<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 11.01.19
	 * Time: 15:23
	 */

	namespace ItsMiegerLaraDbExtTest\Model;


	class TestModelMassInsertMutated extends BaseTestModel
	{
		protected $table = 'mass_insert_mutated_tests';

		protected $casts = [
			'data' => 'array'
		];
	}