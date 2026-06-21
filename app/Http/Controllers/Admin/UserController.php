<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index', [
            'users' => User::with('restaurant')->latest()->paginate(50),
            'restaurants' => Restaurant::withCount('users')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:6'],
            'role' => ['required', 'in:admin,restaurant_owner,staff'],
            'restaurant_id' => [
                'nullable',
                'required_unless:role,admin',
                'prohibited_if:role,admin',
                'exists:restaurants,id',
                Rule::unique('users', 'restaurant_id')->whereNotNull('restaurant_id'),
            ],
        ]);
        $data['password'] = Hash::make($data['password']);
        User::create($data);
        return back()->with('success', 'User created.');
    }

    public function destroy(Request $request, User $user)
    {
        // Prevent admin from deleting themselves
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $userName = $user->name;
        $user->delete();

        return back()->with('success', 'User "' . $userName . '" deleted.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'in:admin,restaurant_owner,staff'],
            'restaurant_id' => [
                'nullable',
                'required_unless:role,admin',
                'prohibited_if:role,admin',
                'exists:restaurants,id',
                Rule::unique('users', 'restaurant_id')->whereNotNull('restaurant_id')->ignore($user->id),
            ],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        return back()->with('success', 'User updated.');
    }
}
