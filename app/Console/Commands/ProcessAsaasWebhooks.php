<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessAsaasWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asaas:process-events {--all : Processa todos, inclusive sucessos} {--failed : Processa apenas falhas} {--id= : Processa um ID específico}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processa eventos do webhook do Asaas manualmente a partir do log';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = \App\Models\WebhookEvent::query();

        if ($this->option('id')) {
            $query->where('event_id', $this->option('id'));
        } elseif ($this->option('failed')) {
            $query->where('status', 'failed');
        } elseif (!$this->option('all')) {
            $query->whereIn('status', ['pending', 'failed']);
        }

        $events = $query->get();

        if ($events->isEmpty()) {
            $this->info('Nenhum evento encontrado para processar.');
            return;
        }

        $this->info("Processando {$events->count()} eventos...");

        foreach ($events as $event) {
            $this->line("Despachando evento: {$event->event_id} ({$event->event_type})");
            
            // Bypass idempotency reset if needed
            if ($event->status !== 'pending') {
                $event->update(['status' => 'pending']);
            }
            
            \App\Jobs\ProcessAsaasWebhookJob::dispatch($event->event_id, $event->event_type, $event->payload);
        }

        $this->info('Todos os eventos foram enviados para a fila de processamento.');
    }
}
