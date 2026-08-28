<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PayslipPublishedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $messageBody;

    public function __construct(public readonly Payslip $payslip)
    {
        $template = EmailTemplate::where('company_id', $payslip->batch->company_id)
            ->where('type', 'payslip_published')->where('is_active', true)->orderBy('id')->first();
        $replacements = [
            '{employee_name}' => $payslip->employee->name,
            '{target_month}' => $payslip->batch->target_month->format('Y年n月'),
            '{login_url}' => route('login'),
            '{company_name}' => $payslip->batch->company->name,
            '{department_name}' => $payslip->employee->department?->name ?? '',
            '{login_id}' => $payslip->employee->login_id,
            '{payslip_title}' => $payslip->batch->name,
            '{payment_date}' => $payslip->batch->target_month->format('Y年n月'),
        ];
        $subject = strtr($template?->subject ?? '【{company_name}】給与明細を公開しました', $replacements);
        $this->messageBody = strtr($template?->body ?? "{employee_name} 様\n\n給与明細を公開しました。\n{login_url}", $replacements);
        $this->subject($subject);
        if ($template?->sender_address) {
            $this->from($template->sender_address, $template->sender_name ?: null);
        }
    }

    public function build(): self
    {
        return $this->view('emails.payslip-published');
    }
}
