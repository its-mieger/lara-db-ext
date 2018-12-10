<?php

	use Faker\Generator as Faker;

	/** @var \Illuminate\Database\Eloquent\Factory $factory */
	$factory->define(\ItsMiegerLaraDbExtTest\Model\TestModelEloquentBuilder::class, function (Faker $faker) {
		return [
			'name' => $faker->name,
			'x'    => $faker->randomNumber(6)
		];
	});