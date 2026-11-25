<?php

namespace Database\Seeders\Demo;

use App\Models\Clinic;
use App\Models\InventoryItem;
use App\Models\Space;
use App\Models\StudentProfile;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Proveedores, almacenes y artículos con existencias. Todos los movimientos
 * pasan por InventoryService, así lotes y saldos quedan consistentes.
 */
class FaodInventoryDemoSeeder extends Seeder
{
    use DemoHelpers;

    /** [clave, nombre, categoría, tipo, unidad, mínimo, caduca, costo, cantidad inicial, proveedor]. */
    private const ITEMS = [
        ['ANE-001', 'Lidocaína con epinefrina 2% (cartucho)', 'anestesicos', 'material', 'caja', 10, true, 420, 40, 0],
        ['ANE-002', 'Mepivacaína 3% sin vasoconstrictor (cartucho)', 'anestesicos', 'material', 'caja', 4, true, 480, 12, 0],
        ['ANE-003', 'Anestésico tópico en gel (benzocaína 20%)', 'anestesicos', 'material', 'pieza', 6, true, 95, 14, 1],
        ['RES-001', 'Resina compuesta nanohíbrida A2 (jeringa 4 g)', 'resinas', 'material', 'pieza', 20, true, 380, 60, 0],
        ['RES-002', 'Resina compuesta nanohíbrida A3 (jeringa 4 g)', 'resinas', 'material', 'pieza', 20, true, 380, 45, 0],
        ['RES-003', 'Adhesivo universal (frasco 5 ml)', 'resinas', 'material', 'pieza', 8, true, 690, 18, 0],
        ['RES-004', 'Ácido grabador ortofosfórico 37%', 'resinas', 'material', 'pieza', 10, true, 85, 30, 1],
        ['CEM-001', 'Ionómero de vidrio de restauración', 'cementos', 'material', 'pieza', 6, true, 560, 10, 1],
        ['CEM-002', 'Hidróxido de calcio fotocurable', 'cementos', 'material', 'pieza', 5, true, 310, 4, 1],
        ['END-001', 'Limas K 1.ª serie 25 mm (caja 6)', 'endodoncia', 'material', 'caja', 15, false, 150, 50, 0],
        ['END-002', 'Puntas de gutapercha 2.ª serie', 'endodoncia', 'material', 'caja', 10, false, 120, 30, 0],
        ['END-003', 'Hipoclorito de sodio 5.25% (litro)', 'endodoncia', 'material', 'litro', 6, true, 60, 20, 2],
        ['IMP-001', 'Alginato tipo II (bolsa 454 g)', 'impresion', 'material', 'paquete', 10, true, 165, 35, 2],
        ['IMP-002', 'Yeso tipo III (bolsa 1 kg)', 'laboratorio', 'material', 'kilogramo', 15, false, 55, 60, 2],
        ['IMP-003', 'Acrílico autopolimerizable (polvo y líquido)', 'laboratorio', 'material', 'juego', 5, true, 450, 12, 2],
        ['PRF-001', 'Pasta profiláctica (frasco 340 g)', 'profilaxis', 'material', 'pieza', 6, true, 140, 15, 1],
        ['PRF-002', 'Flúor en gel acidulado 1.23%', 'profilaxis', 'material', 'pieza', 6, true, 120, 3, 1],
        ['PRF-003', 'Sellador de fosetas y fisuras', 'profilaxis', 'material', 'pieza', 5, true, 520, 9, 0],
        ['DES-001', 'Guantes de nitrilo talla M (caja 100)', 'desechables', 'material', 'caja', 30, false, 145, 120, 1],
        ['DES-002', 'Cubrebocas tricapa (caja 50)', 'desechables', 'material', 'caja', 30, false, 70, 90, 1],
        ['DES-003', 'Eyectores de saliva (bolsa 100)', 'desechables', 'material', 'paquete', 15, false, 65, 40, 1],
        ['DES-004', 'Baberos desechables (paquete 125)', 'desechables', 'material', 'paquete', 10, false, 110, 25, 1],
        ['BIO-001', 'Bolsas para esterilizar 90 x 230 mm (caja 200)', 'bioseguridad', 'material', 'caja', 10, false, 230, 22, 1],
        ['BIO-002', 'Indicador biológico para autoclave', 'bioseguridad', 'material', 'caja', 2, true, 980, 5, 1],
        ['BIO-003', 'Desinfectante de superficies (litro)', 'bioseguridad', 'material', 'litro', 10, true, 180, 28, 1],
        ['CIR-001', 'Sutura seda 3-0 con aguja (caja 12)', 'cirugia', 'material', 'caja', 5, true, 390, 14, 0],
        ['CIR-002', 'Hojas de bisturí n.º 15 (caja 100)', 'cirugia', 'material', 'caja', 2, false, 320, 6, 0],
        ['MED-001', 'Amoxicilina 500 mg (caja 12 cápsulas)', 'medicamentos', 'material', 'caja', 5, true, 85, 20, 2],
        ['MED-002', 'Ibuprofeno 400 mg (caja 10 tabletas)', 'medicamentos', 'material', 'caja', 5, true, 40, 25, 2],
        ['ORT-001', 'Brackets metálicos Roth 0.022 (kit 20)', 'ortodoncia', 'material', 'juego', 5, false, 650, 18, 0],
        ['ORT-002', 'Arco NiTi 0.014 superior (paquete 10)', 'ortodoncia', 'material', 'paquete', 5, false, 280, 15, 0],
        ['INS-001', 'Pieza de mano de alta velocidad', 'instrumental', 'instrumental', 'pieza', 4, false, 3200, 10, 0],
        ['INS-002', 'Pieza de mano de baja velocidad (contrángulo)', 'instrumental', 'instrumental', 'pieza', 3, false, 2800, 8, 0],
        ['INS-003', 'Fórceps n.º 150 superior', 'instrumental', 'instrumental', 'pieza', 2, false, 950, 6, 0],
        ['INS-004', 'Curetas Gracey (juego de 7)', 'instrumental', 'instrumental', 'juego', 2, false, 2400, 4, 0],
        ['EQP-001', 'Lámpara de fotocurado LED inalámbrica', 'otro', 'equipo', 'pieza', 2, false, 4500, 3, 0],
    ];

    public function run(InventoryService $inventory): void
    {
        $keeper = User::whereHas('people.staffProfile', fn ($q) => $q->where('position', 'Encargado de almacén'))->first();
        Auth::setUser($keeper);

        $suppliers = collect([
            ['Depósito Dental del Guadiana, S.A. de C.V.', 'DDG0503158K2', 'Blvd. Felipe Pescador 1402, Durango, Dgo.'],
            ['Distribuidora Médica del Norte, S.A. de C.V.', 'DMN1107224T9', 'Av. 20 de Noviembre 2210, Durango, Dgo.'],
            ['Insumos y Laboratorio Dental de Durango', null, 'Calle Negrete 815, Zona Centro, Durango, Dgo.'],
        ])->map(function ($row) {
            return Supplier::create([
                'name' => $row[0],
                'rfc' => $row[1],
                'contact_name' => $this->fullName(),
                'phone' => $this->phone(),
                'email' => 'ventas@'.str($row[0])->before(',')->slug().'.test',
                'address' => $row[2],
                'is_active' => true,
            ]);
        });

        $general = Warehouse::firstOrCreate(['code' => 'ALM'], ['name' => 'Almacén general', 'is_active' => true]);
        $clinic = Warehouse::create(['code' => 'ALM-CLIC', 'name' => 'Subalmacén de Clínica de Licenciatura', 'clinic_id' => Clinic::firstWhere('code', 'CLIN-LIC')?->id, 'is_active' => true]);
        $postgrad = Warehouse::create(['code' => 'ALM-POS', 'name' => 'Subalmacén de clínicas de posgrado', 'clinic_id' => Clinic::firstWhere('code', 'CLIN-PERIO')?->id, 'is_active' => true]);
        $lab = Warehouse::create(['code' => 'ALM-LPRO', 'name' => 'Almacén del Laboratorio de prótesis', 'space_id' => Space::firstWhere('code', 'LAB-PRO')?->id, 'is_active' => true]);

        foreach (self::ITEMS as $i => [$code, $name, $category, $kind, $unit, $minimum, $expires, $cost, $quantity, $supplierIndex]) {
            $supplier = $suppliers[$supplierIndex];

            $item = InventoryItem::create([
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'kind' => $kind,
                'unit' => $unit,
                'minimum_stock' => $minimum,
                'tracks_expiration' => $expires,
                'default_supplier_id' => $supplier->id,
                'is_active' => true,
            ]);

            // Dos compras: una anterior (lote que caduca antes) y una reciente.
            $batches = $quantity >= 10 ? [intdiv($quantity, 3), $quantity - intdiv($quantity, 3)] : [$quantity];

            foreach ($batches as $b => $batchQuantity) {
                $expiresAt = match (true) {
                    ! $expires => null,
                    $b === 0 && $i % 9 === 0 => today()->subDays(10),   // ya caducó
                    $b === 0 && $i % 4 === 0 => today()->addDays(mt_rand(8, 25)), // por caducar
                    default => today()->addMonths(mt_rand(8, 30)),
                };

                $inventory->receive($item, $general, $batchQuantity, [
                    'lot_number' => $expires ? sprintf('L%s%03d', today()->subMonths(4 - $b * 3)->format('ym'), $i * 7 + $b) : null,
                    'expires_at' => $expiresAt?->toDateString(),
                    'unit_cost' => $cost,
                    'supplier_id' => $supplier->id,
                    'reason' => 'Compra',
                    'reference' => sprintf('FAC-%05d', 3100 + $i * 3 + $b),
                ]);
            }

            if ($kind === 'instrumental' || $kind === 'equipo') {
                continue;
            }

            $available = $inventory->stock($item, $general);

            // Surtido a las clínicas y al laboratorio.
            $target = match ($category) {
                'laboratorio', 'impresion' => $lab,
                'ortodoncia' => $postgrad,
                default => $clinic,
            };

            $transfer = floor($available * 0.45);

            if ($transfer >= 1) {
                $inventory->transfer($item, $general, $target, $transfer, ['reference' => 'REQ-'.(700 + $i)]);
            }

            if (in_array($category, ['resinas', 'anestesicos', 'desechables', 'endodoncia'], true) && $available - $transfer >= 4) {
                $inventory->transfer($item, $general, $postgrad, floor(($available - $transfer) * 0.25), ['reference' => 'REQ-'.(800 + $i)]);
            }
        }

        $this->studentLoans($inventory, $general);
        $this->losses($inventory, $clinic);

        Auth::forgetUser();
    }

    /** Préstamo de instrumental a alumnos; parte ya devuelto. */
    private function studentLoans(InventoryService $inventory, Warehouse $warehouse): void
    {
        $students = StudentProfile::where('status', 'active')
            ->whereHas('groups', fn ($q) => $q->where('code', 'like', 'LCD-%')->where('period_number', '>=', 5))
            ->inRandomOrder()
            ->limit(6)
            ->get();

        $items = InventoryItem::whereIn('code', ['INS-001', 'INS-002', 'INS-003', 'INS-004'])->get();

        foreach ($students as $n => $student) {
            $item = $items[$n % $items->count()];
            $out = $inventory->withdraw($item, $warehouse, 1, [
                'type' => 'entrega',
                'reason' => 'Préstamo para práctica clínica',
                'student_profile_id' => $student->id,
            ])->first();

            if ($n % 2 === 0) {
                $inventory->receive($item, $warehouse, 1, [
                    'type' => 'devolucion',
                    'inventory_lot_id' => $out->inventory_lot_id,
                    'reason' => 'Devolución de préstamo',
                    'student_profile_id' => $student->id,
                    'related_movement_id' => $out->id,
                ]);
            }
        }
    }

    private function losses(InventoryService $inventory, Warehouse $warehouse): void
    {
        $alginate = InventoryItem::firstWhere('code', 'IMP-001');
        $gloves = InventoryItem::firstWhere('code', 'DES-001');
        $resin = InventoryItem::firstWhere('code', 'RES-001');

        if ($inventory->stock($gloves, $warehouse) >= 2) {
            $inventory->withdraw($gloves, $warehouse, 2, ['type' => 'danado', 'reason' => 'Cajas mojadas por fuga en el techo']);
        }

        if ($inventory->stock($resin, $warehouse) >= 1) {
            $inventory->withdraw($resin, $warehouse, 1, ['type' => 'merma', 'reason' => 'Jeringa endurecida por exposición a la luz']);
        }

        $generalAlginate = Warehouse::firstWhere('code', 'ALM');

        if ($alginate && $inventory->stock($alginate, $generalAlginate) >= 1) {
            $inventory->withdraw($alginate, $generalAlginate, 1, ['type' => 'merma', 'reason' => 'Bolsa abierta y contaminada']);
        }
    }
}
