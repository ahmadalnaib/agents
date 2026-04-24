<?php

use App\Ai\Agents\GermanyWorkflowAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it creates workflow and steps from workflow advisor response', function () {
    GermanyWorkflowAgent::fake([
        json_encode([
            'title' => 'Berlin Setup Workflow',
            'summary' => 'Steps for settling in after moving to Berlin.',
            'steps' => [
                [
                    'title' => 'Register your address',
                    'description' => 'Book an appointment and complete Anmeldung.',
                    'status' => 'pending',
                ],
                [
                    'title' => 'Pick health insurance',
                    'description' => 'Compare providers and submit enrollment.',
                    'status' => 'pending',
                ],
            ],
            'links' => [
                [
                    'title' => 'Berlin Service Portal',
                    'url' => 'https://service.berlin.de/',
                ],
                [
                    'title' => 'Germany Health System',
                    'url' => 'https://www.bundesgesundheitsministerium.de/',
                ],
            ],
        ], JSON_UNESCAPED_SLASHES),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('workflow.advice'), [
        'problem' => 'I moved to Germany and I need a clear process for registration and health insurance.',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('workflow.title', 'Berlin Setup Workflow')
        ->assertJsonCount(2, 'steps')
        ->assertJsonCount(2, 'links');

    $this->assertDatabaseHas('workflows', [
        'user_id' => $user->id,
        'title' => 'Berlin Setup Workflow',
        'status' => 'in_progress',
    ]);

    $this->assertDatabaseHas('workflow_steps', [
        'step_number' => 1,
        'title' => 'Register your address',
        'status' => 'pending',
    ]);
});

test('it falls back when the advisor returns non json text', function () {
    GermanyWorkflowAgent::fake([
        'Start by gathering your documents and speaking to your local authority.',
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('workflow.advice'), [
        'problem' => 'I need help understanding my first administrative steps in Germany.',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('workflow.title', 'Germany Problem Workflow')
        ->assertJsonCount(1, 'steps')
        ->assertJsonCount(0, 'links');
});
