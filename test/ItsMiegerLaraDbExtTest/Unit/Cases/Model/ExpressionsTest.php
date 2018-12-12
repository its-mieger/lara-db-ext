<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.12.18
	 * Time: 09:18
	 */

	namespace ItsMiegerLaraDbExtTest\Unit\Cases\Model;

	use ItsMiegerLaraDbExtTest\Model\TestModelDummyConnection;

	use ItsMiegerLaraDbExtTest\Unit\TestCase;

	class ExpressionsTest extends TestCase
	{

		public function testSumExpr() {
			$this->assertEquals("sum(\"test_table\".\"id\") as \"id\"", TestModelDummyConnection::sumExpr("id")->getValue());
			$this->assertEquals("sum(\"test_table\".\"id\")", TestModelDummyConnection::sumExpr("id", false)->getValue());
			$this->assertEquals("sum(\"test_table\".\"id\") as \"myAlias\"", TestModelDummyConnection::sumExpr("id", "myAlias")->getValue());
			$this->assertEquals("sum(1)", TestModelDummyConnection::sumExpr(\DB::raw("1"))->getValue());
			$this->assertEquals("sum(1) as \"myAlias\"", TestModelDummyConnection::sumExpr(\DB::raw("1"), "myAlias")->getValue());
		}

		public function testAvgExpr() {
			$this->assertEquals("avg(\"test_table\".\"id\") as \"id\"", TestModelDummyConnection::avgExpr("id")->getValue());
			$this->assertEquals("avg(\"test_table\".\"id\")", TestModelDummyConnection::avgExpr("id", false)->getValue());
			$this->assertEquals("avg(\"test_table\".\"id\") as \"myAlias\"", TestModelDummyConnection::avgExpr("id", "myAlias")->getValue());
			$this->assertEquals("avg(1)", TestModelDummyConnection::avgExpr(\DB::raw("1"))->getValue());
			$this->assertEquals("avg(1) as \"myAlias\"", TestModelDummyConnection::avgExpr(\DB::raw("1"), "myAlias")->getValue());
		}

		public function testMinExpr() {
			$this->assertEquals("min(\"test_table\".\"id\") as \"id\"", TestModelDummyConnection::minExpr("id")->getValue());
			$this->assertEquals("min(\"test_table\".\"id\")", TestModelDummyConnection::minExpr("id", false)->getValue());
			$this->assertEquals("min(\"test_table\".\"id\") as \"myAlias\"", TestModelDummyConnection::minExpr("id", "myAlias")->getValue());
			$this->assertEquals("min(1)", TestModelDummyConnection::minExpr(\DB::raw("1"))->getValue());
			$this->assertEquals("min(1) as \"myAlias\"", TestModelDummyConnection::minExpr(\DB::raw("1"), "myAlias")->getValue());

		}

		public function testMaxExpr() {
			$this->assertEquals("max(\"test_table\".\"id\") as \"id\"", TestModelDummyConnection::maxExpr("id")->getValue());
			$this->assertEquals("max(\"test_table\".\"id\")", TestModelDummyConnection::maxExpr("id", false)->getValue());
			$this->assertEquals("max(\"test_table\".\"id\") as \"myAlias\"", TestModelDummyConnection::maxExpr("id", "myAlias")->getValue());
			$this->assertEquals("max(1)", TestModelDummyConnection::maxExpr(\DB::raw("1"))->getValue());
			$this->assertEquals("max(1) as \"myAlias\"", TestModelDummyConnection::maxExpr(\DB::raw("1"), "myAlias")->getValue());
		}

		public function testLowerExpr() {
			$this->assertEquals("lower(\"test_table\".\"id\") as \"id\"", TestModelDummyConnection::lowerExpr("id")->getValue());
			$this->assertEquals("lower(\"test_table\".\"id\")", TestModelDummyConnection::lowerExpr("id", false)->getValue());
			$this->assertEquals("lower(\"test_table\".\"id\") as \"myAlias\"", TestModelDummyConnection::lowerExpr("id", "myAlias")->getValue());
			$this->assertEquals("lower(1)", TestModelDummyConnection::lowerExpr(\DB::raw("1"))->getValue());
			$this->assertEquals("lower(1) as \"myAlias\"", TestModelDummyConnection::lowerExpr(\DB::raw("1"), "myAlias")->getValue());
		}

		public function testUpperExpr() {
			$this->assertEquals("upper(\"test_table\".\"id\") as \"id\"", TestModelDummyConnection::upperExpr("id")->getValue());
			$this->assertEquals("upper(\"test_table\".\"id\")", TestModelDummyConnection::upperExpr("id", false)->getValue());
			$this->assertEquals("upper(\"test_table\".\"id\") as \"myAlias\"", TestModelDummyConnection::upperExpr("id", "myAlias")->getValue());
			$this->assertEquals("upper(1)", TestModelDummyConnection::upperExpr(\DB::raw("1"))->getValue());
			$this->assertEquals("upper(1) as \"myAlias\"", TestModelDummyConnection::upperExpr(\DB::raw("1"), "myAlias")->getValue());
		}


		public function testCountExpr() {
			$this->assertEquals("count(\"test_table\".\"id\") as \"id\"", TestModelDummyConnection::countExpr("id")->getValue());
			$this->assertEquals("count(\"test_table\".\"id\")", TestModelDummyConnection::countExpr("id", false)->getValue());
			$this->assertEquals("count(\"test_table\".\"id\", \"test_table\".\"name\")", TestModelDummyConnection::countExpr(["id", "name"])->getValue());
			$this->assertEquals("count(\"test_table\".\"id\", \"test_table\".\"name\") as \"myAlias\"", TestModelDummyConnection::countExpr(["id", "name"], "myAlias")->getValue());
			$this->assertEquals("count(\"test_table\".\"id\") as \"myAlias\"", TestModelDummyConnection::countExpr("id", "myAlias")->getValue());
			$this->assertEquals("count(1)", TestModelDummyConnection::countExpr(\DB::raw("1"))->getValue());
			$this->assertEquals("count(1) as \"myAlias\"", TestModelDummyConnection::countExpr(\DB::raw("1"), "myAlias")->getValue());
			$this->assertEquals("count(1, 2)", TestModelDummyConnection::countExpr([\DB::raw("1"), \DB::raw("2")])->getValue());
			$this->assertEquals("count(1, 2) as \"myAlias\"", TestModelDummyConnection::countExpr([\DB::raw("1"), \DB::raw("2")], "myAlias")->getValue());

			$this->assertEquals("count(distinct \"test_table\".\"id\") as \"id\"", TestModelDummyConnection::countExpr("id", null, true)->getValue());
			$this->assertEquals("count(distinct \"test_table\".\"id\")", TestModelDummyConnection::countExpr("id", false, true)->getValue());
			$this->assertEquals("count(distinct \"test_table\".\"id\", \"test_table\".\"name\")", TestModelDummyConnection::countExpr(["id", "name"], null, true)->getValue());
			$this->assertEquals("count(distinct \"test_table\".\"id\", \"test_table\".\"name\") as \"myAlias\"", TestModelDummyConnection::countExpr(["id", "name"], "myAlias", true)->getValue());
			$this->assertEquals("count(distinct \"test_table\".\"id\") as \"myAlias\"", TestModelDummyConnection::countExpr("id", "myAlias", true)->getValue());
			$this->assertEquals("count(distinct 1)", TestModelDummyConnection::countExpr(\DB::raw("1"), null, true)->getValue());
			$this->assertEquals("count(distinct 1) as \"myAlias\"", TestModelDummyConnection::countExpr(\DB::raw("1"), "myAlias", true)->getValue());
			$this->assertEquals("count(distinct 1, 2)", TestModelDummyConnection::countExpr([\DB::raw("1"), \DB::raw("2")], null, true)->getValue());
			$this->assertEquals("count(distinct 1, 2) as \"myAlias\"", TestModelDummyConnection::countExpr([\DB::raw("1"), \DB::raw("2")], "myAlias", true)->getValue());
		}

		public function testCountDistinctExpr() {
			$this->assertEquals("count(distinct \"test_table\".\"id\") as \"id\"", TestModelDummyConnection::countDistinctExpr("id")->getValue());
			$this->assertEquals("count(distinct \"test_table\".\"id\")", TestModelDummyConnection::countDistinctExpr("id", false)->getValue());
			$this->assertEquals("count(distinct \"test_table\".\"id\", \"test_table\".\"name\")", TestModelDummyConnection::countDistinctExpr(["id", "name"])->getValue());
			$this->assertEquals("count(distinct \"test_table\".\"id\", \"test_table\".\"name\") as \"myAlias\"", TestModelDummyConnection::countDistinctExpr(["id", "name"], "myAlias")->getValue());
			$this->assertEquals("count(distinct \"test_table\".\"id\") as \"myAlias\"", TestModelDummyConnection::countDistinctExpr("id", "myAlias")->getValue());
			$this->assertEquals("count(distinct 1)", TestModelDummyConnection::countDistinctExpr(\DB::raw("1"))->getValue());
			$this->assertEquals("count(distinct 1) as \"myAlias\"", TestModelDummyConnection::countDistinctExpr(\DB::raw("1"), "myAlias")->getValue());
			$this->assertEquals("count(distinct 1, 2)", TestModelDummyConnection::countDistinctExpr([\DB::raw("1"), \DB::raw("2")])->getValue());
			$this->assertEquals("count(distinct 1, 2) as \"myAlias\"", TestModelDummyConnection::countDistinctExpr([\DB::raw("1"), \DB::raw("2")], "myAlias")->getValue());
		}

		public function testCastExpr() {
			$this->assertEquals("cast(\"test_table\".\"id\" as integer) as \"id\"", TestModelDummyConnection::castExpr("id", "Integer")->getValue());
			$this->assertEquals("cast(\"test_table\".\"id\" as date) as \"myAlias\"", TestModelDummyConnection::castExpr("id", "date", "myAlias")->getValue());
			$this->assertEquals("cast(\"test_table\".\"id\" as date)", TestModelDummyConnection::castExpr("id", "date", false)->getValue());
			$this->assertEquals("cast(1 as integer)", TestModelDummyConnection::castExpr(\DB::raw("1"), "integer")->getValue());
			$this->assertEquals("cast(1 as date) as \"myAlias\"", TestModelDummyConnection::castExpr(\DB::raw("1"), "date", "myAlias")->getValue());
			$this->assertEquals("cast(\"test_table\".\"id\" as varchar(255 )) as \"id\"", TestModelDummyConnection::castExpr("id", "varchar(255 )")->getValue());
		}

		public function testCastExprInvalidType() {
			$this->expectException(\InvalidArgumentException::class);

			$this->assertEquals("cast(\"test_table\".\"id\" as integer) as \"id\"", TestModelDummyConnection::castExpr("id", "\"asd\"")->getValue());
		}

		public function testFunctionExpr() {
			$this->assertEquals("fn(\"test_table\".\"id\") as \"id\"", TestModelDummyConnection::functionExpr("fn", "id")->getValue());
			$this->assertEquals("fn(\"test_table\".\"id\")", TestModelDummyConnection::functionExpr("fn", "id", false)->getValue());
			$this->assertEquals("fn(\"test_table\".\"id\", \"test_table\".\"name\")", TestModelDummyConnection::functionExpr("fn", ["id", "name"])->getValue());
			$this->assertEquals("fn(\"test_table\".\"id\", \"test_table\".\"name\") as \"myAlias\"", TestModelDummyConnection::functionExpr("fn", ["id", "name"], "myAlias")->getValue());
			$this->assertEquals("fn(\"test_table\".\"id\") as \"myAlias\"", TestModelDummyConnection::functionExpr("fn", "id", "myAlias")->getValue());
			$this->assertEquals("fn(1)", TestModelDummyConnection::functionExpr("fn", \DB::raw("1"))->getValue());
			$this->assertEquals("fn(1) as \"myAlias\"", TestModelDummyConnection::functionExpr("fn", \DB::raw("1"), "myAlias")->getValue());
			$this->assertEquals("fn(1, 2)", TestModelDummyConnection::functionExpr("fn", [\DB::raw("1"), \DB::raw("2")])->getValue());
			$this->assertEquals("fn(1, 2) as \"myAlias\"", TestModelDummyConnection::functionExpr("fn", [\DB::raw("1"), \DB::raw("2")], "myAlias")->getValue());
			$this->assertEquals("fn(PRE 1, 2) as \"myAlias\"", TestModelDummyConnection::functionExpr("fn", [\DB::raw("1"), \DB::raw("2")], "myAlias", \DB::raw("PRE"))->getValue());
			$this->assertEquals("fn(1, 2 AFT) as \"myAlias\"", TestModelDummyConnection::functionExpr("fn", [\DB::raw("1"), \DB::raw("2")], "myAlias", null, \DB::raw("AFT"))->getValue());
			$this->assertEquals("fn(PRE 1, 2 AFT) as \"myAlias\"", TestModelDummyConnection::functionExpr("fn", [\DB::raw("1"), \DB::raw("2")], "myAlias", \DB::raw("PRE"), \DB::raw("AFT"))->getValue());
		}

	}