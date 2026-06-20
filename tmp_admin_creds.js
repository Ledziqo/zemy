const fs = require('fs');

// 1. Update admin credentials in seeder
let seeder = fs.readFileSync('database/seeders/DatabaseSeeder.php', 'utf8');
seeder = seeder.replace(
    "['email' => 'admin@zemtab.test'],",
    "['email' => 'Aesliexx@gmail.com'],"
);
seeder = seeder.replace(
    "'name' => 'ZemTab Admin', 'password' => Hash::make('password'), 'role' => 'admin'",
    "'name' => 'ZemTab Admin', 'password' => Hash::make('Mudi2005'), 'role' => 'admin'"
);
fs.writeFileSync('database/seeders/DatabaseSeeder.php', seeder);
console.log('Seeder updated with new admin credentials');

// 2. Remove setup link from login page
let login = fs.readFileSync('resources/views/auth/login.blade.php', 'utf8');
login = login.replace(
    '\n        <a href="{{ route(\'setup.show\') }}" class="mt-6 text-sm text-zem-muted hover:text-zem-gold">Setup Database</a>\n',
    '\n'
);
fs.writeFileSync('resources/views/auth/login.blade.php', login);
console.log('Setup link removed from login page');

// 3. Add email and password fields to Users edit form
let users = fs.readFileSync('resources/views/admin/users/index.blade.php', 'utf8');
users = users.replace(
    `<input name="name" value="{{ $user->name }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <select name="role" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">`,
    `<input name="name" value="{{ $user->name }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <input name="email" type="email" value="{{ $user->email }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <input name="password" type="password" placeholder="New password (leave blank to keep)" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <select name="role" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">`
);
fs.writeFileSync('resources/views/admin/users/index.blade.php', users);
console.log('Users edit form updated with email/password fields');