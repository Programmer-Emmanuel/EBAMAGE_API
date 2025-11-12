<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestNeonConnection extends Command
{
    protected $signature = 'test:neon';
    protected $description = 'Test Neon PostgreSQL connection';

    public function handle()
    {
        try {
            DB::connection()->getPdo();
            $this->info('✅ Connection successful to Neon PostgreSQL');
        } catch (\Exception $e) {
            $this->error('❌ Connection failed: ' . $e->getMessage());
        }
    }
}