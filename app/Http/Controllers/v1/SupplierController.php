<?php
// app/Http/Controllers/API/SupplierController.php
namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    /**
     * Get all suppliers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Supplier::query();

            if ($request->has('status')) {
                $isActive = $request->status === 'active';
                $query->where('is_active', $isActive);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('contact_person', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $suppliers = $query->orderBy('name')
                              ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $suppliers,
                'message' => 'Suppliers retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve suppliers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new supplier
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'address' => 'nullable|string',
            'email' => 'nullable|email|unique:suppliers,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $supplier = Supplier::create([
                'name' => $request->name,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'address' => $request->address,
                'email' => $request->email,
                'is_active' => true
            ]);

            return response()->json([
                'success' => true,
                'data' => $supplier,
                'message' => 'Supplier created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific supplier
     */
    public function show(Supplier $supplier): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $supplier,
                'message' => 'Supplier retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update supplier
     */
    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'contact_person' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:50',
            'address' => 'nullable|string',
            'email' => 'nullable|email|unique:suppliers,email,' . $supplier->id,
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $supplier->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $supplier,
                'message' => 'Supplier updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete supplier
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        try {
            // Check if supplier has stock movements
            if ($supplier->stockMovements()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete supplier with existing stock movements'
                ], 400);
            }

            $supplier->delete();

            return response()->json([
                'success' => true,
                'message' => 'Supplier deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle supplier status
     */
    public function toggleStatus(Supplier $supplier): JsonResponse
    {
        try {
            $supplier->update(['is_active' => !$supplier->is_active]);

            return response()->json([
                'success' => true,
                'data' => $supplier,
                'message' => 'Supplier status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update supplier status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get supplier stock movements
     */
    public function stockMovements(Supplier $supplier, Request $request): JsonResponse
    {
        try {
            $movements = $supplier->stockMovements()
                                 ->with(['productVariant.product', 'user'])
                                 ->orderBy('created_at', 'desc')
                                 ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $movements,
                'message' => 'Supplier stock movements retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve supplier stock movements: ' . $e->getMessage()
            ], 500);
        }
    }
}

