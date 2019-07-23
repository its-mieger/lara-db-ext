<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 28.11.18
	 * Time: 14:14
	 */
	class CreateBulkImportTestTable extends Migration
	{
		/**
		 * Run the migrations.
		 *
		 * @return void
		 */
		public function up() {

			Schema::create('bulk_import_tests', function (Blueprint $table) {
				$table->bigIncrements('id');
				$table->string('a', 255)->nullable();
				$table->string('b', 255)->nullable();
				$table->string('u', 255)->nullable();
				$table->unsignedBigInteger('last_batch_id');
				$table->boolean('last_batch_modified')->nullable();
				$table->boolean('last_batch_created')->nullable();
				$table->unique('u');
				$table->timestamps();
			});
		}

		/**
		 * Reverse the migrations.
		 *
		 * @return void
		 */
		public function down() {
			Schema::dropIfExists('bulk_import_tests');
		}
	}