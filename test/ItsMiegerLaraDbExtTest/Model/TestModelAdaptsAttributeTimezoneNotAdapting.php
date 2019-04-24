<?php


	namespace ItsMiegerLaraDbExtTest\Model;


	class TestModelAdaptsAttributeTimezoneNotAdapting extends BaseTestModel
	{
		protected $table = 'test_query_table';

		protected $dates = [
			'dt',
		];
	}