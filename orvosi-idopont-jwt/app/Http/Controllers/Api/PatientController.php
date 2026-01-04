<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;

class PatientController extends Controller
{
    /**
     * ADMIN: minden páciens
     * USER: csak a saját páciens rekordja
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return Patient::all();
        }

        return Patient::where('user_id', $user->id)->get();
    }

    /**
     * ADMIN: bárkit létrehozhat
     * USER: csak saját magának
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'phone' => 'required|string|max:20',
            'user_id' => 'sometimes|exists:users,id'
        ]);

        // user nem adhat meg más user_id-t
        if ($user->role !== 'admin') {
            $data['user_id'] = $user->id;
        } elseif (!isset($data['user_id'])) {
            // admin: ha nincs user_id megadva, saját maga
            $data['user_id'] = $user->id;
        }

        return response()->json(
            Patient::create($data),
            201
        );
    }

    /**
     * ADMIN: bárkit
     * USER: csak a sajátját
     */
    public function show($id)
    {
        $patient = Patient::findOrFail($id);

        $user = Auth::user();
        if ($user->role !== 'admin' && $patient->user_id !== $user->id) {
            abort(403, 'Forbidden');
        }

        return $patient;
    }

    /**
     * ADMIN: bárkit
     * USER: csak a sajátját
     */
    public function update(Request $request, $id)
    {
        $patient = Patient::findOrFail($id);

        $user = Auth::user();
        if ($user->role !== 'admin' && $patient->user_id !== $user->id) {
            abort(403, 'Forbidden');
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'birth_date' => 'sometimes|date',
            'phone' => 'sometimes|string|max:20'
        ]);

        $patient->update($data);

        return response()->json($patient);
    }

    /**
     * ADMIN: törölhet
     * USER: NEM
     */
    public function destroy($id)
    {
        $this->adminOnly();

        Patient::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Patient deleted successfully'
        ]);
    }

    /**
     * ====== SEGÉD METÓDUSOK ======
     */

    private function adminOnly(): void
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            abort(403, 'Admin only');
        }
    }
}
