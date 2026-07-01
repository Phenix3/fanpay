<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Sms\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOtpSms implements ShouldQueue
{
    use Queueable;

    public string $phone;

    public string $code;

    public string $purpose;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phone, string $code, string $purpose = 'verification')
    {
        $this->phone = $phone;
        $this->code = $code;
        $this->purpose = $purpose;
    }

    /**
     * Execute the job.
     */
    public function handle(SmsService $smsService): void
    {
        $smsService->sendOtp($this->phone, $this->code);
    }
}
