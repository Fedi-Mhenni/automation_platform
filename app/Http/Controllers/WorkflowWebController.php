<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use Illuminate\Support\Facades\Auth;

class WorkflowWebController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function create()
    {
        return view('workflows.create');
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $workflow = Auth::user()->workflows()->create([
            'name'      => $request->name,
            'is_active' => false,
        ]);

        return redirect()->route('workflows.edit', $workflow)
            ->with('success', "Workflow « {$workflow->name} » créé.");
    }

    public function show(Workflow $workflow)
    {
        $this->authorizeWorkflow($workflow);
        return view('workflows.show', ['workflowId' => $workflow->id]);
    }

    public function edit(Workflow $workflow)
    {
        $this->authorizeWorkflow($workflow);
        return view('workflows.editor', compact('workflow'));
    }

    public function logs(Workflow $workflow)
    {
        $this->authorizeWorkflow($workflow);
        return view('workflows.logs', ['workflowId' => $workflow->id]);
    }

    public function test(Workflow $workflow)
    {
        $this->authorizeWorkflow($workflow);
        return view('workflows.test', ['workflowId' => $workflow->id]);
    }

    private function authorizeWorkflow(Workflow $workflow): void
    {
        abort_if($workflow->user_id !== Auth::id(), 403);
    }
}
