<?php

	use Faker\Generator as Faker;

	/** @var \Illuminate\Database\Eloquent\Factory $factory */
	$factory->define(\ItsMiegerLaraDbExtTest\Model\TestBulkImport::class, function (Faker $faker) {
		return [
			'a'                   => $faker->asciify('****************'),
			'b'                   => $faker->asciify('****************'),
			'u'                   => $faker->unique()->asciify('****************'),
			'last_batch_id'       => 0,
			'last_batch_modified' => false,
			'last_batch_created'  => false,
		];
	});