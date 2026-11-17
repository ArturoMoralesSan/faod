# Pendientes del sistema

Sistema integral de gestión interna – Facultad de Odontología
Última actualización: 17 de noviembre de 2026

## Hecho

- **Administración general:** usuarios (con varios roles), roles, permisos, departamentos y coordinaciones, parámetros generales y catálogos.
- **Selector de rol:** el menú lateral se arma con los permisos del rol elegido.
- **Personas:** registro único con perfiles de alumno, docente y administrativo.
- **Programas académicos:** programas, especialidades, generaciones, periodos, planes académicos (asignaturas por semestre y módulos) y asignaturas.
- **Alumnos:** inscripción interna, situación ante la universidad por periodo con comprobantes, bajas, egresos e historial.
- **Docentes:** expediente con grados, certificaciones, experiencia, disponibilidad, especialidades y áreas de conocimiento.
- **Grupos y horarios:** grupos por periodo, materias, docentes y horario semanal con validación de choques (aula, docente y grupo).
- **Clínicas y espacios:** clínicas, aulas, laboratorios y consultorios.
- **Operación de clínicas:** áreas, supervisores (docentes responsables o supervisores, por área y vigencia), equipos (estado y mantenimiento), horario de atención de consultorios, sesiones clínicas (consultorio, grupo, supervisor, lista de asistencia; valida choques de consultorio, supervisor, clases y disponibilidad) e incidencias (tipo, gravedad, seguimiento, resolución y evidencias).
- **Seeders:** permisos, roles, menú, catálogos, parámetros, departamentos, especialidades, áreas y periodos.

## Módulos pendientes

Orden recomendado por dependencias:

| Orden | Módulo | Alcance |
|---|---|---|
| 1 | 6. Pacientes | Expediente, contactos de emergencia, antecedentes médicos y odontológicos, alergias, medicamentos, consultas y evolución |
| 2 | 7. Diagnósticos | Catálogo de diagnósticos, presuntivo/definitivo, revisión y autorización docente, odontograma |
| 3 | 9. Tratamientos | Planes, etapas, procedimientos, materiales utilizados y validación docente |
| 4 | 10. Agenda y citas | Agenda por clínica, confirmar, reprogramar, cancelar, inasistencias y recordatorios (usar consultorios, su horario de atención y las sesiones clínicas) |
| 5 | 11. Inventario y almacén | Artículos, proveedores, lotes y caducidad, existencias, movimientos, inventario físico y alertas |
| 6 | 12. Control administrativo y financiero | Conceptos de cobro, cuotas, adeudos, descuentos, pagos, gastos, caja y cortes |
| — | 5. Cursos y actividades académicas | Independiente; puede hacerse en cualquier momento |

## Pendientes de lo ya hecho

- [ ] Historial de actividades y de modificaciones (propuesta: `spatie/laravel-activitylog`).
- [ ] Activar y desactivar cuentas: la columna `users.is_active` existe, pero todavía no bloquea el inicio de sesión.
- [ ] Restablecer contraseñas desde el panel.
- [ ] Listados y reportes exportables (PDF / Excel).
- [ ] Contenido del Dashboard.

## Pendientes técnicos

- [x] Migraciones ejecutadas en la base local (incluida la de operación de clínicas) y seeders de permisos y menú actualizados.
- [ ] Probar en el navegador (sobre todo Clínicas, Sesiones clínicas e Incidencias).
- [ ] Decidir si el rol activo también debe restringir las rutas. Hoy solo filtra el menú; las rutas validan con la suma de los roles del usuario.
- [ ] Eliminar las columnas de texto que ya tienen tabla normalizada: `teacher_profiles.specialty`, `teacher_profiles.highest_degree` y `staff_profiles.department`.
- [ ] Renombrar `app/Models/people.php` a `People.php`; en Linux la clase no carga.
- [ ] Usar PHP 8.4 o superior en el PATH. Con 8.1 fallan `npm run dev` y `npm run build` (Wayfinder).
- [ ] Escribir tests. Ya existe `tests/Feature/Admin/ClinicOperationTest.php` (6 pruebas). Se corren con
  `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit` (el PHP 8.5 de Laragon no tiene `pdo_sqlite` activado en php.ini).
- [ ] Arreglar las pruebas del kit inicial que ya fallaban: usan la ruta `dashboard` (ahora es `admin.dashboard`) y `HomeController` usa `App\Models\Banner`, que no existe.
