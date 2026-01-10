<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\TicketEvent;
use Illuminate\Support\Facades\DB;

class BackfillTicketSource extends Command
{
    protected $signature = 'tickets:backfill-source';
    protected $description = 'Backfill source field for existing tickets based on TicketEvent meta';

    public function handle()
    {
        $this->info('Starting ticket source backfill...');
        
        // Find tickets created by chatbot (have event with source=chatbot in meta)
        $chatbotTicketIds = TicketEvent::where('event_type', 'created')
            ->whereNotNull('meta')
            ->where(function($q) {
                $q->whereRaw("JSON_EXTRACT(meta, '$.source') = 'chatbot'")
                  ->orWhere('meta->source', 'chatbot');
            })
            ->pluck('ticket_id')
            ->unique();
        
        $chatbotCount = Ticket::whereIn('id', $chatbotTicketIds)->update(['source' => 'chatbot']);
        $this->info("Updated {$chatbotCount} tickets to source=chatbot");
        
        // Update remaining tickets (with NULL or empty source) to manual
        $manualCount = Ticket::whereNull('source')->update(['source' => 'manual']);
        $this->info("Updated {$manualCount} tickets to source=manual (default)");
        
        // Summary
        $totals = DB::table('tickets')
            ->selectRaw("source, COUNT(*) as count")
            ->groupBy('source')
            ->get();
        
        $this->info("\nFinal source distribution:");
        foreach ($totals as $row) {
            $this->info("  {$row->source}: {$row->count}");
        }
        
        return 0;
    }
}
