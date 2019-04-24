<?php


	namespace ItsMiegerLaraDbExtTest\Unit\Cases\Model;


	use Carbon\Carbon;
	use Illuminate\Foundation\Testing\DatabaseTransactions;
	use ItsMieger\LaraDbExt\Connection\AdaptsTimezone;
	use ItsMiegerLaraDbExtTest\Model\TestModelAdaptsAttributeTimezoneAdapting;
	use ItsMiegerLaraDbExtTest\Model\TestModelAdaptsAttributeTimezoneNotAdapting;
	use ItsMiegerLaraDbExtTest\Unit\TestCase;

	class AdaptsAttributeTimezoneTest extends TestCase
	{
		use DatabaseTransactions;

		public function testAdapts() {


			if (date_default_timezone_get() != 'UTC')
				$this->markTestSkipped('This test expects the current timezone to be UTC, but it is ' . date_default_timezone_get());

			// we need timezone adapting connection here
			$this->assertContains(AdaptsTimezone::class, class_uses_recursive((new TestModelAdaptsAttributeTimezoneAdapting())->getConnection()));
			$this->assertTrue((new TestModelAdaptsAttributeTimezoneAdapting())->getConnection()->getConfig('adapt_timezone'));


			$now = (new Carbon())->setTimezone('Europe/Berlin');

			$model       = new TestModelAdaptsAttributeTimezoneAdapting();
			$model->dt   = $now;

			$this->assertSame($now->copy()->setTimezone('UTC')->format($model->getDateFormat()), $model->getAttributes()['dt']);

		}

		public function testAdapts_save() {


			if (date_default_timezone_get() != 'UTC')
				$this->markTestSkipped('This test expects the current timezone to be UTC, but it is ' . date_default_timezone_get());

			// we need timezone adapting connection here
			$this->assertContains(AdaptsTimezone::class, class_uses_recursive((new TestModelAdaptsAttributeTimezoneAdapting())->getConnection()));
			$this->assertTrue((new TestModelAdaptsAttributeTimezoneAdapting())->getConnection()->getConfig('adapt_timezone'));


			$now = (new Carbon())->setTimezone('Europe/Berlin');


			$written       = new TestModelAdaptsAttributeTimezoneAdapting();
			$written->name = 'a';
			$written->x    = 'b';
			$written->dt   = $now;
			$written->save();


			$this->assertSame('Europe/Berlin', $now->getTimezone()->getName());

			$read = (new TestModelAdaptsAttributeTimezoneAdapting())->find($written->id);


			$this->assertSame($now->getTimestamp(), $read->dt->getTimestamp());

		}

		public function testNotAdapts() {


			if (date_default_timezone_get() != 'UTC')
				$this->markTestSkipped('This test expects the current timezone to be UTC, but it is ' . date_default_timezone_get());

			// we need a connection not adapting timezone here
			$this->assertFalse((bool)(new TestModelAdaptsAttributeTimezoneNotAdapting())->getConnection()->getConfig('adapt_timezone'));

			$now = (new Carbon())->setTimezone('Europe/Berlin');

			$model     = new TestModelAdaptsAttributeTimezoneNotAdapting();
			$model->dt = $now;

			$this->assertSame($now->format($model->getDateFormat()), $model->getAttributes()['dt']);

		}

		public function testNotAdapts_save() {


			if (date_default_timezone_get() != 'UTC')
				$this->markTestSkipped('This test expects the current timezone to be UTC, but it is ' . date_default_timezone_get());

			// we need a connection not adapting timezone here
			$this->assertFalse((bool)(new TestModelAdaptsAttributeTimezoneNotAdapting())->getConnection()->getConfig('adapt_timezone'));


			$now = (new Carbon())->setTimezone('Europe/Berlin');


			$written       = new TestModelAdaptsAttributeTimezoneNotAdapting();
			$written->name = 'a';
			$written->x    = 'b';
			$written->dt   = $now;
			$written->save();


			$this->assertSame('Europe/Berlin', $now->getTimezone()->getName());

			$read = (new TestModelAdaptsAttributeTimezoneNotAdapting())->find($written->id);


			$this->assertSame($now->getTimestamp() + $now->getTimezone()->getOffset($now), $read->dt->getTimestamp());

		}
	}