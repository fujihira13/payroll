<?php

namespace App\Http\Controllers\Company;

use App\Enums\PayslipItemCategory;
use App\Http\Controllers\Controller;
use App\Models\CompanyPayslipSetting;
use App\Models\PayslipTemplate;
use App\Support\PayslipLayouts;
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

    public function prepare(Request $request): View
    {
        $data = $this->selectionData($request);
        $template = PayslipTemplate::where('is_active', true)->with('items')->findOrFail($data['payslip_template_id']);

        return view('company.settings.map', compact('template', 'data') + [
            'slots' => PayslipLayouts::slots($template->layout_type),
        ]);
    }

    public function confirm(Request $request): View
    {
        $data = $this->mappingData($request);
        $template = PayslipTemplate::where('is_active', true)->findOrFail($data['payslip_template_id']);

        return view('company.settings.confirm', compact('template', 'data') + [
            'slots' => PayslipLayouts::slots($data['layout_type']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->mappingData($request);
        $template = PayslipTemplate::where('is_active', true)->findOrFail($data['payslip_template_id']);
        $setting = DB::transaction(function () use ($request, $data, $template) {
            CompanyPayslipSetting::where('company_id', $request->user()->company_id)->update(['is_active' => false]);
            $setting = CompanyPayslipSetting::create([
                'company_id' => $request->user()->company_id,
                'payslip_template_id' => $template->id,
                'configured_by' => $request->user()->id,
                'name' => $data['name'],
                'layout_type' => $data['layout_type'],
                'is_active' => true,
            ]);
            foreach (array_values($data['items']) as $index => $item) {
                $setting->items()->create($item + [
                    'sort_order' => $index,
                    'is_required' => (bool) ($item['is_required'] ?? false),
                    'is_active' => (bool) ($item['is_active'] ?? false),
                ]);
            }

            return $setting;
        });

        return redirect()->route('company.settings.edit', $setting)->with('success', '3段階の帳票設定を作成しました。');
    }

    public function edit(Request $request, CompanyPayslipSetting $setting): View
    {
        $this->ensureTenant($request, $setting);
        $setting->load('items', 'template');

        return view('company.settings.edit', compact('setting') + [
            'categories' => PayslipItemCategory::cases(),
            'layoutTypes' => PayslipLayouts::types(),
            'slots' => PayslipLayouts::slots($setting->layout_type),
        ]);
    }

    public function update(Request $request, CompanyPayslipSetting $setting): RedirectResponse
    {
        $this->ensureTenant($request, $setting);
        $data = $this->editableData($request);
        DB::transaction(function () use ($request, $setting, $data) {
            if ((bool) $data['is_active']) {
                CompanyPayslipSetting::where('company_id', $request->user()->company_id)
                    ->whereKeyNot($setting->id)->update(['is_active' => false]);
            }
            $setting->update([
                'name' => $data['name'],
                'layout_type' => $data['layout_type'],
                'is_active' => $data['is_active'],
                'configured_by' => $request->user()->id,
            ]);
            $setting->items()->delete();
            foreach (array_values($data['items']) as $index => $item) {
                $setting->items()->create($item + [
                    'sort_order' => $index,
                    'is_required' => (bool) ($item['is_required'] ?? false),
                    'is_active' => (bool) ($item['is_active'] ?? false),
                ]);
            }
        });

        return back()->with('success', '帳票の項目と配置を保存しました。');
    }

    private function selectionData(Request $request): array
    {
        return $request->validate([
            'payslip_template_id' => ['required', Rule::exists('payslip_templates', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
        ]);
    }

    private function mappingData(Request $request): array
    {
        return $request->validate([
            'payslip_template_id' => ['required', Rule::exists('payslip_templates', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'layout_type' => ['required', Rule::in(array_keys(PayslipLayouts::types()))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.source_template_item_id' => ['nullable', 'exists:payslip_template_items,id'],
            'items.*.code' => ['required', 'alpha_dash', 'max:60', 'distinct'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.category' => ['required', Rule::enum(PayslipItemCategory::class)],
            'items.*.data_type' => ['required', Rule::in(['amount', 'number', 'text'])],
            'items.*.slot_code' => ['nullable', Rule::in(array_keys(PayslipLayouts::slots($request->input('layout_type', 'standard'))))],
            'items.*.is_required' => ['nullable', 'boolean'],
            'items.*.is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function editableData(Request $request): array
    {
        return $request->validate([
            'payslip_template_id' => ['required', 'exists:payslip_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'layout_type' => ['required', Rule::in(array_keys(PayslipLayouts::types()))],
            'is_active' => ['required', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.source_template_item_id' => ['nullable', 'exists:payslip_template_items,id'],
            'items.*.code' => ['required', 'alpha_dash', 'max:60', 'distinct'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.category' => ['required', Rule::enum(PayslipItemCategory::class)],
            'items.*.data_type' => ['required', Rule::in(['amount', 'number', 'text'])],
            'items.*.slot_code' => ['nullable', Rule::in(array_keys(PayslipLayouts::slots($request->input('layout_type', 'standard'))))],
            'items.*.is_required' => ['nullable', 'boolean'],
            'items.*.is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function ensureTenant(Request $request, CompanyPayslipSetting $setting): void
    {
        abort_unless($setting->company_id === $request->user()->company_id, 404);
    }
}
