<?php

use App\Ai\Agents\WritingAssistantAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('writing assistant agent has clearer instructions', function () {
    $agent = new WritingAssistantAgent('clearer');
    $instructions = $agent->instructions();

    expect($instructions)->toBeString();
    expect($instructions)->toContain('clearer');
    expect($instructions)->toContain('concise');
});

test('writing assistant agent has formal instructions', function () {
    $agent = new WritingAssistantAgent('formal');
    $instructions = $agent->instructions();

    expect($instructions)->toBeString();
    expect($instructions)->toContain('formal');
    expect($instructions)->toContain('professional');
});

test('writing assistant agent has friendly instructions', function () {
    $agent = new WritingAssistantAgent('friendly');
    $instructions = $agent->instructions();

    expect($instructions)->toBeString();
    expect($instructions)->toContain('friendlier');
    expect($instructions)->toContain('conversational');
});

test('writing assistant agent has shorter instructions', function () {
    $agent = new WritingAssistantAgent('shorter');
    $instructions = $agent->instructions();

    expect($instructions)->toBeString();
    expect($instructions)->toContain('shorter');
    expect($instructions)->toContain('preserving meaning');
});

test('writing assistant agent has rewrite instructions', function () {
    $agent = new WritingAssistantAgent('rewrite');
    $instructions = $agent->instructions();

    expect($instructions)->toBeString();
    expect($instructions)->toContain('clarity');
    expect($instructions)->toContain('tone');
});

test('writing assistant agent has default instructions for unknown action', function () {
    $agent = new WritingAssistantAgent('unknown_action');
    $instructions = $agent->instructions();

    expect($instructions)->toBeString();
    expect($instructions)->toContain('Improve the text');
});

test('writing assistant agent includes locale in instructions when provided', function () {
    $agent = new WritingAssistantAgent('clearer', 'fr');
    $instructions = $agent->instructions();

    expect($instructions)->toContain('fr');
    expect($instructions)->toContain('Translate');
});

test('writing assistant agent does not include locale when not provided', function () {
    $agent = new WritingAssistantAgent('clearer');
    $instructions = $agent->instructions();

    expect($instructions)->not->toContain('Translate');
});

test('writing assistant agent combines action and locale', function () {
    $agent = new WritingAssistantAgent('formal', 'es');
    $instructions = $agent->instructions();

    expect($instructions)->toContain('formal');
    expect($instructions)->toContain('es');
    expect($instructions)->toContain('Translate');
});

test('writing assistant agent handles null locale', function () {
    $agent = new WritingAssistantAgent('clearer', null);
    $instructions = $agent->instructions();

    expect($instructions)->not->toContain('Translate');
});
