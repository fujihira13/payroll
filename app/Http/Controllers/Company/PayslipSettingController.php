<?php

namespace App\Http\Controllers\Company;

use App\Enums\PayslipItemCategory;
use App\Http\Controllers\Controller;
use App\Models\CompanyPayslipSetting;
use App\Models\PayslipTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayslipSettingController extends Controller
{
    public function index(Request $request): View
    {
        $settings = CompanyPayslipSetting::where('company_id', $request->user()->company_id)
            ->with(['template', 'items'])->latest()->get();

        return view('company.settings.index', compact('settings'));
    }

    public function create(): View
    {
        return view('company.settings.create', [
            'templates' => PayslipTemplate::where('is_active', true)->with('items')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payslip_template_id' => ['required', Rule::exists('payslip_templates', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
        ]);
        $template = PayslipTemplate::with('items')->findOrFail($data['payslip_template_id']);

        $setting = DB::transaction(function () use ($request, $data, $template) {
            CompanyPayslipSetting::where('company_id', $request->user()->company_id)->update(['is_active' => false]);
            $setting = CompanyPayslipSetting::create($data + [
                'company_id' => $request->user()->company_id,
                'configured_by' => $request->user()->id,
                'is_active' => true,
            ]);
            foreach ($template->items as $item) {
                $setting->items()->create([
                    'source_template_item_id' => $item->id,
                    'code' => $item->code,
                    'label' => $item->label,
                    'category' => $item->category,
                    'data_type' => $item->data_type,
                    'sort_order' => $item->sort_order,
                    'is_required' => $item->is_required,
                    'is_active' => true,
                ]);
            }

            return $setting;
        });

        return redirect()->route('company.settings.edit', $setting)->with('success', 'テンプレートを自社設定にコピーしました。');
    }

    public function edit(Request $request, CompanyPayslipSetting $setting): View
    {
        $this->ensureTenant($request, $setting);
        $setting->load('items', 'template');

        return view('company.settings.edit', compact('setting') + ['categories' => PayslipItemCategory::cases()]);
    }

    public function update(Request $request, CompanyPayslipSetting $setting): RedirectResponse
    {
        $this->ensureTenant($request, $setting);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.code' => ['required', 'alpha_dash', 'max:60', 'distinct'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.category' => ['required', Rule::enum(PayslipItemCategory::class)],
            'items.*.data_type' => ['required', Rule::in(['amount', 'number', 'text'])],
            'items.*.is_required' => ['nullable', 'boolean'],
            'items.*.is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $setting, $data) {
            if ((bool) $data['is_active']) {
                CompanyPayslipSetting::where('company_id', $request->user()->company_id)
                    ->whereKeyNot($setting->id)->update(['is_active' => false]);
            }
            $setting->update(['name' => $data['name'], 'is_active' => $data['is_active'], 'configured_by' => $request->user()->id]);
            $setting->items()->delete();
            foreach (array_values($data['items']) as $index => $item) {
                $setting->items()->create($item + [
                    'sort_order' => $index,
                    'is_required' => (bool) ($item['is_required'] ?? false),
                    'is_active' => (bool) ($item['is_active'] ?? false),
                ]);
            }
        });

        return back()->with('success', '自社用の明細項目を保存しました。');
    }

    private function ensureTenant(Request $request, CompanyPayslipSetting $setting): void
    {
        abort_unless($setting->company_id === $request->user()->company_id, 404);
    }
}
