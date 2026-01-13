<?php

namespace App\Jobs;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AllPinJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private string $url, private string $token, private string $transid, private string $cloudid) {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $date = date('Y-m-d');
        $dataSend = [
            "trans_id" => $this->transid,
            "cloud_id" => $this->cloudid
        ];

        sleep(5);
        Http::withToken($this->token)->post($this->url, $dataSend);

        try {
            Log::info('ALL PIN SUCCESS', [
                'status' => true,
                'data' => $dataSend
            ]);
        } catch (\Exception $e) {
            Log::error('ALL PIN FAILED', [
                'status' => false,
                'data' => $dataSend
            ]);

            throw $e;
        }

    }
}
