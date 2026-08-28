<?php

namespace App\Http\Controllers\System;

use App\Enums\PayslipItemCategory;
use App\Http\Controllers\Controller;
use App\Models\PayslipTemplate;
use App\Support\PayslipLayouts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayslipTemplateController extends Controller
{
    public function index(): View
    {
        $templates = PayslipTemplate::withCount('items')->latest()->paginate(15);

        return view('system.templates.index', compact('templates'));
    }

    public function create(): View
    {
        return view('system.templates.form', [
            'template' => new PayslipTemplate,
            'categories' => PayslipItemCategory::cases(),
            'layoutTypes' => PayslipLayouts::types(),
            'slots' => PayslipLayouts::slots(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data) {
            $template = PayslipTemplate::create([
                'created_by_admin_id' => auth('admin')->id(),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'],
                'layout_type' => $data['layout_type'],
            ]);
            $this->replaceItems($template, $data['items']);
        });

        return redirect()->route('manage.templates.index')->with('success', '帳票テンプレートを作成しました。');
    }

    public function edit(PayslipTemplate $template): View
    {
        $template->load('items');

        return view('system.templates.form', compact('template') + [
            'categories' => PayslipItemCategory::cases(),
            'layoutTypes' => PayslipLayouts::types(),
            'slots' => PayslipLayouts::slots($template->layout_type),
        ]);
    }

    public function update(Request $request, PayslipTemplate $template): RedirectResponse
    {
        $data = $this->validated($request, $template);
        DB::transaction(function () use ($data, $template) {
            $template->update(collect($data)->except('items')->all());
            $this->replaceItems($template, $data['items']);
        });

        return redirect()->route('manage.templates.index')->with('success', '帳票テンプレートを更新しました。既存の会社設定には影響しません。');
    }

    public function destroy(PayslipTemplate $template): RedirectResponse
    {
        if ($template->companySettings()->exists()) {
            return back()->withErrors(['template' => '会社で使用中のため削除できません。無効にしてください。']);
        }
        $template->delete();

        return back()->with('success', '帳票テンプレートを削除しました。');
    }

    private function validated(Request $request, ?PayslipTemplate $template = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
            'layout_type' => ['required', Rule::in(array_keys(PayslipLayouts::types()))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.code' => ['required', 'alpha_dash', 'max:60', 'distinct'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.category' => ['required', Rule::enum(PayslipItemCategory::class)],
            'items.*.data_type' => ['required', Rule::in(['amount', 'number', 'text'])],
            'items.*.is_required' => ['nullable', 'boolean'],
            'items.*.slot_code' => ['nullable', Rule::in(array_keys(PayslipLayouts::slots((string) $request->input('layout_type', 'standard'))))],
        ]);
    }

    private function replaceItems(PayslipTemplate $template, array $items): void
    {
        $template->items()->delete();
        foreach (array_values($items) as $index => $item) {
            $template->items()->create($item + [
                'sort_order' => $index,
                'is_required' => (bool) ($item['is_required'] ?? false),
            ]);
        }
    }
}
