<?php

use App\Ai\Agents\WorkflowStepCoachAgent;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it toggles a workflow step status for the owner', function () {
    $user = User::factory()->create();

    $workflow = Workflow::create([
        'user_id' => $user->id,
        'title' => 'Berlin move',
        'input' => 'Need a setup plan for moving to Berlin.',
        'status' => 'in_progress',
    ]);

    $step = WorkflowStep::create([
        'workflow_id' => $workflow->id,
        'step_number' => 1,
        'title' => 'Register address',
        'description' => 'Book Anmeldung appointment.',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->patchJson(route('workflow.steps.toggle', ['step' => $step]));

    $response
        ->assertSuccessful()
        ->assertJsonPath('step.id', $step->id)
        ->assertJsonPath('step.status', 'done')
        ->assertJsonPath('progress.done', 1)
        ->assertJsonPath('progress.total', 1)
        ->assertJsonPath('progress.percentage', 100);

    $this->assertDatabaseHas('workflow_steps', [
        'id' => $step->id,
        'status' => 'done',
    ]);
});

test('it answers a step question using the step coach agent', function () {
    WorkflowStepCoachAgent::fake([
        'Take your passport, rental contract, and Wohnungsgeberbestatigung to your Anmeldung appointment.',
    ]);

    $user = User::factory()->create();

    $workflow = Workflow::create([
        'user_id' => $user->id,
        'title' => 'Berlin move',
        'input' => 'Need a setup plan for moving to Berlin.',
        'status' => 'in_progress',
    ]);

    $step = WorkflowStep::create([
        'workflow_id' => $workflow->id,
        'step_number' => 1,
        'title' => 'Register address',
        'description' => 'Book Anmeldung appointment.',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->postJson(route('workflow.steps.ask', ['step' => $step]), [
        'question' => 'What documents should I take?',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('step.id', $step->id)
        ->assertJsonPath(
            'answer',
            'Take your passport, rental contract, and Wohnungsgeberbestatigung to your Anmeldung appointment.'
        );
});

test('it forbids non owners from toggling another users step', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $workflow = Workflow::create([
        'user_id' => $owner->id,
        'title' => 'Berlin move',
        'input' => 'Need a setup plan for moving to Berlin.',
        'status' => 'in_progress',
    ]);

    $step = WorkflowStep::create([
        'workflow_id' => $workflow->id,
        'step_number' => 1,
        'title' => 'Register address',
        'description' => 'Book Anmeldung appointment.',
        'status' => 'pending',
    ]);

    $this->actingAs($otherUser)
        ->patchJson(route('workflow.steps.toggle', ['step' => $step]))
        ->assertForbidden();
});
