<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmployeeNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $messageBody;

    public function __construct(public readonly User $employee, public readonly EmailTemplate $template)
    {
        $replacements = [
            '{company_name}' => $employee->company->name,
            '{department_name}' => $employee->department?->name ?? '',
            '{employee_name}' => $employee->name,
            '{login_id}' => $employee->login_id,
            '{login_url}' => route('login'),
            '{payslip_title}' => '',
            '{payment_date}' => '',
        ];
        $this->subject(strtr($template->subject, $replacements));
        $this->messageBody = strtr($template->body, $replacements);
        if ($template->sender_address) {
            $this->from($template->sender_address, $template->sender_name ?: null);
        }
    }

    public function build(): self
    {
        return $this->view('emails.payslip-published');
    }
}
