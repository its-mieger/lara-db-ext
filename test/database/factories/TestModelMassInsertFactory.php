<?php

	use Faker\Generator as Faker;

	/** @var \Illuminate\Database\Eloquent\Factory $factory */
	$factory->define(\ItsMiegerLaraDbExtTest\Model\TestModelMassInsert::class, function (Faker $faker) {
		return [
			'name' => $faker->name,
			'u'    => $faker->unique()->bothify('?')
		];
	});