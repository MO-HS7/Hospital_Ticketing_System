<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Policies\TicketPolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Ticket::class, TicketPolicy::class);
        
        // Health check: Chatbot AI configuration
        $this->checkChatbotAIConfig();
    }
    
    /**
     * Check Chatbot AI configuration and log warnings.
     */
    protected function checkChatbotAIConfig(): void
    {
        $aiEnabled = config('services.chatbot.ai_enabled', false);
        $apiKey = config('services.gemini.api_key');
        
        if ($aiEnabled && empty($apiKey)) {
            \Log::error('🚨 CHATBOT AI MISCONFIGURED: AI is enabled but GEMINI_API_KEY is missing!', [
                'action_required' => 'Set GEMINI_API_KEY in .env or disable AI with CHATBOT_AI_ENABLED=false',
                'fallback' => 'Chatbot will use rule-based responses (degraded experience)',
            ]);
        } elseif ($aiEnabled && !empty($apiKey)) {
            // SECURITY: Never log any portion of the API key
            \Log::info('✅ Chatbot AI enabled', [
                'model' => config('services.gemini.model', 'gemini-2.0-flash'),
                'key_configured' => true,
            ]);
        } else {
            \Log::debug('Chatbot AI disabled (using rule-based fallback)');
        }
    }
}
