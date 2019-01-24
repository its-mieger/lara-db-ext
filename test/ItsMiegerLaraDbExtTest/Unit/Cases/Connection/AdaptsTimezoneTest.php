<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 24.01.19
	 * Time: 21:43
	 */

	namespace ItsMiegerLaraDbExtTest\Unit\Cases\Connection;



	use Carbon\Carbon;
	use Illuminate\Support\Facades\DB;
	use ItsMiegerLaraDbExtTest\Unit\TestCase;

	class AdaptsTimezoneTest extends TestCase
	{

		public function testTimezoneAdaption() {

			if (date_default_timezone_get() != 'UTC')
				$this->markTestSkipped('This test expects the current timezone to be UTC, but it is ' . date_default_timezone_get());


			// test the default behaviour without time zone adaption
			$passedDate   = Carbon::now('+02:00');
			$ret          = DB::connection()->select('SELECT ? n', [$passedDate]);
			$returnedDate = new Carbon($ret[0]->n);
			$this->assertSame(7199, $returnedDate->diffInSeconds($passedDate));


			// test with timezone adaption
			$passedDate   = Carbon::now('+02:00');
			$ret          = DB::connection()->fork(['adapt_timezone' => true])->select('SELECT ? n', [$passedDate]);
			$returnedDate = new Carbon($ret[0]->n);
			$this->assertSame(0, $returnedDate->diffInSeconds($passedDate));

			// test with timezone adaption but same timezone
			$passedDate   = Carbon::now();
			$ret          = DB::connection()->fork(['adapt_timezone' => true])->select('SELECT ? n', [$passedDate]);
			$returnedDate = new Carbon($ret[0]->n);
			$this->assertSame(0, $returnedDate->diffInSeconds($passedDate));


		}
	}