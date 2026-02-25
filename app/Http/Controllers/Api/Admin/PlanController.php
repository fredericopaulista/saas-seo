<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Display a listing of the plans.
     */
    public function index()
    {
        return response()->json(Plan::withCount('subscriptions')->get());
    }

    /**
     * Store a newly created plan in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:plans,slug',
            'price' => 'required|numeric|min:0',
            'max_projects' => 'required|integer|min:1',
            'asaas_id' => 'nullable|string',
            'features_json' => 'nullable|array'
        ]);

        $plan = Plan::create($validated);
        return response()->json($plan, 201);
    }

    /**
     * Display the specified plan.
     */
    public function show(Plan $plan)
    {
        return response()->json($plan);
    }

    /**
     * Update the specified plan in storage.
     */
    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:plans,slug,' . $plan->id,
            'price' => 'sometimes|numeric|min:0',
            'max_projects' => 'sometimes|integer|min:1',
            'asaas_id' => 'nullable|string',
            'features_json' => 'nullable|array'
        ]);

        $plan->update($validated);
        return response()->json($plan);
    }

    /**
     * Remove the specified plan from storage.
     */
    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            return response()->json(['message' => 'Não é possível deletar um plano com assinaturas ativas.'], 422);
        }

        $plan->delete();
        return response()->json(['message' => 'Plano deletado com sucesso.']);
    }
}
