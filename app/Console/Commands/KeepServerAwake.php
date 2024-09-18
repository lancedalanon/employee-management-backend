<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class KeepServerAwake extends Command
{
    protected $signature = 'server:keep-awake';
    protected $description = 'Send a request to keep the server awake';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $url = 'http://localhost/ping'; 
        
        try {
            $response = Http::get($url);

            if ($response->successful()) {
                $this->info('Server kept awake successfully.');
            } else {
                $this->error('Failed to keep the server awake.');
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
