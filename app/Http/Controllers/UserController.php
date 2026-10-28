<?php

namespace App\Http\Controllers;

use App\Domain\UserAdminService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);
        return view('users.index', ['users' => User::with('roles')->orderByRaw('active desc')->orderBy('name')->get()]);
    }

    public function update(Request $request, User $user, UserAdminService $admin)
    {
        $this->authorize('update', $user);
        $d = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'initials' => ['nullable', 'string', 'max:4', 'regex:/^[A-Za-z]{2,4}$/', Rule::unique('users', 'initials')->ignore($user->id)],
            'roles' => ['array'], 'roles.*' => [Rule::in(User::ROLES)],
            'sales_target' => ['nullable', 'numeric', 'min:0'], 'commission_pct' => ['nullable', 'numeric', 'between:0,100'],
            'active' => ['nullable', 'boolean'], 'gets_leads' => ['nullable', 'boolean'],
        ]);
        $admin->update($user, $d + ['active' => $request->boolean('active'), 'gets_leads' => $request->boolean('gets_leads')], $request->user());
        return $this->ok('common.saved');
    }

    public function locale(Request $request)
    {
        $l = $request->input('locale') === 'en' ? 'en' : 'ar';
        $request->session()->put('locale', $l);
        if ($u = $request->user()) {
            $u->update(['locale' => $l]);
        }
        return back();
    }
}
