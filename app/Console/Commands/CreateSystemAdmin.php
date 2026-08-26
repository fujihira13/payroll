<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSystemAdmin extends Command
{
    protected $signature = 'payroll:create-system-admin {email?} {--name=} {--password=}';

    protected $description = '最初のシステム管理者を作成します';

    public function handle(): int
    {
        $data = [
            'name' => $this->option('name') ?: $this->ask('氏名'),
            'email' => $this->argument('email') ?: $this->ask('メールアドレス'),
            'password' => $this->option('password') ?: $this->secret('パスワード（8文字以上）'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        User::create([
            'role' => UserRole::SystemAdmin,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        $this->info('システム管理者を作成しました。');

        return self::SUCCESS;
    }
}
