<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{

    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');
            $table->date('date');
            $table->dateTime('clock_in_at')->nullable();
            $table->dateTime('clock_out_at')->nullable();
            $table->enum('status', [
                'outside',
                'working',
                'break',
                'finished',
            ])->default('outside');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });
    }


    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
