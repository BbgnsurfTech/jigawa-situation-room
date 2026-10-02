<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with('lga', 'ward')->withCount('wardReports');

        if ($request->role) $query->byRole($request->role);
        if ($request->lga_id) $query->byLga($request->lga_id);
        if ($request->is_active !== null) $query->where('is_active', (bool) $request->is_active);

        return response()->json($query->paginate($request->input('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['super_admin', 'supervisor', 'ward_agent'])],
            'lga_id' => ['nullable', 'exists:lgas,id'],
            'ward_id' => ['nullable', 'exists:wards,id'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'party_affiliation' => ['nullable', 'exists:parties,acronym'],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['name'] = $data['full_name'];
        $user = User::create($data);
        $user->assignRole($data['role']);

        return response()->json(['message' => 'User created.', 'user' => $user], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user->load('lga', 'ward'));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['sometimes', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'lga_id' => ['sometimes', 'nullable', 'exists:lgas,id'],
            'ward_id' => ['sometimes', 'nullable', 'exists:wards,id'],
            'gender' => ['sometimes', Rule::in(['male', 'female', 'other'])],
        ]);

        $user->update($data);
        return response()->json(['message' => 'User updated.', 'user' => $user]);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->update(['is_active' => false]);
        $user->tokens()->delete();
        return response()->json(['message' => 'User deactivated.']);
    }
}
