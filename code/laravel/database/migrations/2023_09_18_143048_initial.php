<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("DROP VIEW IF EXISTS view_auth_users");

        Schema::table('users', function (Blueprint $table) {
            // custom options
            $table->json('custom_options')->nullable();
            // custom data
            $table->json('custom_data')->nullable();
        });


        Schema::create('vcards', function (Blueprint $table) {
            // Only Portuguese number are supported (9 digits)
            // Example: 919452846
            $table->string('phone_number', 9);
            $table->primary('phone_number');

            // vCard personal profile
            $table->string('name');
            $table->string('email');
            $table->string('photo_url')->nullable();

            // vCard credentials for authentication and transaction confirmation
            // Note: always use "hash" values for passwords/secret codes
            $table->string('password');
            $table->string('confirmation_code');

            // vCard valid date and status
            // Transactions are only allowed if blocked = false and current date <= valid_date
            $table->boolean('blocked', false);

            // vCard current financial profile:
            $table->decimal('balance', 9, 2)->default(0);
            $table->decimal('max_debit', 9, 2)->default(5000);

            // custom options
            $table->json('custom_options')->nullable();
            // custom data
            $table->json('custom_data')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('default_categories', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['C', 'D']);  // Credit (income) or Debit (expense)
            $table->string('name', 50);
            $table->unique(['type', 'name']);

            // custom options
            $table->json('custom_options')->nullable();
            // custom data
            $table->json('custom_data')->nullable();

            $table->softDeletes();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('vcard', 9);
            $table->foreign('vcard')->references('phone_number')->on('vcards');
            $table->enum('type', ['C', 'D']);  // Credit (income) or Debit (expense)
            $table->string('name', 50);
            $table->unique(['vcard', 'type', 'name']);

            // custom options
            $table->json('custom_options')->nullable();
            // custom data
            $table->json('custom_data')->nullable();

            $table->softDeletes();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('vcard', 9);
            $table->foreign('vcard')->references('phone_number')->on('vcards');

            // Only the date (does not include the time) of the transaction
            // This is useful for indexing transactions by date (grouping by date)
            $table->date('date');
            $table->index(['vcard', 'date']);
            // Both the date and time
            $table->dateTime('datetime');
            $table->enum('type', ['C', 'D']);  // Credit (income) or Debit (expense)
            $table->decimal('value', 9, 2);
            $table->decimal('old_balance', 9, 2);
            $table->decimal('new_balance', 9, 2);

            // Every transaction (credit / debit) must have an associated payment
            // The payment has a type and a reference
            // If transaction is between 2 vCards the reference will be the phone_number of the cVard
            // Supported ttypes of payments (The type of payment affects the validation)
                // vCard -  Phone number with 9 digits - must exist on the database
                // MBWAY -  Phone number with 9 digits
                // PayPal - eMail
                // IBAN - bank transfer (2 letters + 23 digits)
                // MB - Multibanco payment - entity number (5 digits) + Reference (9 digits))
                // VISA - Visa card number (16 digits)
            $table->enum('payment_type', ['VCARD', 'MBWAY', 'PAYPAL', 'IBAN', 'MB', 'VISA']);
            $table->string('payment_reference');

            // When a transaction is between 2 vCards, the following fields must be filled
            // Otherwise, they should be null
            // Note: the pair transaction should not refer to the current transaction
            // and the pair vcard should not refer to the current vcard
            $table->unsignedBigInteger('pair_transaction')->nullable();
            $table->foreign('pair_transaction')->references('id')->on('transactions');
            $table->string('pair_vcard', 9)->nullable();
            $table->foreign('pair_vcard')->references('phone_number')->on('vcards');

            // Transaction description and classification (optional)
            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories');
            $table->string('description')->nullable();

            // custom options
            $table->json('custom_options')->nullable();
            // custom data
            $table->json('custom_data')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement("
          CREATE VIEW view_auth_users AS
            SELECT phone_number as id, 'V' as user_type, phone_number as username, password, name, email, blocked, confirmation_code, photo_url, deleted_at from vcards
            union
            SELECT id, 'A', email, password, name, email, 0, null, null, null from users
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('default_categories');
        Schema::dropIfExists('vcards');
        DB::statement("DROP VIEW IF EXISTS view_auth_users");
    }
};
