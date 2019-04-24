<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 29.11.18
	 * Time: 15:16
	 */

	namespace ItsMiegerLaraDbExtTest\Model;


	use Illuminate\Database\Eloquent\Model;
	use ItsMieger\LaraDbExt\Model\CreatesRelatedFromAttributes;
	use ItsMieger\LaraDbExt\Model\Expressions;
	use ItsMieger\LaraDbExt\Model\Identifiers;
	use ItsMieger\LaraDbExt\Model\MassInserts;
	use ItsMieger\LaraDbExt\Model\ResolvesBuilders;
	use ItsMieger\LaraDbExt\Model\SerializeDateFormat;

	abstract class BaseTestModel extends Model
	{
		use Identifiers;
		use ResolvesBuilders;
		use CreatesRelatedFromAttributes;
		use Expressions;
		use MassInserts;
		use SerializeDateFormat;
	}