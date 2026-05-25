<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_admin';

    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('username')->nullable()->unique();
            $table->string('password');
            $table->boolean('status')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('admin_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('admin_role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_role_id');
            $table->unsignedBigInteger('admin_permission_id');

            $table->foreign('admin_role_id')->references('id')->on('admin_roles')->onDelete('cascade');
            $table->foreign('admin_permission_id')->references('id')->on('admin_permissions')->onDelete('cascade');
            $table->primary(['admin_role_id', 'admin_permission_id'], 'admin_role_perm_primary');
        });

        Schema::create('admin_model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_user_id');
            $table->unsignedBigInteger('admin_role_id');

            $table->foreign('admin_user_id')->references('id')->on('admin_users')->onDelete('cascade');
            $table->foreign('admin_role_id')->references('id')->on('admin_roles')->onDelete('cascade');
            $table->primary(['admin_user_id', 'admin_role_id'], 'admin_model_role_primary');
        });

        Schema::create('admin_model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_user_id');
            $table->unsignedBigInteger('admin_permission_id');

            $table->foreign('admin_user_id')->references('id')->on('admin_users')->onDelete('cascade');
            $table->foreign('admin_permission_id')->references('id')->on('admin_permissions')->onDelete('cascade');
            $table->primary(['admin_user_id', 'admin_permission_id'], 'admin_model_perm_primary');
        });

        $permissions = [
            'companies_view',
            'companies_approve',
            'companies_reject',
            'blogs_view',
            'blogs_create',
            'blogs_edit',
            'blogs_delete',
            'testimonials_view',
            'testimonials_create',
            'testimonials_edit',
            'testimonials_delete',
            'privacy_policy_view',
            'privacy_policy_edit',
            'terms_conditions_view',
            'terms_conditions_edit',
            'users_view',
            'users_edit',
        ];

        $now = now();
        foreach ($permissions as $permission) {
            DB::connection('mysql_admin')->table('admin_permissions')->insert([
                'name' => $permission,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $superAdminRoleId = DB::connection('mysql_admin')->table('admin_roles')->insertGetId([
            'name' => 'Super Admin',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $permissionIds = DB::connection('mysql_admin')->table('admin_permissions')->pluck('id');
        foreach ($permissionIds as $permissionId) {
            DB::connection('mysql_admin')->table('admin_role_has_permissions')->insert([
                'admin_role_id' => $superAdminRoleId,
                'admin_permission_id' => $permissionId,
            ]);
        }

        $adminUserId = DB::connection('mysql_admin')->table('admin_users')->insertGetId([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::connection('mysql_admin')->table('admin_model_has_roles')->insert([
            'admin_user_id' => $adminUserId,
            'admin_role_id' => $superAdminRoleId,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_model_has_permissions');
        Schema::dropIfExists('admin_model_has_roles');
        Schema::dropIfExists('admin_role_has_permissions');
        Schema::dropIfExists('admin_permissions');
        Schema::dropIfExists('admin_roles');
        Schema::dropIfExists('admin_users');
    }
};
