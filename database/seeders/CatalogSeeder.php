<?php

namespace Database\Seeders;

use App\Models\CatalogItem;
use App\Models\CatalogType;
use Illuminate\Database\Seeder;

/**
 * Catálogos generales del sistema. Los marcados como "del sistema" no se
 * pueden eliminar ni cambiar de código, porque el código los usa por nombre
 * (p. ej. CatalogType::options('document_types')).
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalogs = [
            'document_types' => [
                'name' => 'Tipos de documento',
                'description' => 'Documentos que se adjuntan a personas, alumnos y docentes.',
                'items' => [
                    'identificacion' => 'Identificación oficial',
                    'curp' => 'CURP',
                    'acta_nacimiento' => 'Acta de nacimiento',
                    'comprobante_domicilio' => 'Comprobante de domicilio',
                    'fotografia' => 'Fotografía',
                    'titulo' => 'Título profesional',
                    'cedula' => 'Cédula profesional',
                    'certificado' => 'Certificado de estudios',
                    'comprobante_inscripcion' => 'Comprobante de inscripción',
                    'constancia' => 'Constancia',
                    'otro' => 'Otro',
                ],
            ],

            'relationships' => [
                'name' => 'Parentescos',
                'description' => 'Para contactos de emergencia.',
                'items' => [
                    'padre' => 'Padre',
                    'madre' => 'Madre',
                    'conyuge' => 'Cónyuge',
                    'hijo' => 'Hijo(a)',
                    'hermano' => 'Hermano(a)',
                    'tutor' => 'Tutor(a)',
                    'otro' => 'Otro',
                ],
            ],

            'blood_types' => [
                'name' => 'Tipos de sangre',
                'items' => [
                    'o_pos' => 'O+',
                    'o_neg' => 'O-',
                    'a_pos' => 'A+',
                    'a_neg' => 'A-',
                    'b_pos' => 'B+',
                    'b_neg' => 'B-',
                    'ab_pos' => 'AB+',
                    'ab_neg' => 'AB-',
                ],
            ],

            'payment_methods' => [
                'name' => 'Métodos de pago',
                'items' => [
                    'efectivo' => 'Efectivo',
                    'tarjeta_debito' => 'Tarjeta de débito',
                    'tarjeta_credito' => 'Tarjeta de crédito',
                    'transferencia' => 'Transferencia',
                    'deposito' => 'Depósito bancario',
                ],
            ],

            'units' => [
                'name' => 'Unidades de medida',
                'description' => 'Para materiales e insumos del almacén.',
                'items' => [
                    'pieza' => 'Pieza',
                    'caja' => 'Caja',
                    'paquete' => 'Paquete',
                    'juego' => 'Juego',
                    'ml' => 'Mililitro',
                    'litro' => 'Litro',
                    'gramo' => 'Gramo',
                    'kilogramo' => 'Kilogramo',
                ],
            ],
        ];

        foreach ($catalogs as $code => $catalog) {
            $type = CatalogType::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $catalog['name'],
                    'description' => $catalog['description'] ?? null,
                    'is_system' => true,
                ]
            );

            $sort = 0;

            foreach ($catalog['items'] as $itemCode => $name) {
                CatalogItem::updateOrCreate(
                    ['catalog_type_id' => $type->id, 'code' => $itemCode],
                    ['name' => $name, 'sort' => $sort++]
                );
            }
        }
    }
}
