<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de existencias: toda alta o baja de inventario pasa por aquí para que
 * lotes, kárdex y saldos queden consistentes.
 *
 * - Las salidas toman primero lo que caduca antes (FEFO).
 * - No se permite existencia negativa.
 * - El consumo clínico no usa lotes caducados.
 */
class InventoryService
{
    /**
     * Entrada de mercancía (compra, donación, devolución).
     */
    public function receive(InventoryItem $item, Warehouse $warehouse, float $quantity, array $data = []): InventoryMovement
    {
        $this->assertPositive($quantity);

        if ($item->tracks_expiration && empty($data['expires_at'])) {
            throw ValidationException::withMessages(['expires_at' => 'Este artículo requiere fecha de caducidad.']);
        }

        return DB::transaction(function () use ($item, $warehouse, $quantity, $data) {
            $lot = InventoryLot::query()
                ->where('inventory_item_id', $item->id)
                ->where('warehouse_id', $warehouse->id)
                ->where('lot_number', $data['lot_number'] ?? null)
                ->when(
                    $data['expires_at'] ?? null,
                    fn ($q, $date) => $q->whereDate('expires_at', $date),
                    fn ($q) => $q->whereNull('expires_at'),
                )
                ->lockForUpdate()
                ->first();

            $lot ??= new InventoryLot([
                'inventory_item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'lot_number' => $data['lot_number'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'quantity' => 0,
            ]);

            $lot->fill([
                'quantity' => $lot->quantity + $quantity,
                'supplier_id' => $data['supplier_id'] ?? $lot->supplier_id,
                'unit_cost' => $data['unit_cost'] ?? $lot->unit_cost,
                'received_at' => $lot->received_at ?? today(),
            ])->save();

            return $this->record($item, $warehouse, $lot, $data['type'] ?? 'entrada', $quantity, [
                'unit_cost' => $data['unit_cost'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'reason' => $data['reason'] ?? null,
                'reference' => $data['reference'] ?? null,
                'related_movement_id' => $data['related_movement_id'] ?? null,
            ]);
        });
    }

    /**
     * Salida (merma, préstamo, baja) o consumo clínico. Reparte la cantidad entre
     * lotes por fecha de caducidad, o la toma del lote indicado.
     *
     * @return Collection<int, InventoryMovement>
     */
    public function withdraw(InventoryItem $item, Warehouse $warehouse, float $quantity, array $data = []): Collection
    {
        $this->assertPositive($quantity);
        $type = $data['type'] ?? 'salida';

        return DB::transaction(function () use ($item, $warehouse, $quantity, $data, $type) {
            $lots = InventoryLot::query()
                ->where('inventory_item_id', $item->id)
                ->where('warehouse_id', $warehouse->id)
                ->where('quantity', '>', 0)
                ->when($data['inventory_lot_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
                ->when($type === 'consumo', fn ($q) => $q->where(fn ($q) => $q
                    ->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', today())))
                // FEFO: primero lo que caduca antes; lo que no caduca, al final.
                ->orderByRaw('expires_at is null')
                ->orderBy('expires_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $available = $lots->sum('quantity');

            if ($available + 0.00001 < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => sprintf(
                        'Existencia insuficiente de "%s" en %s: hay %s %s%s.',
                        $item->name,
                        $warehouse->name,
                        rtrim(rtrim(number_format($available, 2, '.', ''), '0'), '.'),
                        $item->unit,
                        $type === 'consumo' ? ' vigentes' : '',
                    ),
                ]);
            }

            $movements = collect();
            $remaining = $quantity;

            foreach ($lots as $lot) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min($remaining, $lot->quantity);
                $lot->update(['quantity' => $lot->quantity - $take]);
                $remaining -= $take;

                $movements->push($this->record($item, $warehouse, $lot, $type, -$take, [
                    'unit_cost' => $lot->unit_cost,
                    'reason' => $data['reason'] ?? null,
                    'reference' => $data['reference'] ?? null,
                    'treatment_procedure_id' => $data['treatment_procedure_id'] ?? null,
                ]));
            }

            return $movements;
        });
    }

    /**
     * Deja un lote con la cantidad indicada (conteo físico o corrección).
     */
    public function adjust(InventoryLot $lot, float $newQuantity, string $reason, ?int $countId = null): ?InventoryMovement
    {
        if ($newQuantity < 0) {
            throw ValidationException::withMessages(['quantity' => 'La existencia no puede ser negativa.']);
        }

        return DB::transaction(function () use ($lot, $newQuantity, $reason, $countId) {
            $lot = InventoryLot::lockForUpdate()->findOrFail($lot->id);
            $difference = round($newQuantity - $lot->quantity, 2);

            if ($difference == 0) {
                return null;
            }

            $lot->update(['quantity' => $newQuantity]);

            return $this->record($lot->item, $lot->warehouse, $lot, 'ajuste', $difference, [
                'unit_cost' => $lot->unit_cost,
                'reason' => $reason,
                'inventory_count_id' => $countId,
            ]);
        });
    }

    /**
     * Traspaso entre almacenes conservando lote y caducidad.
     */
    public function transfer(InventoryItem $item, Warehouse $from, Warehouse $to, float $quantity, array $data = []): void
    {
        if ($from->is($to)) {
            throw ValidationException::withMessages(['to_warehouse_id' => 'Elige un almacén distinto al de origen.']);
        }

        DB::transaction(function () use ($item, $from, $to, $quantity, $data) {
            $outs = $this->withdraw($item, $from, $quantity, [
                ...$data,
                'type' => 'traspaso',
                'reason' => $data['reason'] ?? "Traspaso a {$to->name}",
            ]);

            foreach ($outs as $out) {
                $in = $this->receive($item, $to, abs($out->quantity), [
                    'type' => 'traspaso',
                    'lot_number' => $out->lot?->lot_number,
                    'expires_at' => $out->lot?->expires_at?->toDateString(),
                    'unit_cost' => $out->unit_cost,
                    'supplier_id' => $out->lot?->supplier_id,
                    'reason' => $data['reason'] ?? "Traspaso desde {$from->name}",
                    'reference' => $data['reference'] ?? null,
                    'related_movement_id' => $out->id,
                ]);

                $out->update(['related_movement_id' => $in->id]);
            }
        });
    }

    /** Existencia total de un artículo en un almacén. */
    public function stock(InventoryItem $item, ?Warehouse $warehouse = null): float
    {
        return (float) InventoryLot::query()
            ->where('inventory_item_id', $item->id)
            ->when($warehouse, fn ($q) => $q->where('warehouse_id', $warehouse->id))
            ->sum('quantity');
    }

    private function record(InventoryItem $item, Warehouse $warehouse, InventoryLot $lot, string $type, float $quantity, array $extra): InventoryMovement
    {
        return InventoryMovement::create([
            'inventory_item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'inventory_lot_id' => $lot->id,
            'type' => $type,
            'quantity' => $quantity,
            'balance_after' => $this->stock($item, $warehouse),
            'user_id' => auth()->id(),
            'moved_at' => now(),
            ...$extra,
        ]);
    }

    private function assertPositive(float $quantity): void
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'La cantidad debe ser mayor que cero.']);
        }
    }
}
