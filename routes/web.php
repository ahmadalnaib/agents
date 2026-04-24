<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\WorkflowAdvisorController;
use App\Http\Controllers\WorkflowStepController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::post('/agent', [AgentController::class, 'handle'])->name('agent.handle');
    Route::post('/workflow/advice', [WorkflowAdvisorController::class, 'advice'])->name('workflow.advice');
    Route::patch('/workflow/steps/{step}/toggle', [WorkflowStepController::class, 'toggle'])->name('workflow.steps.toggle');
    Route::post('/workflow/steps/{step}/ask', [WorkflowStepController::class, 'ask'])->name('workflow.steps.ask');
});

require __DIR__.'/settings.php';
