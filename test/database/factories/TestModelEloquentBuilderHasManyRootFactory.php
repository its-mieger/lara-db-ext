<?php

	use Faker\Generator as Faker;

	/** @var \Illuminate\Database\Eloquent\Factory $factory */
	$factory->define(\ItsMiegerLaraDbExtTest\Model\TestModelEloquentBuilderHasManyRoot::class, function (Faker $faker) {
		return [
			'name' => $faker->name,
			'x'    => $faker->randomNumber(6)
		];
	});