<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    // 🔴 ADMIN – összes időpont
    public function index()
    {
        $this->adminOnly();

        return response()->json(
            Appointment::with(['patient', 'doctor'])->get()
        );
    }

    // 🟢 USER – saját időpontok
    public function myAppointments()
    {
        return response()->json(
            Appointment::where('patient_id', Auth::id())
                ->with('doctor')
                ->get()
        );
    }

    // 🟢 USER – új időpont
    public function store(Request $request)
    {
        $request->validate([
            'doctor_id'        => 'required|exists:doctors,id',
            'appointment_time' => 'required|date'
        ]);

        $appointment = Appointment::create([
            'patient_id'       => Auth::id(),
            'doctor_id'        => $request->doctor_id,
            'appointment_time' => $request->appointment_time,
            'status'           => 'pending'
        ]);

        return response()->json($appointment, 201);
    }

    // 🔴 ADMIN – státusz módosítás
    public function updateStatus(Request $request, $id)
    {
        $this->adminOnly();

        $request->validate([
            'status' => 'required|in:pending,approved,cancelled'
        ]);

        $appointment = Appointment::findOrFail($id);
        $appointment->update([
            'status' => $request->status
        ]);

        return response()->json($appointment);
    }

    // 🟢 USER / 🔴 ADMIN – törlés
    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);
        $user = Auth::user();

        if (
            $user->role !== 'admin' &&
            $appointment->patient_id !== $user->id
        ) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $appointment->delete();

        return response()->json(['message' => 'Deleted']);
    }

    // 🔐 ADMIN CHECK
    private function adminOnly(): void
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            abort(403, 'Admin only');
        }
    }
}
