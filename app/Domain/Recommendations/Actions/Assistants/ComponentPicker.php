<?php

namespace DDD\Domain\Recommendations\Actions\Assistants;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Recommendations\Actions\Assistants\PageBuilder;
use DDD\App\Services\OpenAI\AssistantService;

class ComponentPicker implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;
    
    public $name = 'component_picker';
    public $timeout = 60;
    public $tries = 50;
    public $backoff = 5;

    protected AssistantService $assistant;

    public function __construct(AssistantService $assistant)
    {
        $this->assistant = $assistant;
    }

    function handle(Recommendation $recommendation)
    {
        $recommendation->update(['status' => $this->name . '_in_progress']);

        // Start the run if it hasn't been started yet
        if (!isset($recommendation->runs[$this->name])) {
            $this->assistant->addMessageToThread(
                threadId: $recommendation->thread_id,
                message: '',
            );
    
            $run = $this->assistant->createRun(
                threadId: $recommendation->thread_id,
                assistantId: 'asst_pSC8qeDAM1Le5PJVPlfZ9HYA',
                // maxPromptTokens: 40000,
                // maxCompletionTokens: 10000,
            );

            $recommendation->runs = array_merge($recommendation->runs, [
                $this->name => $run['id'],
            ]);

            $recommendation->save();
        }

        // Check the status of the run
        $run = $this->assistant->getRun(
            threadId: $recommendation->thread_id,
            runId: $recommendation->runs[$this->name]
        );

        // Log the status
        Log::info($this->name . ': ' . $run['status']);

        if (in_array($run['status'], ['requires_action', 'cancelled', 'failed', 'incomplete', 'expired'])) {
            // End the job
            Log::info('Ending the job...');
            $recommendation->update(['status' => $this->name . '_' . $run['status']]);
            return;
        }

        if (in_array($run['status'], ['in_progress', 'queued'])) {
            // Log::info($this->name . ' prompt tokens allowed: ' . $run['max_prompt_tokens']);
            // Log::info($this->name . ' completion tokens allowed: ' . $run['max_completion_tokens']);
            if (isset($run['usage'])) {
                Log::info($this->name . ' prompt tokens used: ' . $run['usage']['prompt_tokens']);
                Log::info($this->name . ' completion tokens used: ' . $run['usage']['completion_tokens']);
                Log::info('Current time: ' . now());
            }

            // Dispatch a new instance of the job with a delay
            self::dispatch($recommendation)->delay(now()->addSeconds($this->backoff));
            return;
        }

        if (in_array($run['status'], ['completed', 'incomplete'])) {
            $recommendation->update(['status' => $this->name . '_completed']);
            PageBuilder::dispatch($recommendation)->delay(now()->addSeconds(15));
            return;
        }

        return;
    }
}