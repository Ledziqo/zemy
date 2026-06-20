const fs = require('fs');

let c = fs.readFileSync('app/Http/Controllers/Admin/UserController.php', 'utf8');

// Add email and password to the update validation and logic
c = c.replace(
    `    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:admin,restaurant_owner,staff'],
            'restaurant_id' => [
                'nullable',
                'required_unless:role,admin',
                'prohibited_if:role,admin',
                'exists:restaurants,id',
                Rule::unique('users', 'restaurant_id')->whereNotNull('restaurant_id')->ignore($user->id),
            ],
        ]);
        $user->update($data);
        return back()->with('success', 'User updated.');
    }`,
    `    public function update(Request $request, User $user)
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
    }`
);

fs.writeFileSync('app/Http/Controllers/Admin/UserController.php', c);
console.log('UserController updated with email/password handling');