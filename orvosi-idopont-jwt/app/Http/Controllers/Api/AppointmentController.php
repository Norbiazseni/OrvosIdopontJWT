<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Admin sees all appointments
        if ($user->role === 'admin') {
            return Appointment::with(['patient', 'doctor'])->get();
        }

        // Regular user sees only their appointments (via their patient record)
        $patientIds = Patient::where('user_id', $user->id)->pluck('id');
        
        return Appointment::whereIn('patient_id', $patientIds)
            ->with(['patient', 'doctor'])
            ->get();
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'doctor_id'        => 'required|exists:doctors,id',
            'appointment_time' => 'required|date',
        ]);

        // Check if user owns this patient record
        $patient = Patient::findOrFail($request->patient_id);
        
        if ($user->role !== 'admin' && $patient->user_id !== $user->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $appointment = Appointment::create([
            'patient_id'       => $request->patient_id,
            'doctor_id'        => $request->doctor_id,
            'appointment_time' => $request->appointment_time,
            'status'           => 'pending',
        ]);

        return response()->json($appointment, 201);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $appointment = Appointment::findOrFail($id);

        // Admin can update any appointment
        // Users can only update their own appointments
        if ($user->role !== 'admin') {
            $patient = $appointment->patient;
            if (!$patient || $patient->user_id !== $user->id) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
        }

        $data = $request->validate([
            'doctor_id'        => 'sometimes|exists:doctors,id',
            'appointment_time' => 'sometimes|date',
            'status'           => 'sometimes|in:pending,approved,cancelled',
        ]);

        // Only admin can change status
        if (isset($data['status']) && $user->role !== 'admin') {
            return response()->json(['error' => 'Only admin can change appointment status'], 403);
        }

        $appointment->update($data);

        return response()->json($appointment);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $appointment = Appointment::findOrFail($id);

        // Admin can delete any appointment
        // Users can only delete their own appointments
        if ($user->role !== 'admin') {
            $patient = $appointment->patient;
            if (!$patient || $patient->user_id !== $user->id) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
        }

        $appointment->delete();

        return response()->json(['message' => 'Deleted']);
    }
}

