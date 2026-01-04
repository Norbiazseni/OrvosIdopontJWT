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

+-------------------------+      +----------------------+         +----------------------+        +-----------------------+
| personal_access_tokens |       |        users         |         |       patients       |        |        doctors        |
+-------------------------+    _1| id (PK)              |         | id (PK)              |        | id (PK)               |
| id (PK)                 | K_/  | name                 |         | name                 |        | name                  |
| tokenable_id (FK)       |      | email (unique)       |         | email (nullable)     |        | specialization        |
| tokenable_type          |      | password             |         | phone (nullable)     |        | phone (nullable)      |
| name                    |      | role ('admin/user')  |         | created_at           |        | created_at            |
| token (unique)          |      | created_at           |         | updated_at           |        | updated_at            |
| abilities               |      | updated_at           |         +----------------------+        +-----------------------+
| last_used_at            |      +----------------------+
| created_at              |
+-------------------------+
                                                                   1
                                                   +-------------------------------------+
                                                   |              appointments           |
                                                   +-------------------------------------+
                                                   | id (PK)                             |
                                                   | patient_id (FK → patients.id)       |
                                                   | doctor_id (FK → doctors.id)         |
                                                   | appointment_time                    |
                                                   | status ('pending','approved',...)   |
                                                   | created_at                          |
                                                   | updated_at                          |
                                                   +-------------------------------------+
                                                     ^                               ^
                                                     |                               |
                                                     |0..N                           |0..N
                                                     |                               |
                                                   patients                        doctors
  
```

Minden modellnél és migrációnál soft delete alkalmazva, csak kitöröltnek látszik az adat, valójában nem az.

Példa:

```
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;
}

```

<img width="1024" height="256" alt="image" src="https://github.com/user-attachments/assets/c5cc2894-47f3-47b4-ab31-6edc6f0be9e9" />



## Nem védett végpontok

- GET `/hello` — teszt: visszaad egy JSON üzenetet
- POST `/register` — felhasználó regisztráció
- POST `/login` — bejelentkezés, visszaadja a Bearer tokent

<img width="564" height="281" alt="image" src="https://github.com/user-attachments/assets/6fc19004-4f96-4831-bf96-7c4020a6fca1" />

Fejléc:
- Content-Type: application/json
- Accept: application/json

Példa /login kérésre:


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

Fejléc:
- Authorization: Bearer {token}
- Accept: application/json

Általános jogosultságok:
- admin: minden erőforrást lát/kezel
- user: csak a saját rekordjaihoz fér hozzá (patients/appointments), nem hozhat létre orvost/egyéb admin műveleteket

<img width="656" height="467" alt="image" src="https://github.com/user-attachments/assets/7f388b5a-17d0-4b65-a371-acd8df1abb71" />

---

## Patients (páciensek)

<img width="271" height="142" alt="image" src="https://github.com/user-attachments/assets/32f1ea7a-bb1c-430d-8880-ab186eded42a" />


GET `/patients` — lista
- admin: mindenkit lát
- user: csak saját record (feltételezve user.id = patient.id)

GET `/patients/{id}` — részletek a páciensről (403, ha nincs jogosultság)

POST `/patients` — létrehozás (csak admin tud létrehozni)

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

PUT `/patients/{id}` — teljes frissítés (csak admin)
```
{
  "name": "Norbert Kovács Updated",
  "email": "norbert_new@example.com"
}
```

DELETE `/patients/{id}` — törlés (csak admin)

---

## Doctors (orvosok)

<img width="268" height="135" alt="image" src="https://github.com/user-attachments/assets/f0380069-118a-46c9-acc1-1c99378feb60" />



GET `/doctors` — lista (minden user láthatja)
GET `/doctors/{id}` — részletek

POST `/doctors` — létrehozás (csak admin)
Body:
```
{
  "name": "Dr. Név",
  "specialization": "szakterület",
  "room": "101"
}
```

PUT `/doctors/{id}` — módosítás (csak admin)

DELETE `/doctors/{id}` — törlés (csak admin)

---

## Appointments (időpontok)

<img width="288" height="141" alt="image" src="https://github.com/user-attachments/assets/5ee0e6a6-ca86-40c2-af9e-50e624b5d047" />



GET `/appointments` — lista
- admin: minden időpont
- user: csak sajátjai (appointment.patient_id === user.id)
Lehetőség szűrésre query paramokkal:
- ?doctor_id=#
- ?status=scheduled|completed|cancelled

GET `/appointments/{id}` — részletek (403, ha nem jogosult)

POST `/appointments` — létrehozás (jelen implementáció: csak admin hozhat létre)
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

PUT `/appointments/{id}` — teljes frissítés (admin vagy a saját patient-je)


DELETE `/appointments/{id}` — törlés (admin vagy a saját patient-je)

---

## Példa hibaválasz (érvénytelen token)
```
Response: 401 Unauthorized
{
  "message": "Invalid token"
}
```

### Hitelesítés és Jogosultságok 

### Token-alapú Autentifikáció
- Minden hitelesített végpont `Authorization: Bearer {token}` header-t igényel
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
- Tesztek API hívásokat imitálnak: actingAs($user) vagy tokennel withHeaders(['Authorization' => 'Bearer '.$token])

### Factory-k:

**-AppointmentFactory.php**

```
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
            'patient_id' => Patient::factory(),   // új Patient rekordot ad hozzá
            'doctor_id' => Doctor::factory(),     // új Doctor rekordot ad hozzá
            'appointment_time' => $this->faker->dateTimeBetween('+1 days', '+1 month'),
            'status' => $this->faker->randomElement(['scheduled','completed','cancelled']),
        ];
    }

}
```
Az AppointmentFactory automatikusan létrehoz időpontokat a teszteléshez vagy seedeléshez. Minden új rekordhoz új pácienst és orvost generál, valamint véletlenszerű időpontot és státuszt rendel (scheduled, completed, cancelled).

**-DoctorFactory.php**

```
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
            'specialization' => $this->faker->word(),
            'room' => $this->faker->numberBetween(100, 500),
        ];
    }
}

?>
```
A DoctorFactory automatikusan létrehoz orvosokat teszteléshez vagy seedeléshez, véletlenszerű nevet, szakterületet és szobaszámot rendel minden új rekordhoz.

**-PatientFactory.php**

```
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Patient;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'birth_date' => $this->faker->date(),
        ];
    }
}

?>
```
A PatientFactory automatikusan létrehoz pácienseket teszteléshez vagy seedeléshez, véletlenszerű nevet, egyedi e-mail címet és születési dátumot generálva minden új rekordhoz.

**-UserFactory.php**

```
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => bcrypt('password'), // jelszó minden usernek: password
            'remember_token' => Str::random(10),
            'role' => 'user', // alap user, admin a seederben külön
        ];
    }

    // Admin állapot
    public function admin()
    {
        return $this->state(fn () => ['role' => 'admin']);
    }
}

```
A UserFactory automatikusan létrehoz felhasználókat teszteléshez vagy seedeléshez. Minden usernek ad egy nevet, egyedi e-mail címet, alap jelszót (password), valamint egy role mezőt (user), és tartalmaz egy admin helper-t is, amivel könnyen készíthetünk admin jogosultságú felhasználót a seederben.


## Controllerek

**-AuthController.php**

```
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // REGISTER
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        return response()->json(['user' => $user], 201);
    }

    // LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Token generálás
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['token' => $token]);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}

```

Az AuthController kezeli a felhasználók regisztrációját, bejelentkezését és kijelentkezését.

**-DoctorController.php**

```
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Doctor;

class DoctorController extends Controller
{
    // Listázás
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Doctor::query();

        // USER-ek ne módosíthassák, de láthatják az összes orvost
        // Ha akarjuk, csak admin láthat mindent, usernek csak listázás
        return response()->json($query->get());
    }

    // Egy orvos lekérése
    public function show($id)
    {
        $doctor = Doctor::findOrFail($id);
        return response()->json($doctor);
    }

    // Új orvos létrehozása (csak admin)
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string',
            'specialization' => 'required|string',
            'room' => 'required|string',
        ]);

        $doctor = Doctor::create($data);
        return response()->json($doctor, 201);
    }

    // Orvos adatainak módosítása (csak admin)
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $doctor = Doctor::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string',
            'specialization' => 'sometimes|string',
            'room' => 'sometimes|string',
        ]);

        $doctor->update($data);
        return response()->json($doctor);
    }

    // Orvos törlése (csak admin)
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $doctor = Doctor::findOrFail($id);
        $doctor->delete();

        return response()->json(['message' => 'Deleted']);
    }
}

```
A DoctorController kezeli az orvosok adatait az API-n keresztül. Bárki lekérdezheti az orvosok listáját vagy egy konkrét orvos adatait, de új orvos létrehozása, módosítása vagy törlése csak admin jogosultsággal lehetséges

**-PatientController.php**

```

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Patient::query();

        if ($user->role !== 'admin') {
            // User csak a saját recordját látja, feltételezzük user_id = patient_id
            $query->where('id', $user->id);
        }

        return response()->json($query->get());
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $patient = Patient::findOrFail($id);

        if ($user->role !== 'admin' && $patient->id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($patient);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:patients',
            'birth_date' => 'required|date'
        ]);

        $patient = Patient::create($data);
        return response()->json($patient, 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $patient = Patient::findOrFail($id);

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:patients,email,'.$id,
            'birth_date' => 'sometimes|date'
        ]);

        $patient->update($data);
        return response()->json($patient);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $patient = Patient::findOrFail($id);
        $patient->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

```

A PatientController kezeli a páciens adatait az API-n keresztül. Admin felhasználók teljes hozzáféréssel létrehozhatnak, módosíthatnak és törölhetnek pácienseket, míg normál felhasználók csak a saját adataikat láthatják és kérhetik le, a jogosultságokat minden műveletnél ellenőrzi.

**-AppointmentController.php**

```
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Appointment::query();

        if ($user->role !== 'admin') {
            $query->where('patient_id', $user->id);
        }

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->get());
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $appointment = Appointment::findOrFail($id);

        if ($user->role !== 'admin' && $appointment->patient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($appointment);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_time' => 'required|date',
            'status' => 'required|string',
        ]);

        // Csak admin hozhat létre időpontot
        if(auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $appointment = Appointment::create($request->all());

        return response()->json($appointment, 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $appointment = Appointment::findOrFail($id);

        if ($user->role !== 'admin' && $appointment->patient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'doctor_id' => 'sometimes|integer',
            'appointment_time' => 'sometimes|date',
            'status' => 'sometimes|string'
        ]);

        $appointment->update($data);
        return response()->json($appointment);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $appointment = Appointment::findOrFail($id);

        if ($user->role !== 'admin' && $appointment->patient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $appointment->delete();
        return response()->json(['message' => 'Deleted']);
    }
}


```

Az AppointmentController kezeli az időpontok API-n keresztüli CRUD műveleteit.

## Modellek

**-Appointment.php**

```
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory; // <-- EZ FONTOS
    use SoftDeletes;

    protected $fillable = ['patient_id', 'doctor_id', 'appointment_time', 'status'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}

```
Az Appointment (Időpont) modell az időpontfoglalásokat reprezentálja az alkalmazásban.
Támogatja a factory-ket (teszteléshez/seedeléshez), a soft delete-et (törléskor nem törli végleg az adatot), és kapcsolatban áll egy Patient-tel és egy Doctor-ral (belongsTo), miközben csak a megadott mezők tölthetők tömegesen ($fillable).

**-Doctor.php**

```
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use HasFactory; // FONTOS
    use SoftDeletes;

    protected $fillable = ['name', 'specialization', 'room'];
}

```

A Doctor (Orvos) modell az orvosokat kezeli az alkalmazásban.
Támogatja a factory-ket (teszteléshez/seedeléshez), a soft delete-et (logikai törlés), és csak a megadott mezők (name, specialization, room) tölthetők fel tömegesen ($fillable).

**-Patient.php**

```

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory; // EZ FONTOS
    use SoftDeletes;

    protected $fillable = ['name', 'email', 'birth_date'];
}

?>

```

A Patient (Páciens) modell a páciensek adatainak kezeléséért felel.
Támogatja a factory-ket (tesztelés/seedelés), a soft delete-et (logikai törlés), és csak a megadott mezők (name, email, birth_date) tölthetők fel tömegesen ($fillable).

**-User.php**

```

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}

```
A User (Felhasználó) modell kezeli az alkalmazás felhasználóit és az autentikációt.
Támogatja az API tokenes beléptetést (Sanctum), a factory-ket, az értesítéseket, valamint a soft delete-et; a jelszó rejtett és automatikusan hash-elve kerül mentésre.

## Seedelés:

Ez a **DatabaseSeeder** felelős az adatbázis feltöltéséért tesztelés vagy fejlesztés során. Létrehoz:

1. Felhasználókat – 3 admin és 3 normál user.
2. Pácienseket – 10 darab véletlenszerű rekord.
3. Orvosokat – 5 darab véletlenszerű rekord.
4. Időpontokat – 20 darab foglalás, ahol a patient_id és doctor_id már létező páciensekből és orvosokból kerül kiválasztásra, így valódi kapcsolatok jönnek létre az adatok között.


```
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Appointment;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1️⃣ USERS
        User::factory()->count(3)->admin()->create(); // 3 admin
        User::factory()->count(3)->create();         // 3 normál user

        // 2️⃣ PATIENTS
        $patients = Patient::factory()->count(10)->create();

        // 3️⃣ DOCTORS
        $doctors = Doctor::factory()->count(5)->create();

        // 4️⃣ APPOINTMENTS
        // Már létező patient/doctor rekordokból választ
        Appointment::factory()->count(20)->create([
            'patient_id' => function () use ($patients) {
                return $patients->random()->id;
            },
            'doctor_id' => function () use ($doctors) {
                return $doctors->random()->id;
            }
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






