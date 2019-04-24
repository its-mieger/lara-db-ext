<?php


	namespace ItsMiegerLaraDbExtTest\Model;


	class TestModelAdaptsAttributeTimezoneAdapting extends BaseTestModel
	{
		protected $table = 'test_query_table';

		protected $connection = 'adapt-timezone-connection';

		protected $dates = [
			'dt',
		];
	}