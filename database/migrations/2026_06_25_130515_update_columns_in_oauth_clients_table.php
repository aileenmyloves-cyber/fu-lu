<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            // Update oauth_clients primary key type from bigint to UUID for Passport v13+ compatibility
            $table->uuid('id')->change();

            // Old columns become nullable
            $table->text('redirect')->nullable()->change();

            $table->boolean('personal_access_client')
                ->nullable()
                ->change();

            $table->boolean('password_client')
                ->nullable()
                ->change();

            // Add new Passport columns
            $table->nullableMorphs('owner');

            $table->text('redirect_uris')->nullable()->after('redirect');
            $table->text('grant_types')->nullable()->after('redirect_uris');



        });

        DB::statement("
            ALTER TABLE oauth_access_tokens
            MODIFY COLUMN client_id CHAR(36) NOT NULL
        ");

        DB::statement("
            ALTER TABLE oauth_auth_codes
            MODIFY COLUMN client_id CHAR(36) NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert client_id back to bigint in related tables before changing the primary key
        DB::statement("
            ALTER TABLE oauth_access_tokens
            MODIFY COLUMN client_id BIGINT UNSIGNED NOT NULL
        ");

        DB::statement("
            ALTER TABLE oauth_auth_codes
            MODIFY COLUMN client_id BIGINT UNSIGNED NOT NULL
        ");

        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->dropColumn([
                'owner_type',
                'owner_id',
                'redirect_uris',
                'grant_types',
            ]);

            $table->text('redirect')->nullable(false)->change();
            $table->boolean('personal_access_client')->nullable(false)->change();
            $table->boolean('password_client')->nullable(false)->change();
        });

        // Revert id from UUID back to bigint auto-increment
        DB::statement("
            ALTER TABLE oauth_clients
            MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
        ");
    }
};
