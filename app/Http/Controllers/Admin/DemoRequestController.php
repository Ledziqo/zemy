<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use Illuminate\Http\Request;

class DemoRequestController extends Controller
{
    public function index()
    {
        return view('admin.demo_requests.index', ['requests' => DemoRequest::latest()->paginate(50)]);
    }

    public function update(Request $request, DemoRequest $demoRequest)
    {
        $demoRequest->update($request->validate(['status' => ['required', 'in:new,contacted,converted,closed']]));
        return back()->with('success', 'Demo request updated.');
    }
}
