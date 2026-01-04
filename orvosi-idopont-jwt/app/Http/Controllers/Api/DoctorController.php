<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;

class DoctorController extends Controller
{
    public function index()
    {
        return Doctor::all();
    }

    public function show($id)
    {
        return Doctor::findOrFail($id);
    }

    public function store(Request $request)
    {
        $this->adminOnly();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'room' => 'required|string|max:50',
        ]);

        $doctor = Doctor::create($data);

        return response()->json($doctor, 201);
    }

    public function update(Request $request, $id)
    {
        $this->adminOnly();

        $doctor = Doctor::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'specialization' => 'sometimes|string|max:255',
            'room' => 'sometimes|string|max:50',
        ]);

        $doctor->update($data);

        return response()->json($doctor);
    }

    public function destroy($id)
    {
        $this->adminOnly();

        $doctor = Doctor::findOrFail($id);
        $doctor->delete();

        return response()->json([
            'message' => 'Doctor deleted successfully'
        ]);
    }

    private function adminOnly(): void
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            abort(403, 'Admin only');
        }
    }
}
?>