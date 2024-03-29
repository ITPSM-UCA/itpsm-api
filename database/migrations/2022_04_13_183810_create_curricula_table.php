<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('careers', function (Blueprint $table) {
      $table->id();
      $table->string('name', 255);

      $table->timestamps();
    });

    Schema::create('subjects', function (Blueprint $table) {
      $table->id();
      $table->string('name', 255);
      $table->string('code', 6)->unique();

      $table->timestamps();
    });

    Schema::create('scholarships', function (Blueprint $table) {
      $table->id();
      $table->string('name', 255);
      $table->string('scholarship_foundation', 255);

      $table->timestamps();
    });

    Schema::create('curricula', function (Blueprint $table) {
      $table->id();
      $table->string('name', 255);
      $table->integer('year');
      $table->integer('total_uv');
      $table->boolean('is_active');
      $table->boolean('is_approved');

      $table->unsignedBigInteger('career_id')->index();
      $table->foreign('career_id')->references('id')->on('careers');

      $table->timestamps();
      $table->softDeletes();
    });

    Schema::create('student_curricula', function (Blueprint $table) {
      $table->float('cum');
      $table->integer('entry_year');
      $table->integer('uv')->default(0);;
      $table->integer('graduation_year')->nullable();
      $table->float('scholarship_rate')->nullable();

      $table->unsignedBigInteger('student_id')->index();
      $table->foreign('student_id')->references('id')->on('students');

      $table->unsignedBigInteger('curriculum_id')->index();
      $table->foreign('curriculum_id')->references('id')->on('curricula');

      $table->char('status', 1)->default('A'); // A -> Activo - G -> Finalizada - D -> Desertadaß
      $table->integer('level')->default(1);

      $table->primary(['student_id', 'curriculum_id']);

      /**
       * Make nullable
       */
      $table->unsignedBigInteger('scholarship_id')->nullable();
      $table->foreign('scholarship_id')->references('id')->on('scholarships')->nullable();

      $table->timestamps();
      $table->softDeletes();
    });

    Schema::create('curriculum_subjects', function (Blueprint $table) {
      $table->id();
      $table->integer('uv');
      $table->integer('cycle');

      $table->unsignedBigInteger('curriculum_id')->index();
      $table->foreign('curriculum_id')->references('id')->on('curricula');

      $table->unsignedBigInteger('subject_id')->index();
      $table->foreign('subject_id')->references('id')->on('subjects');

      $table->timestamps();
      $table->softDeletes();
    });

    Schema::create('prerequisites', function (Blueprint $table) {
      $table->unsignedBigInteger('curriculum_subject_id')->index();
      $table->foreign('curriculum_subject_id')->references('id')->on('curriculum_subjects');

      $table->unsignedBigInteger('prerequisite_id')->index();
      $table->foreign('prerequisite_id')->references('id')->on('curriculum_subjects');

      $table->primary(['curriculum_subject_id', 'prerequisite_id']);

      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('curricula');
    Schema::dropIfExists('careers');
    Schema::dropIfExists('subjects');
    Schema::dropIfExists('scholarships');
    Schema::dropIfExists('prerequisites');
    Schema::dropIfExists('curriculum_subjects');
    Schema::dropIfExists('student_curricula');
  }
};
