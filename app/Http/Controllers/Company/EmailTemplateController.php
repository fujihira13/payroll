<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function edit(Request $request): View
    {
        $template = EmailTemplate::firstOrCreate(
            ['company_id' => $request->user()->company_id, 'type' => 'payslip_published'],
            [
                'subject' => '【{company_name}】{target_month}の給与明細を公開しました',
                'body' => "{employee_name} 様\n\n{target_month}の給与明細を公開しました。\n以下のURLからログインしてご確認ください。\n{login_url}\n\n{company_name}",
            ]
        );

        return view('company.email-template.edit', compact('template'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);
        EmailTemplate::updateOrCreate(
            ['company_id' => $request->user()->company_id, 'type' => 'payslip_published'],
            $data
        );

        return back()->with('success', '通知メールを保存しました。');
    }
}
