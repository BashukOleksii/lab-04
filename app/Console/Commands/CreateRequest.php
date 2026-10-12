<?php

namespace App\Console\Commands;

use App\Models\RepairRequest;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function PHPSTORM_META\type;

#[Signature('app:create-request {type}')]
#[Description('Command description')]
class CreateRequest extends Command
{
    
    protected $signature = 'app:create-request {type}';

    protected $description = 'Надсилає сповіщення конкретному користувачу за його ID';

    public function handle()
    {
        $type = $this->argument('type');

        match($type){
            'open' => RepairRequest::factory()->open()->create(),
            'in_progress' => RepairRequest::factory()->inProgress()->create(),
            default => RepairRequest::factory()->closed()->create()
        };
        
        return Command::SUCCESS;
    }

}
