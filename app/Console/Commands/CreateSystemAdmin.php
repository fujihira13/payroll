<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSystemAdmin extends Command
{
    protected $signature = 'payroll:create-system-admin {login_id?} {--name=} {--password=}';

    protected $description = '最初のシステム管理者を作成します';

    public function handle(): int
    {
        $data = [
            'name' => $this->option('name') ?: $this->ask('氏名'),
            'login_id' => $this->argument('login_id') ?: $this->ask('ログインID'),
            'password' => $this->option('password') ?: $this->secret('パスワード（8文字以上）'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'login_id' => ['required', 'alpha_dash:ascii', 'min:4', 'max:20', 'unique:admins,login_id'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        Admin::create([
            'name' => $data['name'],
            'login_id' => $data['login_id'],
            'password' => Hash::make($data['password']),
            'force_password_change' => false,
            'is_active' => true,
        ]);

        $this->info('システム管理者を作成しました。');

        return self::SUCCESS;
    }
}
