# OrvosIdopontJWT REST API — Dokumentáció

Az OrvosIdopontJWT egy Laravel-alapú REST API alkalmazás, amely orvosi páciensek, orvosok és időpontok kezelésére szolgál JWT (JSON Web Token) alapú autentikációval.

---

## Általános

- Base URL: `http://127.0.0.1:8000/api`
- Adatbázis neve: orvos_idopont_jwt
- Auth: JWT Bearer token (tymon/jwt-auth). A token a `/login` végponttal szerezhető be.
- Hibák:
  - 400 Bad Request — rossz kérés
  - 401 Unauthorized — hiányzó/érvénytelen token
  - 403 Forbidden — nincs jogosultság
  - 404 Not Found — nem található erőforrás
  - 500+ — szerverhiba

---

## Adatmodell

### User (Felhasználó)
- `id`: Elsődleges kulcs  
- `name`: Felhasználó teljes neve  
- `email`: E-mail cím (egyedi)  
- `password`: Hash-elt jelszó
- `role`: Felhasználó jogosultsága (user/admin)
- `remember_token`: Session / remember token *(nullable)*  
- `created_at`, `updated_at`, `deleted_at`: Időbélyegek

---

### Patient (Páciens)
- `id`: Elsődleges kulcs  
- `name`: Páciens neve
- `email`: Páciens email címe
- `birth_date`: Születési dátum *(nullable)*  
- `created_at`, `updated_at`, `deleted_at`: Időbélyegek  

---

### Doctor (Orvos)
- `id`: Elsődleges kulcs   
- `name`: Orvos neve  
- `specialization`: Szakvizsga / specializáció *(nullable)*
- `room`: Szoba megnevezése
- `created_at`, `updated_at`, `deleted_at`: Időbélyegek  

---

### Appointment (Időpont)
- `id`: Elsődleges kulcs  
- `patient_id`: Foglaláshoz tartozó páciens *(FK)*  
- `doctor_id`: Kapcsolódó orvos *(FK)*
- `appointment_time`: Időpont
- `status`: Státusz (pl. `pending`, `approved`, `cancelled`)  
- `created_at`, `updated_at`, `deleted_at`: Időbélyegek  


### Adatbázis struktúra
```

+--------------------------+    +-----------------------+   +-----------------------+    +-----------------------+
|   personal_access_tokens |    |         users         |   |        patients       |    |        doctors        |
+--------------------------+    +-----------------------+   +-----------------------+    +-----------------------+
| id (PK)                  |    | id (PK)               |   | id (PK)               |    | id (PK)               |
| tokenable_id (FK)        |    | name                  |   | name                  |    | name                  |
| tokenable_type           |    | email (unique)        |   | email                 |    | specialization        |
| name                     |    | password              |   | birth_date            |    | room                  |
| token (unique)           |    | role                  |   | created_at            |    | created_at            |
| abilities                |    | remember_token        |   | updated_at            |    | updated_at            |
| last_used_at             |    | created_at            |   | deleted_at            |    | deleted_at            |
| created_at               |    | updated_at            |   +-----------------------+    +-----------------------+
| updated_at               |    | deleted_at            |
+--------------------------+   +-----------------------+
                                  


                         +-------------------------------------------+
                         |               appointments                |
                         +-------------------------------------------+
                         | id (PK)                                   |
                         | patient_id (FK → patients.id)             |
                         | doctor_id (FK → doctors.id)               |
                         | appointment_time                          |
                         | status ('pending','approved','cancelled') |
                         | created_at                                |
                         | updated_at                                |
                         | deleted_at                                |
                         +-------------------------------------------+
                                   ^                         ^
                                   |                         |
                                 1..N                      1..N
                                   |                         |
                              patients                    doctors

  
```

Minden modellnél és migrációnál soft delete alkalmazva, csak kitöröltnek látszik az adat, valójában nem az.

Példa:

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;
}

```

<img width="1024" height="256" alt="531722403-c5cc2894-47f3-47b4-ab31-6edc6f0be9e9" src="https://github.com/user-attachments/assets/1d0ceae3-1bed-4d7f-a81b-793690bdf6ee" />



## Nem védett végpontok

- GET `/hello` — teszt: visszaad egy JSON üzenetet
- POST `/register` — felhasználó regisztráció
- POST `/login` — bejelentkezés, visszaadja a JWT tokent

```php

// 🔓 PUBLIC
Route::get('/hello', function () {
    return response()->json(['message' => 'Hello API']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

```


Példa /login kérésre:

Fejlécek:
- Content-Type: application/json
- Accept: application/json

```
Body:
{
  "email": "liliane47@example.com",
  "password": "password"
}
Példa válasz:
{
    "token": "3|CxgDpQXEol85wrdwlgoVJbhZ2mJGEVENZd7c48C2a54f3084"
}
```
---

## Védett végpontok (auth:sanctum)

Fejlécek:
- Authorization: Bearer {token}
- Accept: application/json
- Authorization: Bearer {JWT_TOKEN}

Általános jogosultságok:
- admin: minden erőforrást lát/kezel
- user: csak a saját rekordjaihoz fér hozzá (patients/appointments), nem hozhat létre orvost/egyéb admin műveleteket

```php

// 🔐 JWT PROTECTED
Route::middleware('auth:api')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // 👤 PATIENTS
    Route::get('/patients', [PatientController::class, 'index']);
    Route::get('/patients/{id}', [PatientController::class, 'show']);
    Route::post('/patients', [PatientController::class, 'store']);
    Route::put('/patients/{id}', [PatientController::class, 'update']);
    Route::delete('/patients/{id}', [PatientController::class, 'destroy']);

    // 👨‍⚕️ DOCTORS
    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::get('/doctors/{id}', [DoctorController::class, 'show']);
    Route::post('/doctors', [DoctorController::class, 'store']);
    Route::put('/doctors/{id}', [DoctorController::class, 'update']);
    Route::delete('/doctors/{id}', [DoctorController::class, 'destroy']);

    // 📅 APPOINTMENTS
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::patch('/appointments/{appointment}', [AppointmentController::class, 'update']);
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy']);

    Route::middleware('auth:api')->post('/logout', [AuthController::class, 'logout']);

```

---

## Patients (páciensek)

<img width="271" height="142" alt="image" src="https://github.com/user-attachments/assets/32f1ea7a-bb1c-430d-8880-ab186eded42a" />


GET `/patients` — lista:

Fejlécek:
- Authorization: Bearer {token}
- Accept: application/json
- Authorization: Bearer {JWT_TOKEN}

- admin: mindenkit lát
- user: csak saját rekordjait (feltételezve user.id = patient.id)

GET `/patients/{id}`:

Fejlécek:
- Authorization: Bearer {token}
- Accept: application/json
- Authorization: Bearer {JWT_TOKEN}

Részletek a páciensről (403, ha nincs jogosultság)

POST `/patients`:

Fejlécek:
- Authorization: Bearer {token}
- Accept: application/json
- Authorization: Bearer {JWT_TOKEN}

Létrehozás (csak admin tud létrehozni)

Body (példa):
```
{
  "name": "Norbert Kovács",
  "email": "norbert@example.com",
  "birth_date": "2005-01-01"
}
```
Válasz: 201 Created + patient objektum
```
{
    "name": "Norbert Kovács",
    "email": "norbert@example.com",
    "birth_date": "2005-01-01",
    "updated_at": "2025-12-04T09:50:41.000000Z",
    "created_at": "2025-12-04T09:50:41.000000Z",
    "id": 11
}
```

PUT `/patients/{id}`:

Fejlécek:
- Authorization: Bearer {token}
- Accept: application/json
- Authorization: Bearer {JWT_TOKEN}

Teljes frissítés (csak admin)

```
{
  "name": "Norbert Kovács Updated",
  "email": "norbert_new@example.com"
}
```

DELETE `/patients/{id}`

Fejlécek:
- Authorization: Bearer {token}
- Accept: application/json
- Authorization: Bearer {JWT_TOKEN}

Törlés (csak admin)

---

## Doctors (orvosok)

<img width="268" height="135" alt="image" src="https://github.com/user-attachments/assets/f0380069-118a-46c9-acc1-1c99378feb60" />



GET `/doctors`:

Fejlécek:
- Authorization: Bearer {token}
- Accept: application/json
- Authorization: Bearer {JWT_TOKEN}

Orvosok listája (minden user láthatja).

GET `/doctors/{id}`:

Fejlécek:
- Authorization: Bearer {token}
- Accept: application/json
- Authorization: Bearer {JWT_TOKEN}

Részletek az adott orvosról.

POST `/doctors`

Fejlécek:
- Authorization: Bearer {token}
- Accept: application/json
- Authorization: Bearer {JWT_TOKEN}

Létrehozás (csak admin).

Body:
```
{
  "name": "Dr. Név",
  "specialization": "szakterület",
  "room": "101"
}
```

PUT `/doctors/{id}`:

Fejlécek:
- Authorization: Bearer {token}
- Accept: application/json
- Authorization: Bearer {JWT_TOKEN}

Módosítás (csak admin)

DELETE `/doctors/{id}`:

Fejlécek:
- Authorization: Bearer {token}
- Accept: application/json
- Authorization: Bearer {JWT_TOKEN}

Törlés (csak admin)

---

## Appointments (időpontok)

<img width="288" height="141" alt="image" src="https://github.com/user-attachments/assets/5ee0e6a6-ca86-40c2-af9e-50e624b5d047" />



GET `/appointments`:

Fejlécek:
- Authorization: Bearer {token}
- Accept: application/json
- Authorization: Bearer {JWT_TOKEN}

Információ:
- admin: minden időpont
- user: csak sajátjai (appointment.patient_id === user.id)

GET `/appointments/{id}: 

Fejlécek:
- Authorization: Bearer {token}
- Accept: application/json
- Authorization: Bearer {JWT_TOKEN}

Részletek az időpontokról(403, ha nem jogosult)

POST `/appointments`:

Fejlécek:
- Authorization: Bearer {token}
- Accept: application/json
- Authorization: Bearer {JWT_TOKEN}


Létrehozás (jelen implementáció: csak admin hozhat létre)

Body:
```
{
  "patient_id": 1,
  "doctor_id": 2,
  "appointment_time": "2025-12-20 10:00:00",
  "status": "scheduled"
}
```
Válasz: 201 Created + appointment objektum

PUT `/appointments/{id}`:

Fejlécek:
- Authorization: Bearer {token}
- Accept: application/json
- Authorization: Bearer {JWT_TOKEN}


Teljes frissítés (admin vagy a saját patient-je)


DELETE `/appointments/{id}`:

Fejlécek:
- Authorization: Bearer {token}
- Accept: application/json
- Authorization: Bearer {JWT_TOKEN}

Törlés (admin vagy a saját patient-je)

---

## Példa hibaválasz (érvénytelen token)
```
Response: 401 Unauthorized
{
  "message": "Invalid token"
}
```

### Hitelesítés és Jogosultságok 

### JWT Token-alapú Autentifikáció
- Minden hitelesített végpont `Authorization: Bearer {JWT token}` header-t igényel
- A token bejelentkezéskor jön vissza
- A tokeneket a `personal_access_tokens` táblában tároljuk

### Szerepek

1. **Normál felhasználó** (`role = user`)
   - Saját profil megtekintése és módosítása
   - Erőforrások megtekintése
   - Saját foglalások létrehozása, olvasása és törlése (CRUD részben)
   - Foglalások státusza nem módosítható

2. **Adminisztrátor** (`role = admin`)
   - Összes felhasználó kezelése
   - Erőforrások teljes kezelése (pl. páciensek, orvosok, időpontok)
   - Összes foglalás megtekintése és kezelése
   - Foglalás státuszának módosítása


---

## Factory, Controller, Seedelés és Tesztelés

- Factories és seederek használata: database/seeders/DatabaseSeeder.php és factories mappában.
- Futtatás helyben: php artisan migrate:fresh --seed majd php artisan test
- A feature tesztek API hívásokat imitálnak.
-JWT alapú autentikáció esetén a tesztek többsége Bearer tokennel történik:

### Factory-k:

**-AppointmentFactory.php**

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition()
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'appointment_time' => $this->faker->dateTimeBetween('+1 days', '+1 month'),
            'status' => $this->faker->randomElement(['pending', 'approved', 'cancelled']),
        ];
    }
}
?>

```
Az AppointmentFactory automatikusan hoz létre teszteléshez időpontfoglalásokat, minden rekordhoz új pácienst és orvost generálva, valamint véletlenszerű jövőbeli időpontot és státuszt (pending, approved, cancelled) rendel hozzá.

**-DoctorFactory.php**

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Doctor;

class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'specialization' => $this->faker->randomElement(['Cardiology', 'Dermatology', 'Pediatrics']),
            'room' => $this->faker->numberBetween(101, 305),
        ];
    }
}

?>
```
A DoctorFactory automatikusan létrehoz orvosokat teszteléshez vagy seedeléshez, véletlenszerű nevet, szakterületet és szobaszámot rendel minden új rekordhoz.

**-PatientFactory.php**

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Patient;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'birth_date' => $this->faker->date(), // véletlenszerű születési dátum
        ];
    }
}
?>
```
A PatientFactory automatikusan létrehoz pácienseket teszteléshez vagy seedeléshez, véletlenszerű nevet, egyedi e-mail címet és születési dátumot generálva minden új rekordhoz.

**-UserFactory.php**

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'), // egyszerűség kedvéért
            'role' => 'user',
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => 'admin',
            'email' => 'admin@email.hu'
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}


```
A UserFactory automatikusan hoz létre felhasználókat teszteléshez vagy seedeléshez alapértelmezetten user szerepkörrel, egységes jelszóval (password), valamint tartalmaz egy admin() állapotot admin jogosultságú felhasználók létrehozásához.


## Controllerek

**-AuthController.php**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // 🟢 REGISZTRÁCIÓ
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user'
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Registered successfully',
            'token' => $token,
            'user' => $user
        ], 201);
    }

    // 🔵 LOGIN
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'token' => $token,
            'user' => JWTAuth::user()
        ]);
    }


    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

}
?>

```

Az AuthController kezeli a felhasználók regisztrációját, bejelentkezését és kijelentkezését.

**-DoctorController.php**

```php
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

```
A DoctorController kezeli az orvosok adatait az API-n keresztül. Bárki lekérdezheti az orvosok listáját vagy egy konkrét orvos adatait, de új orvos létrehozása, módosítása vagy törlése csak admin jogosultsággal lehetséges

**-PatientController.php**

```php

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


```

A PatientController kezeli a páciensek adatait; az admin minden rekordhoz hozzáfér, míg a normál felhasználók kizárólag a saját felhasználói fiókjukhoz (user_id) tartozó páciens rekordot érhetik el és módosíthatják.

**-AppointmentController.php**

```php

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




```

Az AppointmentController kezeli az időpontok CRUD műveleteit; az admin minden időpontot kezelhet, míg a normál felhasználók kizárólag a saját pácienseikhez tartozó időpontokat láthatják, hozhatják létre, módosíthatják vagy törölhetik, a státusz módosítása pedig kizárólag admin jogosultsággal lehetséges.

## Modellek

**-Appointment.php**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Testing\Fluent\Concerns\Has;

class Appointment extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_time',
        'status'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
?>

```
Az Appointment (Időpont) modell az időpontfoglalásokat reprezentálja az alkalmazásban.
Támogatja a factory-ket (teszteléshez/seedeléshez), a soft delete-et (törléskor nem törli végleg az adatot), és kapcsolatban áll egy Patient-tel és egy Doctor-ral (belongsTo), miközben csak a megadott mezők tölthetők tömegesen ($fillable).

**-Doctor.php**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'name',
        'specialization',
        'room',
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}

?>

```

A Doctor (Orvos) modell az orvosokat kezeli az alkalmazásban.
Támogatja a factory-ket (teszteléshez/seedeléshez), a soft delete-et (logikai törlés), és csak a megadott mezők (name, specialization, room) tölthetők fel tömegesen ($fillable).

**-Patient.php**

```php

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'name',
        'email',
        'birth_date'
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}

?>

```

A Patient (Páciens) modell a páciensek adatainak kezeléséért felel.
Támogatja a factory-ket (tesztelés/seedelés), a soft delete-et (logikai törlés), és csak a megadott mezők (name, email, birth_date) tölthetők fel tömegesen ($fillable).

**-User.php**

```php

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role
        ];
    }

    // OPTIONAL kapcsolatok
    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }
}

?>

```
A User (Felhasználó) modell kezeli az alkalmazás felhasználóit és az autentikációt.
Támogatja a JWT alapú tokenes autentikációt (tymon/jwt-auth), a factory-ket, az értesítéseket, valamint a soft delete-et; a jelszó rejtett és automatikusan hash-elve kerül mentésre.

## Seedelés:

A **DatabaseSeeder** az adatbázis feltöltéséért felel tesztelési és fejlesztési környezetben.
Létrehoz egy admin felhasználót fix e-mail címmel, 10 darab normál felhasználót, egy páciens rekordot és egy orvos rekordot.
Ezután 5 időpontot generál, amelyek mind ugyanahhoz a pácienshez és orvoshoz tartoznak, biztosítva az adatok közötti kapcsolatokat.


```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;


class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 🟢 Admin user
        User::firstOrCreate(
            ['email' => 'admin@email.hu'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => 'admin'
            ]
        );


        // 🔵 Normál userek
        User::factory(10)->create();
        $patient = Patient::factory()->create();
        $doctor = Doctor::factory()->create();

        // 🟣 Appointmentek
        Appointment::factory()->count(5)->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id
        ]);

    }
}

```

**Seedelés futtatása**: `php artisan db:seed`

## Tesztelés

<img width="647" height="132" alt="image" src="https://github.com/user-attachments/assets/28ab8f3b-61fd-4d49-ac75-87715e4e9cf8" />


-AppointmentTest.php

1. admin_can_create_appointment() - Egy admin felhasználó sikeresen létre tud-e hozni egy új időpontot.
2. normal_user_cannot_create_appointment() - Egy sima (nem admin) felhasználó nem hozhat létre időpontot.
3. can_get_appointments_list() - Egy admin le tudja-e kérni az összes időpontot az API-n keresztül.
4. admin_can_update_appointment() - Az admin módosíthatja egy létező időpont adatait.
5. admin_can_delete_appointment() - Az admin jogosult-e időpontot törölni.

-DoctorTest.php

1. admin_can_create_doctor() - Egy admin felhasználó képes-e új orvost létrehozni az API-n keresztül.
2. normal_user_cannot_create_doctor() - Egy normál (nem admin) felhasználó ne tudjon új orvost létrehozni.
3. can_get_doctors_list() - Egy admin le tudja-e kérni az összes orvost az API-n keresztül.

-PatientTest.php

1. admin_can_create_patient() - Egy admin felhasználó képes-e új pácienst létrehozni az API-n keresztül.
2. normal_user_cannot_create_patient() - Egy normál (nem admin) felhasználó ne hozhasson létre új pácienst.
3. can_get_patients_list() - Egy admin le tudja-e kérni az összes pácienst az API-ból.

11 (+1 próbateszt) tesztet tartalmaz, melyek közül mind sikerrel lefut.

**Tesztek futtatása**: `php artisan test`






