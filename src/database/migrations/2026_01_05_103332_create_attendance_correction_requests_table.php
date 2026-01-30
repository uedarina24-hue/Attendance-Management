<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceCorrectionRequestsTable extends Migration
{

    public function up()
    {
        Schema::create('attendance_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');
            $table->enum('status', [
                'pending',
                'approved',
            ])->default('pending');
            $table->timestamp('applied_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('attendance_correction_requests');
    }
}
