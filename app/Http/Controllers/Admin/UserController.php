<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display users list.
     */
    public function index()
    {
        return view('admin.users.index');
    }

    /**
     * Get all users with pagination and search.
     */
    public function getUsers(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;
        
        $query = User::with('roles', 'permissions');
        
        // Apply search if provided
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('first_name', 'like', '%' . $search . '%')
                  ->orWhere('last_name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }
        
        // Get paginated results
        $users = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        // Format the data
        $data = [
            'current_page' => $users->currentPage(),
            'data' => collect($users->items())->map(function ($user) {
                return [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'name' => $user->name ?? trim($user->first_name . ' ' . $user->last_name),
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'country_code' => $user->country_code,
                    'avatar' => $user->avatar_url,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'permissions' => $user->getDirectPermissions()->pluck('name')->toArray(),
                    'is_active' => (bool) $user->is_active,
                    'is_platform_admin' => (bool) $user->is_platform_admin,
                    'has_two_factor' => !is_null($user->two_factor_confirmed_at),
                    'is_locked' => $user->locked_until && now()->lt($user->locked_until),
                    'email_verified_at' => $user->email_verified_at?->format('M d, Y H:i'),
                    'last_login_at' => $user->last_login_at ? $user->last_login_at->format('M d, Y H:i') : 'Never',
                    'created_at' => $user->created_at->format('M d, Y'),
                ];
            })->toArray(),
            'first_page_url' => $users->url(1),
            'from' => $users->firstItem(),
            'last_page' => $users->lastPage(),
            'last_page_url' => $users->url($users->lastPage()),
            'next_page_url' => $users->nextPageUrl(),
            'prev_page_url' => $users->previousPageUrl(),
            'to' => $users->lastItem(),
            'total' => $users->total(),
            'per_page' => $perPage,
        ];
        
        return response()->json($data);
    }

    /**
     * Full detail payload for the View modal.
     */
    public function getUserDetail($id)
    {
        try {
            $user = User::with([
                'roles:id,name',
                'currentCompany:id,name,public_id',
                'devices' => fn ($q) => $q->orderByDesc('last_active_at')->limit(10),
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'name' => $user->name ?? trim($user->first_name . ' ' . $user->last_name),
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'country_code' => $user->country_code,
                    'avatar' => $user->avatar_url,
                    'bio' => $user->bio,
                    'is_active' => (bool) $user->is_active,
                    'is_platform_admin' => (bool) $user->is_platform_admin,
                    'email_verified_at' => $user->email_verified_at?->format('M d, Y H:i'),
                    'last_login_at' => $user->last_login_at?->format('M d, Y H:i'),
                    'last_login_ip' => $user->last_login_ip,
                    'created_at' => $user->created_at?->format('M d, Y H:i'),
                    'updated_at' => $user->updated_at?->format('M d, Y H:i'),

                    'has_two_factor' => !is_null($user->two_factor_confirmed_at),
                    'two_factor_method' => $user->two_factor_method,
                    'two_factor_confirmed_at' => $user->two_factor_confirmed_at?->format('M d, Y H:i'),
                    'password_changed_at' => $user->password_changed_at?->format('M d, Y H:i'),
                    'failed_login_attempts' => (int) $user->failed_login_attempts,
                    'is_locked' => $user->locked_until && now()->lt($user->locked_until),
                    'locked_until' => $user->locked_until?->format('M d, Y H:i'),
                    'terms_accepted_at' => $user->terms_accepted_at?->format('M d, Y H:i'),

                    'locale' => $user->locale,
                    'timezone' => $user->timezone,

                    'current_company' => $user->currentCompany ? [
                        'id' => $user->currentCompany->id,
                        'name' => $user->currentCompany->name,
                        'public_id' => $user->currentCompany->public_id,
                    ] : null,
                    'current_mode' => $user->current_mode,

                    'roles' => $user->roles->pluck('name')->toArray(),
                    'direct_permissions' => $user->getDirectPermissions()->pluck('name')->toArray(),
                    'role_permissions' => $user->getPermissionsViaRoles()->pluck('name')->toArray(),

                    'devices' => $user->devices->map(fn ($d) => [
                        'id' => $d->id,
                        'device_name' => $d->device_name,
                        'platform' => $d->platform,
                        'browser' => $d->browser,
                        'ip_address' => $d->ip_address,
                        'location' => $d->location,
                        'is_trusted' => (bool) $d->is_trusted,
                        'last_active_at' => $d->last_active_at?->format('M d, Y H:i'),
                    ])->toArray(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
    }

    /**
     * Toggle the platform staff flag.
     */
    public function togglePlatformAdmin($id)
    {
        try {
            $user = User::findOrFail($id);

            if ($user->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify super_admin platform flag.',
                ], 403);
            }

            $user->is_platform_admin = !$user->is_platform_admin;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => $user->is_platform_admin
                    ? 'User marked as platform staff.'
                    : 'Platform staff flag removed.',
                'is_platform_admin' => $user->is_platform_admin,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update flag'], 500);
        }
    }

    /**
     * Clear lockout state.
     */
    public function unlockUser($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->forceFill([
                'locked_until' => null,
                'failed_login_attempts' => 0,
            ])->save();

            return response()->json(['success' => true, 'message' => 'User unlocked successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to unlock user'], 500);
        }
    }

    /**
     * Get all roles for assignment.
     */
    public function getRoles()
    {
        $roles = Role::orderBy('name')->get(['id', 'name']);
        return response()->json($roles);
    }

    /**
     * Get all permissions for assignment.
     */
    public function getPermissions()
    {
        $permissions = Permission::orderBy('name')->get(['id', 'name']);
        return response()->json($permissions);
    }

    /**
     * Get user's direct permissions.
     */
    public function getUserPermissions($id)
    {
        try {
            $user = User::findOrFail($id);
            $directPermissions = $user->getDirectPermissions()->pluck('name')->toArray();
            $roles = $user->roles->pluck('name')->toArray();
            $allPermissions = Permission::orderBy('name')->get(['id', 'name']);
            
            return response()->json([
                'success' => true,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'direct_permissions' => $directPermissions,
                'role_permissions' => $user->getPermissionsViaRoles()->pluck('name')->toArray(),
                'roles' => $roles,
                'all_permissions' => $allPermissions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }

    /**
     * Assign direct permission to user.
     */
    public function assignPermission(Request $request, $id)
    {
        $request->validate([
            'permission' => 'required|string|exists:permissions,name'
        ]);

        try {
            $user = User::findOrFail($id);
            
            // Check if trying to modify super_admin
            if ($user->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify permissions of a Super Admin user'
                ], 403);
            }
            
            $user->givePermissionTo($request->permission);
            
            return response()->json([
                'success' => true,
                'message' => 'Permission assigned successfully',
                'permission' => $request->permission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke direct permission from user.
     */
    public function revokePermission(Request $request, $id)
    {
        $request->validate([
            'permission' => 'required|string|exists:permissions,name'
        ]);

        try {
            $user = User::findOrFail($id);
            
            // Check if trying to modify super_admin
            if ($user->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify permissions of a Super Admin user'
                ], 403);
            }
            
            $user->revokePermissionTo($request->permission);
            
            return response()->json([
                'success' => true,
                'message' => 'Permission revoked successfully',
                'permission' => $request->permission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new user.
     */
    public function storeUser(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
        ]);

        try {
            $user = User::create([
                'uuid' => (string) Str::uuid(),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'country_code' => $request->country_code ?? '+1',
                'password' => Hash::make($request->password),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            
            // Assign role
            $user->assignRole($request->role);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at->format('M d, Y'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user details for editing.
     */
    public function getUser($id)
    {
        try {
            $user = User::with('roles')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country_code' => $user->country_code,
                'role' => $user->roles->first()->name ?? null,
                'is_active' => $user->is_active,
                'is_super_admin' => $user->hasRole('super_admin'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }

    /**
     * Update a user.
     */
    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $id,
            'role' => 'required|exists:roles,name',
        ]);

        try {
            $user = User::findOrFail($id);
            
            // Check if trying to modify super_admin
            if ($user->hasRole('super_admin') && !auth()->user()->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot modify a Super Admin user'
                ], 403);
            }
            
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->name = $request->first_name . ' ' . $request->last_name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->country_code = $request->country_code ?? $user->country_code;
            
            // Update password if provided
            if ($request->filled('password')) {
                $request->validate(['password' => 'min:8|confirmed']);
                $user->password = Hash::make($request->password);
            }
            
            $user->save();
            
            // Sync role (remove all current roles and assign new one)
            $user->syncRoles([$request->role]);
            
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at->format('M d, Y'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle user status (activate/deactivate).
     */
    public function toggleUserStatus($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent deactivating super_admin
            if ($user->hasRole('super_admin') && !auth()->user()->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot deactivate a Super Admin user'
                ], 403);
            }
            
            $user->is_active = !$user->is_active;
            $user->save();
            
            $status = $user->is_active ? 'activated' : 'deactivated';
            
            return response()->json([
                'success' => true,
                'message' => "User {$status} successfully",
                'is_active' => $user->is_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle user status'
            ], 500);
        }
    }

    /**
     * Delete a user.
     */
    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent deleting super_admin or yourself
            if ($user->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a Super Admin user'
                ], 403);
            }
            
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ], 403);
            }
            
            $user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }
}