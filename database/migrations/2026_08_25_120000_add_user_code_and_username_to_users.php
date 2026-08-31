<?php

use App\Support\FinancialYear;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Users get the same FY-aware series the invoices use — MQ/US/26-27/0001 —
 * plus a username, which is what staff actually quote to each other rather
 * than a database id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_code')->nullable()->unique()->after('id');
            $table->string('fy_label', 5)->nullable()->after('user_code');
            $table->string('username')->nullable()->unique()->after('name');
        });

        // Existing accounts get a code in id order, and a username from their
        // email local-part (or phone) so nothing is left blank.
        $seq = [];
        foreach (DB::table('users')->orderBy('id')->get() as $user) {
            $fy = FinancialYear::label($user->created_at);
            $seq[$fy] = ($seq[$fy] ?? 0) + 1;

            $base = $user->email ? strtok($user->email, '@') : ($user->phone ?: 'user'.$user->id);
            $username = strtolower(preg_replace('/[^A-Za-z0-9._-]/', '', $base)) ?: 'user'.$user->id;

            while (DB::table('users')->where('username', $username)->exists()) {
                $username .= $user->id;
            }

            DB::table('users')->where('id', $user->id)->update([
                'user_code' => 'MQ/US/'.$fy.'/'.str_pad((string) $seq[$fy], 4, '0', STR_PAD_LEFT),
                'fy_label' => $fy,
                'username' => $username,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['user_code', 'fy_label', 'username']);
        });
    }
};
