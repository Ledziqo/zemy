<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\EmailValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index', [
            'users' => User::with('restaurant')->orderBy('name')->get(),
            'restaurants' => Restaurant::with(['users', 'staffProfiles'])->withCount(['users', 'staffProfiles'])->orderBy('name')->get(),
            'platformUsers' => User::whereNull('restaurant_id')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => array_merge(EmailValidation::rules(), ['unique:users,email']),
            'password' => ['required', 'min:6'],
            'role' => ['required', 'in:admin,restaurant_owner,staff'],
            'restaurant_id' => [
                'nullable',
                'required_unless:role,admin',
                'prohibited_if:role,admin',
                'exists:restaurants,id',
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
            'email' => array_merge(EmailValidation::rules(), [Rule::unique('users', 'email')->ignore($user->id)]),
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'in:admin,restaurant_owner,staff'],
            'restaurant_id' => [
                'nullable',
                'required_unless:role,admin',
                'prohibited_if:role,admin',
                'exists:restaurants,id',
            ],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        DB::transaction(function () use ($user, $data) {
            $user->update($data);

            // The restaurant account form displays the restaurant email, while
            // authentication uses the owner User record. Keep them aligned
            // when an administrator edits the owner directly.
            if ($user->role === 'restaurant_owner' && $user->restaurant) {
                $user->restaurant->update(['email' => $user->email]);
            }
        });

        return back()->with('success', 'User updated.');
    }
}
