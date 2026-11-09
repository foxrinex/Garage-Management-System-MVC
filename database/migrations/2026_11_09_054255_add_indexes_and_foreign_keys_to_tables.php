<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Categories Unique Index
        Schema::table('categories', function (Blueprint $table) {
            $table->unique('category_name', 'category_name');
        });

        // 2. Cars Foreign Key & Index
        Schema::table('cars', function (Blueprint $table) {
            $table->unsignedInteger('category_id')->nullable()->change();
            $table->index('category_id', 'category_id');
            $table->foreign('category_id', 'cars_ibfk_1')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('cascade');
        });

        // 3. Customer Foreign Key & Index
        Schema::table('customer', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
            $table->index('user_id', 'user_id');
            $table->foreign('user_id', 'customer_ibfk_1')
                  ->references('uid')
                  ->on('users')
                  ->onDelete('cascade');
        });

        // 4. Employees Foreign Key & Index
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
            $table->index('user_id', 'user_id');
            $table->foreign('user_id', 'employees_ibfk_1')
                  ->references('uid')
                  ->on('users')
                  ->onDelete('cascade');
        });

        // 5. Manager Foreign Key & Index
        Schema::table('manager', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
            $table->index('user_id', 'user_id');
            $table->foreign('user_id', 'manager_ibfk_1')
                  ->references('uid')
                  ->on('users')
                  ->onDelete('cascade');
        });

        // 6. Users Unique Indexes
        Schema::table('users', function (Blueprint $table) {
            $table->unique('username', 'username');
        });

        // 7. Tags Unique Index
        Schema::table('tags', function (Blueprint $table) {
            $table->unique('tag_name', 'tag_name');
        });

        // 8. Builder Temporary Unique Index
        Schema::table('builder_temporary', function (Blueprint $table) {
            $table->unique(['user_id', 'category_id'], 'user_cat');
        });

        // 9. Auction Bids Foreign Key & Index
        Schema::table('auction_bids', function (Blueprint $table) {
            $table->unsignedInteger('auction_id')->change();
            $table->index('auction_id', 'auction_id');
            $table->foreign('auction_id', 'auction_bids_ibfk_1')
                  ->references('id')
                  ->on('auctions')
                  ->onDelete('cascade');
        });

        // 10. Orders Foreign Keys & Indexes
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('customer_id')->nullable()->change();
            $table->unsignedInteger('car_id')->nullable()->change();

            $table->index('customer_id', 'fk_persistent_history');
            $table->index('car_id', 'fk_order_car');

            $table->foreign('car_id', 'fk_order_car')
                  ->references('id')
                  ->on('cars')
                  ->onDelete('set null');

            $table->foreign('customer_id', 'fk_persistent_history')
                  ->references('uid')
                  ->on('users')
                  ->onDelete('set null');
        });

        // 11. Wishlist Unique Index
        Schema::table('wishlist', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id', 'car_id'], 'unique_wishlist');
        });
    }

    public function down(): void
    {
        Schema::table('wishlist', function (Blueprint $table) {
            $table->dropUnique('unique_wishlist');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('fk_order_car');
            $table->dropForeign('fk_persistent_history');
            $table->dropIndex('fk_order_car');
            $table->dropIndex('fk_persistent_history');
        });

        Schema::table('auction_bids', function (Blueprint $table) {
            $table->dropForeign('auction_bids_ibfk_1');
            $table->dropIndex('auction_id');
        });

        Schema::table('builder_temporary', function (Blueprint $table) {
            $table->dropUnique('user_cat');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique('tag_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('username');
        });

        Schema::table('manager', function (Blueprint $table) {
            $table->dropForeign('manager_ibfk_1');
            $table->dropIndex('user_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign('employees_ibfk_1');
            $table->dropIndex('user_id');
        });

        Schema::table('customer', function (Blueprint $table) {
            $table->dropForeign('customer_ibfk_1');
            $table->dropIndex('user_id');
        });

        Schema::table('cars', function (Blueprint $table) {
            $table->dropForeign('cars_ibfk_1');
            $table->dropIndex('category_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('category_name');
        });
    }
};