<?php

namespace App\Console\Commands;

use App\Services\OneCClient;
use Illuminate\Console\Command;

class OneCSync extends Command
{
    protected $signature = 'onec:sync';

    protected $description = 'Push completed documents to Adines';

    public function handle(OneCClient $onec): int
    {
        if (! $onec->enabled()) {
            $this->warn('ONEC_BASE_URL is empty; sync disabled.');

            return self::SUCCESS;
        }

        [$sent, $failed] = $onec->pushPending();
        $this->info("Documents sent: {$sent}, failed: {$failed}");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
