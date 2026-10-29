<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A role is a named set of permissions: admin panel sections/items ("admin.items") and PDA menu items ("pda.receipt").
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_admin')->default(false); // full access, not editable
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
        });

        $now = now();
        $pda = collect(config('menu.items'))->reject(fn ($i) => $i['key'] === 'tasks')->map(fn ($i) => 'pda.'.$i['key'])->values()->all();

        $adminId = DB::table('roles')->insertGetId(['name' => 'Administrator', 'is_admin' => true, 'permissions' => json_encode([]), 'created_at' => $now, 'updated_at' => $now]);
        $pdaId = DB::table('roles')->insertGetId(['name' => 'PDA operator', 'is_admin' => false, 'permissions' => json_encode($pda), 'created_at' => $now, 'updated_at' => $now]);
        DB::table('roles')->insert(['name' => 'Operator', 'is_admin' => false, 'created_at' => $now, 'updated_at' => $now, 'permissions' => json_encode([
            'admin.documents', 'admin.items', 'admin.counterparties', 'admin.warehouses', 'admin.tasks', 'admin.messages', 'admin.pricetags',
        ])]);

        // existing users keep what they had: admins -> Administrator, operators -> PDA operator (a per-user menu restriction becomes its own role)
        $custom = [];
        foreach (DB::table('users')->get() as $user) {
            if ($user->role === 'admin') {
                $roleId = $adminId;
            } elseif ($user->menu_access) {
                $keys = collect(json_decode($user->menu_access, true))->map(fn ($k) => 'pda.'.$k)->sort()->values()->all();
                $sig = implode(',', $keys);
                $custom[$sig] ??= DB::table('roles')->insertGetId(['name' => 'PDA cheklangan '.(count($custom) + 1), 'is_admin' => false, 'permissions' => json_encode($keys), 'created_at' => $now, 'updated_at' => $now]);
                $roleId = $custom[$sig];
            } else {
                $roleId = $pdaId;
            }
            DB::table('users')->where('id', $user->id)->update(['role_id' => $roleId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('role_id'));
        Schema::dropIfExists('roles');
    }
};
