<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    // Protected roles that cannot be modified or deleted
    protected $protectedRoles = ['super_admin'];
    
    /**
     * Display roles list.
     */
    public function index()
    {
        return view('admin.roles');
    }

    /**
     * Get all roles with their permissions and user counts.
     */
    public function getRoles(Request $request)
    {
        $search = $request->get('search', '');
        
        $query = Role::with('permissions');
        
        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        
        $roles = $query->orderBy('name')->get();
        
        $data = $roles->map(function($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
                'users_count' => User::role($role->name)->count(),
                'created_at' => $role->created_at->format('M d, Y'),
                'is_protected' => in_array($role->name, $this->protectedRoles),
            ];
        });
        
        return response()->json($data);
    }

    /**
     * Get all available permissions.
     */
    public function getPermissions()
    {
        $permissions = Permission::orderBy('name')->get(['id', 'name']);
        return response()->json($permissions);
    }

    /**
     * Store a new role.
     */
    public function storeRole(Request $request)
    {
        $request->validate([
            'role_name' => 'required|string|unique:roles,name'
        ]);

        // Check if trying to create a protected role
        if (in_array($request->role_name, $this->protectedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create protected role: ' . $request->role_name
            ], 403);
        }

        try {
            $role = Role::create([
                'name' => $request->role_name,
                'guard_name' => 'web'
            ]);
            
            // Assign permissions if any
            if ($request->has('permissions') && is_array($request->permissions)) {
                $permissionIds = json_decode($request->permissions, true) ?? $request->permissions;
                $role->syncPermissions($permissionIds);
            }

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')->toArray(),
                    'users_count' => 0,
                    'created_at' => $role->created_at->format('M d, Y'),
                    'is_protected' => false,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get role details for editing.
     */
    public function getRole($id)
    {
        try {
            $role = Role::with('permissions')->findOrFail($id);
            
            // Check if role is protected
            if (in_array($role->name, $this->protectedRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This role is protected and cannot be edited'
                ], 403);
            }
            
            return response()->json([
                'success' => true,
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('id')->toArray(),
                'is_protected' => false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }
    }

    /**
     * Update a role.
     */
    public function updateRole(Request $request, $id)
    {
        try {
            $role = Role::findOrFail($id);
            
            // Check if role is protected
            if (in_array($role->name, $this->protectedRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The ' . $role->name . ' role is protected and cannot be modified'
                ], 403);
            }
            
            $request->validate([
                'role_name' => 'required|string|unique:roles,name,' . $id
            ]);
            
            $role->name = $request->role_name;
            $role->save();
            
            // Sync permissions
            if ($request->has('permissions')) {
                $permissionIds = json_decode($request->permissions, true) ?? $request->permissions;
                if (is_array($permissionIds)) {
                    $role->syncPermissions($permissionIds);
                }
            }
            
            // Clear permission cache
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')->toArray(),
                    'users_count' => User::role($role->name)->count(),
                    'created_at' => $role->created_at->format('M d, Y'),
                    'is_protected' => false,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a role.
     */
    public function deleteRole($id)
    {
        try {
            $role = Role::findOrFail($id);
            
            // Check if role is protected
            if (in_array($role->name, $this->protectedRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The ' . $role->name . ' role is protected and cannot be deleted'
                ], 403);
            }
            
            // Check if role has users
            $usersCount = User::role($role->name)->count();
            
            if ($usersCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete role. It is currently assigned to ' . $usersCount . ' user(s). Please reassign users first.'
                ], 400);
            }
            
            $role->delete();
            
            // Clear permission cache
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            
            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users with a specific role.
     */
    public function getRoleUsers($id)
    {
        try {
            $role = Role::findOrFail($id);
            $users = User::role($role->name)->get(['id', 'name', 'email', 'first_name', 'last_name']);
            
            return response()->json([
                'role_name' => $role->name,
                'users' => $users->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name ?? $user->first_name . ' ' . $user->last_name,
                        'email' => $user->email,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load users'
            ], 500);
        }
    }
}