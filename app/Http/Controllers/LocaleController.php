<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(['en', 'am'])],
        ]);

        return back()->withCookie(cookie('zemtab_locale', $data['locale'], 60 * 24 * 365, null, null, null, true, false, 'lax'));
    }
}
