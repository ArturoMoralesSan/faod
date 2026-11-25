<?php

namespace Database\Seeders\Demo;

use App\Models\People;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Utilidades compartidas por los seeders de demostración de la FAOD.
 *
 * Las personas son ficticias: nombres comunes en Durango combinados al azar
 * (con semilla fija, así cada corrida genera los mismos datos).
 */
trait DemoHelpers
{
    /** Contraseña de todos los usuarios de demostración. */
    public const DEMO_PASSWORD = '12345678';

    private static ?string $passwordHash = null;

    /** @var array<string, true> */
    private static array $usedEmails = [];

    private const FEMALE_NAMES = [
        'María José', 'Ana Sofía', 'Valeria', 'Ximena', 'Fernanda', 'Daniela', 'Andrea', 'Mariana', 'Regina', 'Camila',
        'Paola', 'Alejandra', 'Karla', 'Diana', 'Lucía', 'Gabriela', 'Itzel', 'Brenda', 'Yesenia', 'Rocío', 'Guadalupe',
        'Claudia', 'Verónica', 'Patricia', 'Adriana', 'Elena', 'Natalia', 'Renata', 'Montserrat', 'Abril', 'Jimena', 'Sofía',
        'Martha', 'Leticia', 'Rosa Elena', 'Silvia', 'Laura', 'Mónica', 'Lorena', 'Alma Delia', 'Carmen', 'Teresa',
    ];

    private const MALE_NAMES = [
        'José Luis', 'Juan Carlos', 'Luis Fernando', 'Diego', 'Santiago', 'Emiliano', 'Sebastián', 'Alejandro', 'Daniel',
        'Jorge', 'Ricardo', 'Eduardo', 'Arturo', 'Héctor', 'Francisco', 'Miguel Ángel', 'Rafael', 'Raúl', 'Óscar',
        'Gerardo', 'Iván', 'Rodrigo', 'Andrés', 'Mauricio', 'Jesús', 'Manuel', 'Roberto', 'Fernando', 'Javier', 'Ángel',
        'Leonardo', 'Mateo', 'Jesús Alberto', 'Carlos', 'Alfonso', 'Sergio', 'Enrique', 'Martín', 'Gustavo', 'Hugo',
    ];

    private const SURNAMES = [
        'Hernández', 'García', 'Martínez', 'López', 'González', 'Rodríguez', 'Pérez', 'Sánchez', 'Ramírez', 'Flores',
        'Reyes', 'Soto', 'Vázquez', 'Castañeda', 'Rivas', 'Quiñones', 'Arreola', 'Salazar', 'Ontiveros', 'Herrera',
        'Medina', 'Gurrola', 'Valenzuela', 'Saucedo', 'Simental', 'Galindo', 'Soria', 'Rocha', 'Barraza', 'Morales',
        'Nevárez', 'Fierro', 'Escalera', 'Carrete', 'Ayala', 'Calderón', 'Meraz', 'Unzueta', 'Avitia', 'Corral',
        'Delgado', 'Lozano', 'Ríos', 'Cisneros', 'Alvarado', 'Mijares', 'Ibarra', 'Bueno', 'Villarreal', 'Ochoa',
    ];

    /** Colonias de la ciudad de Durango con su código postal. */
    private const NEIGHBORHOODS = [
        ['Zona Centro', '34000'], ['Los Ángeles', '34076'], ['Jardines de Durango', '34200'], ['Guadalupe', '34220'],
        ['Valle del Sur', '34120'], ['Fraccionamiento Domingo Arrieta', '34180'], ['Del Maestro', '34240'],
        ['Real del Mezquital', '34199'], ['Fidel Velázquez', '34229'], ['Hipódromo', '34270'], ['Las Alamedas', '34139'],
        ['Silvestre Dorador', '34070'], ['Gobernadores', '34148'], ['Fraccionamiento Huizache', '34160'],
        ['Fraccionamiento Las Quintas', '34106'], ['Nueva Vizcaya', '34080'], ['El Refugio', '34170'], ['Ciénega', '34020'],
    ];

    private const STREETS = [
        'Av. 20 de Noviembre', 'Blvd. Felipe Pescador', 'Av. Cuauhtémoc', 'Blvd. Domingo Arrieta', 'Calle Negrete',
        'Calle Pino Suárez', 'Calle Constitución', 'Av. Lázaro Cárdenas', 'Calle Hidalgo', 'Calle Zaragoza',
        'Blvd. Guadiana', 'Av. Fanny Anitúa', 'Calle Bruno Martínez', 'Calle Aquiles Serdán', 'Blvd. Dolores del Río',
        'Calle Juárez', 'Av. Universidad', 'Calle Coronado', 'Calle Patoni', 'Blvd. Francisco Villa',
    ];

    protected function pick(array $items): mixed
    {
        return $items[mt_rand(0, count($items) - 1)];
    }

    /** @return array<int, mixed> */
    protected function pickMany(array $items, int $count): array
    {
        $keys = array_keys($items);
        $picked = [];

        while ($keys && count($picked) < $count) {
            $index = mt_rand(0, count($keys) - 1);
            $picked[] = $items[$keys[$index]];
            array_splice($keys, $index, 1);
        }

        return $picked;
    }

    /** true con la probabilidad indicada (0 a 100). */
    protected function chance(int $percent): bool
    {
        return mt_rand(1, 100) <= $percent;
    }

    /** Nombre completo al azar, para contactos que no se registran como persona. */
    protected function fullName(?string $gender = null): string
    {
        $gender ??= $this->chance(50) ? 'femenino' : 'masculino';

        return implode(' ', [
            $this->pick($gender === 'femenino' ? self::FEMALE_NAMES : self::MALE_NAMES),
            $this->pick(self::SURNAMES),
            $this->pick(self::SURNAMES),
        ]);
    }

    protected function phone(): string
    {
        return sprintf('618 %03d %04d', mt_rand(100, 899), mt_rand(0, 9999));
    }

    /**
     * Crea una persona ficticia. $gender: 'femenino' | 'masculino' | null (al azar).
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function makePerson(?string $gender = null, array $overrides = [], ?int $minAge = 19, ?int $maxAge = 60): People
    {
        $gender ??= $this->chance(55) ? 'femenino' : 'masculino';
        [$neighborhood, $postalCode] = $this->pick(self::NEIGHBORHOODS);

        return People::create([
            'first_name' => $this->pick($gender === 'femenino' ? self::FEMALE_NAMES : self::MALE_NAMES),
            'paternal_surname' => $this->pick(self::SURNAMES),
            'maternal_surname' => $this->pick(self::SURNAMES),
            'birth_date' => today()->subYears(mt_rand($minAge, $maxAge))->subDays(mt_rand(0, 364))->toDateString(),
            'gender' => $gender,
            'phone' => $this->phone(),
            'street' => $this->pick(self::STREETS),
            'exterior_number' => (string) mt_rand(100, 2999),
            'neighborhood' => $neighborhood,
            'postal_code' => $postalCode,
            'city' => 'Victoria de Durango',
            'state' => 'Durango',
            'country' => 'México',
            ...$overrides,
        ]);
    }

    /** Usuario con acceso al sistema para una persona; el correo se arma con su nombre. */
    protected function makeUser(People $person, string $domain, string $role): User
    {
        $base = Str::slug(Str::before($person->first_name, ' ').'.'.$person->paternal_surname, '.');
        $email = "{$base}@{$domain}";

        for ($n = 2; isset(self::$usedEmails[$email]) || User::where('email', $email)->exists(); $n++) {
            $email = "{$base}{$n}@{$domain}";
        }

        self::$usedEmails[$email] = true;
        self::$passwordHash ??= Hash::make(self::DEMO_PASSWORD);

        $user = new User([
            'name' => $person->full_name,
            'email' => $email,
            'password' => self::$passwordHash,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $user->assignRole($role);
        $person->update(['user_id' => $user->id, 'personal_email' => $email]);

        return $user;
    }

    /** Día hábil (lunes a viernes) igual o posterior a la fecha. */
    protected function weekday(CarbonInterface $date): CarbonInterface
    {
        // La app usa fechas inmutables: siempre reasignar.
        $date = Date::parse($date);

        while ($date->isWeekend()) {
            $date = $date->addDay();
        }

        return $date;
    }

    protected function time(int $hour, int $minute = 0): string
    {
        return sprintf('%02d:%02d:00', $hour, $minute);
    }
}
