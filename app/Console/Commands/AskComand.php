<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:ask-comand {text}')]
#[Description('Describe new element')]
class AskComand extends Command
{
    public function handle()
    {
        $text = $this->input('text');
        $answer = $this->ask("Введіть текст: ");
        $this->info("text: " . $answer. ' - ' . $text);
        self::SUCCESS;
    }
}
