<?php

namespace Database\Seeders;

use App\Models\TicketStatus;
use Illuminate\Database\Seeder;

class TicketStatusSeeder extends Seeder
{
    public function run(): void
    {
        TicketStatus::create(['name' => 'abierto']);
        TicketStatus::create(['name' => 'en proceso']);
        TicketStatus::create(['name' => 'cerrado']);
    }
}
