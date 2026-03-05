<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PropertyController extends Controller
{
    /**
     * Get all properties with unit counts
     */
    public function index()
    {
        $properties = Property::with(['units' => function($query) {
            $query->select('id', 'property_id');
        }])
        ->get()
        ->map(function($property) {
            return [
                'id' => $property->id,
                'project_name' => $property->project_name,
                'location' => $property->location,
                'thumbnail' => $property->thumbnail ? asset('storage/' . $property->thumbnail) : null,
                'unit_count' => $property->units->count(),
                'created_at' => $property->created_at,
            ];
        });

        return response()->json([
            'properties' => $properties
        ]);
    }

    /**
     * Create a new property
     */
    public function store(Request $request)
    {
        $request->validate([
            'project_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        // Check if property with same name already exists
        $existing = Property::where('project_name', $request->project_name)->first();
        if ($existing) {
            return response()->json([
                'message' => 'Property with this name already exists'
            ], 422);
        }

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('property-thumbnails', 'public');
        }

        $property = Property::create([
            'project_name' => $request->project_name,
            'location' => $request->location,
            'thumbnail' => $thumbnailPath,
        ]);

        return response()->json([
            'message' => 'Property created successfully',
            'property' => [
                'id' => $property->id,
                'project_name' => $property->project_name,
                'location' => $property->location,
                'thumbnail' => $thumbnailPath ? asset('storage/' . $thumbnailPath) : null,
                'unit_count' => 0,
                'created_at' => $property->created_at,
            ]
        ], 201);
    }

    /**
     * Get a specific property
     */
    public function show($id)
    {
        $property = Property::with('units')->findOrFail($id);
        
        return response()->json([
            'property' => [
                'id' => $property->id,
                'project_name' => $property->project_name,
                'location' => $property->location,
                'unit_count' => $property->units->count(),
                'created_at' => $property->created_at,
            ]
        ]);
    }

    /**
     * Update a property
     */
    public function update(Request $request, $id)
    {
        $property = Property::findOrFail($id);

        $request->validate([
            'project_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        // Check if another property has this name
        $existing = Property::where('project_name', $request->project_name)
            ->where('id', '!=', $id)
            ->first();
            
        if ($existing) {
            return response()->json([
                'message' => 'Another property with this name already exists'
            ], 422);
        }

        // Update units' project_name if it changed
        if ($property->project_name !== $request->project_name) {
            Unit::where('project_name', $property->project_name)
                ->update(['project_name' => $request->project_name]);
        }

        // Handle thumbnail update
        $updateData = [
            'project_name' => $request->project_name,
            'location' => $request->location,
        ];

        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail if exists
            if ($property->thumbnail && Storage::disk('public')->exists($property->thumbnail)) {
                Storage::disk('public')->delete($property->thumbnail);
            }
            $updateData['thumbnail'] = $request->file('thumbnail')->store('property-thumbnails', 'public');
        }

        $property->update($updateData);

        return response()->json([
            'message' => 'Property updated successfully',
            'property' => [
                'id' => $property->id,
                'project_name' => $property->project_name,
                'location' => $property->location,
                'thumbnail' => $property->thumbnail ? asset('storage/' . $property->thumbnail) : null,
                'unit_count' => $property->units->count(),
                'created_at' => $property->created_at,
            ]
        ]);
    }

    /**
     * Delete a property
     */
    public function destroy($id)
    {
        $property = Property::findOrFail($id);

        // Check if property has units
        $unitCount = $property->units()->count();
        
        if ($unitCount > 0) {
            return response()->json([
                'message' => 'Cannot delete property with existing units. Please delete all units first.',
                'unit_count' => $unitCount
            ], 422);
        }

        // Delete thumbnail if exists
        if ($property->thumbnail && Storage::disk('public')->exists($property->thumbnail)) {
            Storage::disk('public')->delete($property->thumbnail);
        }

        $property->delete();

        return response()->json([
            'message' => 'Property deleted successfully'
        ]);
    }
}
