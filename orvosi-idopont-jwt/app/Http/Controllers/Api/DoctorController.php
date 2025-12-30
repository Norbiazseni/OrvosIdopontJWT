<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Doctor;

class DoctorController extends Controller
{
    // 🔵 ÖSSZES DOKTOR
    public function index()
    {
        return Doctor::all();
    }

    // 🔵 EGY DOKTOR
    public function show($id)
    {
        return Doctor::findOrFail($id);
    }

    // 🟢 ÚJ DOKTOR
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'room' => 'required|string|max:50',
        ]);

        $doctor = Doctor::create($data);

        return response()->json($doctor, 201);
    }

    // ✏️ MÓDOSÍTÁS
    public function update(Request $request, $id)
    {
        $doctor = Doctor::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'specialization' => 'sometimes|string|max:255',
            'room' => 'sometimes|string|max:50',
        ]);

        $doctor->update($data);

        return response()->json($doctor);
    }

    // 🗑 TÖRLÉS
    public function destroy($id)
    {
        $doctor = Doctor::findOrFail($id);
        $doctor->delete();

        return response()->json([
            'message' => 'Doctor deleted successfully'
        ]);
    }
}
?>