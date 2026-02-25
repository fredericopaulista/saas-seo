<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     * With Spatie multitenancy, this is automatically scoped to the current tenant if configured right,
     * or we explicitly filter by user's current_tenant_id to be safe.
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->current_tenant_id;
        
        $projects = Project::where('tenant_id', $tenantId)->get();
        
        return response()->json($projects);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'required|url|max:255',
            'country' => 'nullable|string|max:2',
            'language' => 'nullable|string|max:2',
            'gsc_property' => 'required|string|max:255',
        ]);

        $tenantId = $request->user()->current_tenant_id;
        
        if (!$tenantId) {
            return response()->json(['message' => 'Nenhum espaço de trabalho ativo encontrado.'], 403);
        }

        // Ideally checking subscription limits here:
        // app(CheckSubscriptionLimits::class)->checkProjectQuota($tenantId);

        $project = new Project();
        $project->tenant_id = $tenantId;
        $project->name = $request->name;
        $project->domain = $request->domain;
        $project->country = $request->country;
        $project->language = $request->language;
        $project->gsc_property = $request->gsc_property;
        $project->save();

        return response()->json($project, 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $tenantId = $request->user()->current_tenant_id;
        $project = Project::where('tenant_id', $tenantId)->findOrFail($id);
        $project->delete();
        
        return response()->json(['message' => 'Projeto deletado com sucesso']);
    }
}
