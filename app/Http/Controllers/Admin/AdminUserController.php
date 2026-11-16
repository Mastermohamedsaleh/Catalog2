<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminUserController extends Controller
{
    public function index()
    {
        $admins = User::where('role', 'catalog_master_root')
                  ->orWhere('is_super', true)
                  ->latest()
                  ->paginate(10);
        return view('admin.admins.index', compact('admins'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_super' => ['nullable', 'boolean'],
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'catalog_master_root',
            'is_super' => $request->has('is_super'), // تحديد لو هو سوبر أدمن أو أدمن عادي
        ]);

        return redirect()->route('admin.admins.index')
                         ->with('success', 'Admin created successfully!');
    }

    /** This controller only ever touches admin accounts (never merchants). */
    private function ensureAdmin(User $admin): void
    {
        abort_unless($admin->role === 'catalog_master_root' || $admin->is_super, 404);
    }

    public function update(Request $request, User $admin)
    {
        $this->ensureAdmin($admin);

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $admin->id],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_super' => ['nullable', 'boolean'],
        ]);

        $admin->name = $request->name;
        $admin->email = $request->email;
        
        // منع السوبر أدمن الأساسي من إزالة صلاحية السوبر عن نفسه خطأً
        if ($admin->id !== auth()->id()) {
            $wantsSuper = $request->has('is_super');
            if (! $wantsSuper && $admin->is_super && User::where('is_super', true)->count() <= 1) {
                return back()->with('error', 'لا يمكن إزالة صلاحية آخر سوبر أدمن في النظام.');
            }
            $admin->is_super = $wantsSuper;
        }

        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
        }

        $admin->save();

        return redirect()->route('admin.admins.index')
                         ->with('success', 'Admin updated successfully!');
    }

    public function destroy(User $admin)
    {
        $this->ensureAdmin($admin);

        if ($admin->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own active account!');
        }

        if ($admin->is_super && User::where('is_super', true)->count() <= 1) {
            return back()->with('error', 'لا يمكن حذف آخر سوبر أدمن في النظام.');
        }

        $admin->delete();

        return redirect()->route('admin.admins.index')
                         ->with('success', 'Admin deleted successfully!');
    }
}