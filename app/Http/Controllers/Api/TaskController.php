<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        // Return tasks assigned to the current user OR all tasks if user is admin?
        // For now, let's return all tasks to match the web dashboard view logic
        // or just 'latest' tasks.

        // If we want personalized tasks:
        // $tasks = Task::where('assigned_to', $request->user()->id)->get();

        // For the MVP, return all project tasks (flat list) or grouped?
        // Let's return a flat list of tasks for now, maybe filtered by status.
        return response()->json(
            Task::with('project')->latest()->get()
        );
    }

    public function show(Task $task)
    {
        return response()->json($task->load('project'));
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        $task->update($validated);

        return response()->json($task);
    }
}
