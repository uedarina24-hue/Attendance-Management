<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceCorrectionDetailsTable extends Migration
{

    public function up()
    {
        Schema::create('attendance_correction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('correction_request_id')
                ->constrained('attendance_correction_requests')
                ->onDelete('cascade');
            $table->string('field_name');
            $table->string('before_value')->nullable();
            $table->string('after_value');
            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('attendance_correction_details');
    }
}
