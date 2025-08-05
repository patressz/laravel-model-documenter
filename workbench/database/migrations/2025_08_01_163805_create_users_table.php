<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Basic string columns
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->string('slug')->unique()->index();

            // Text columns
            $table->text('bio')->nullable();
            $table->longText('description')->nullable();
            $table->mediumText('summary')->nullable();

            // Numeric columns
            $table->integer('age')->nullable();
            $table->bigInteger('points')->default(0);
            $table->decimal('salary', 10, 2)->nullable();
            $table->float('rating', 3, 2)->default(0.00);
            $table->double('balance')->default(0);
            $table->unsignedInteger('views')->default(0);
            $table->unsignedBigInteger('downloads')->default(0);

            // Date and time columns
            $table->date('birth_date')->nullable();
            $table->time('preferred_time')->nullable();
            $table->datetime('last_login')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->year('graduation_year')->nullable();

            // Boolean columns
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->boolean('receives_notifications')->default(true);

            // JSON and special columns
            $table->json('preferences')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('external_id')->nullable();
            $table->ipAddress('last_ip')->nullable();
            $table->macAddress('device_mac')->nullable();

            // Foreign key columns
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('manager_id')->nullable();

            // Enum column
            $table->enum('status', ['active', 'inactive', 'pending', 'suspended'])->default('pending');
            $table->enum('role', ['admin', 'user', 'moderator'])->default('user');

            // Binary and geometry columns
            $table->binary('avatar')->nullable();
            $table->geometry('location')->nullable();

            // Special Laravel columns
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
