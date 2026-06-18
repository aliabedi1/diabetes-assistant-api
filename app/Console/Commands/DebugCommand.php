<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('debug')]
#[Description('Command description')]
class DebugCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {

    }
}
