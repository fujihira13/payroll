<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $templates = EmailTemplate::where('company_id', $request->user()->company_id)->orderBy('name')->get();

        return view('company.email-template.index', compact('templates'));
    }

    public function create(): View
    {
        return view('company.email-template.form', ['template' => new EmailTemplate]);
    }

    public function store(Request $request): RedirectResponse
    {
        EmailTemplate::create($this->validated($request) + ['company_id' => $request->user()->company_id]);

        return redirect()->route('company.email-templates.index')->with('success', 'メールテンプレートを登録しました。');
    }

    public function edit(Request $request, EmailTemplate $email_template): View
    {
        $this->ensureTenant($request, $email_template);

        return view('company.email-template.form', ['template' => $email_template]);
    }

    public function update(Request $request, EmailTemplate $email_template): RedirectResponse
    {
        $this->ensureTenant($request, $email_template);
        $email_template->update($this->validated($request, $email_template));

        return redirect()->route('company.email-templates.index')->with('success', 'メールテンプレートを更新しました。');
    }

    public function destroy(Request $request, EmailTemplate $email_template): RedirectResponse
    {
        $this->ensureTenant($request, $email_template);
        $email_template->delete();

        return back()->with('success', 'メールテンプレートを削除しました。');
    }

    private function validated(Request $request, ?EmailTemplate $template = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('email_templates')->where('company_id', $request->user()->company_id)->ignore($template)],
            'type' => ['required', Rule::in(['payslip_published', 'employee_notice'])],
            'sender_name' => ['nullable', 'string', 'max:100'],
            'sender_address' => ['nullable', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function ensureTenant(Request $request, EmailTemplate $template): void
    {
        abort_unless($template->company_id === $request->user()->company_id, 404);
    }
}
