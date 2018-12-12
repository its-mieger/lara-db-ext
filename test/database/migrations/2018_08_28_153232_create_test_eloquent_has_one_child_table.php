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
	class CreateTestEloquentHasOneChildTable extends Migration
	{
		/**
		 * Run the migrations.
		 *
		 * @return void
		 */
		public function up() {

			Schema::create('test_eloquent_has_one_child_table', function (Blueprint $table) {
				$table->bigIncrements('id');
				$table->unsignedBigInteger('root_id');
				$table->string('name', 255);
				$table->string('x', 255);
				$table->foreign('root_id')->references('id')->on('test_eloquent_has_one_root_table')->onDelete('cascade');
				$table->timestamps();
			});
		}

		/**
		 * Reverse the migrations.
		 *
		 * @return void
		 */
		public function down() {
			Schema::dropIfExists('test_eloquent_has_one_child_table');
		}
	}