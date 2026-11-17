# Pendientes del sistema

Sistema integral de gestión interna – Facultad de Odontología
Última actualización: 17 de noviembre de 2026

## Hecho

### Base del sistema
- **Administración general:** usuarios (con varios roles), roles, permisos, departamentos y coordinaciones, parámetros generales y catálogos.
- **Cuentas:** una cuenta desactivada no puede iniciar sesión y, si ya tenía sesión abierta, se cierra en la siguiente petición. Se registra el último acceso. Desde Usuarios se puede activar o desactivar una cuenta y enviar el enlace para restablecer la contraseña.
- **Selector de rol:** el menú lateral se arma con los permisos del rol elegido.
- **Panel (Dashboard):** indicadores según los permisos de cada usuario, por ejemplo:
  - alumnos y docentes activos, grupos del periodo,
  - citas, sesiones clínicas y cobros del día,
  - revisiones pendientes, incidencias abiertas,
  - artículos bajo el mínimo, lotes por caducar, adeudos vencidos, cuentas desactivadas.

  Además muestra las listas de citas y sesiones de hoy, las incidencias abiertas y los equipos con mantenimiento próximo o vencido.
- **Personas:** registro único con perfiles de alumno, docente, administrativo y paciente. Tiene búsqueda para selectores.
- **Seeders:** permisos, roles, menú, catálogos, parámetros, departamentos, especialidades, áreas y periodos.

### Académico
- **Programas académicos:** programas, especialidades, generaciones, periodos, planes académicos (asignaturas por semestre y módulos) y asignaturas.
- **Alumnos:** inscripción interna, situación ante la universidad por periodo con comprobantes, bajas, egresos e historial.
- **Docentes:** expediente con grados, certificaciones, experiencia, disponibilidad, especialidades y áreas de conocimiento.
- **Grupos y horarios:** grupos por periodo, materias, docentes y horario semanal con validación de choques (aula, docente y grupo).
- **Cursos y actividades académicas:**
  - cursos, seminarios, congresos, talleres, diplomados y conferencias;
  - ponentes de la facultad o externos;
  - inscritos (alumnos, docentes o externos nuevos) con control de cupo, asistencia, calificación y estado de pago;
  - materiales adjuntos;
  - constancias imprimibles con folio (`C-AAAA-00001`), solo para acreditados que cumplen la asistencia mínima.

### Clínicas
- **Clínicas y espacios:** clínicas, aulas, laboratorios y consultorios.
- **Operación de clínicas:**
  - áreas y supervisores;
  - equipos, con su estado y fechas de mantenimiento;
  - horario de atención de los consultorios;
  - sesiones clínicas con lista de asistencia (se valida que no choquen consultorio, supervisor y clases, y que el consultorio esté disponible);
  - incidencias con seguimiento, resolución y evidencias.

### Atención clínica
- **Pacientes:**
  - expediente con folio consecutivo (`P-000001`); si la CURP ya está registrada (p. ej. un alumno), se liga a esa persona;
  - contactos de emergencia, antecedentes por tipo, alergias (con aviso visible en el expediente y en el plan de tratamiento) y medicamentos;
  - consultas con signos vitales y exploración, y notas de evolución;
  - documentos clínicos adjuntos.
- **Diagnósticos:**
  - catálogo con códigos CIE-10 de padecimientos bucales;
  - diagnóstico presuntivo o definitivo, por diente y caras;
  - odontograma interactivo (dientes permanentes y temporales) con historial por diente.
- **Revisión docente:**
  - el docente autoriza, pide correcciones o rechaza diagnósticos, planes de tratamiento y procedimientos realizados;
  - página de "Revisiones pendientes" para cada docente;
  - cualquier cambio al registro hace que vuelva a requerir revisión.
- **Tratamientos:**
  - catálogo de procedimientos con costo sugerido;
  - planes por etapas, con procedimientos ligados al diagnóstico que atienden;
  - un procedimiento realizado queda pendiente de validación docente;
  - materiales usados: si se toman del inventario, se descuentan de la existencia.

### Agenda
- **Citas:**
  - vista semanal y lista con filtros;
  - confirmar, marcar como atendida, registrar inasistencia, cancelar con motivo y reprogramar (la cita original queda ligada a la nueva);
  - valida choques de consultorio, alumno y paciente, el horario de atención del consultorio y las clases programadas;
  - recordatorio por correo: manual desde la cita o automático con `appointments:send-reminders` (programado diario a las 09:00).

### Inventario
- **Artículos:** existencia por almacén y por lote con caducidad; las salidas usan primero lo que caduca antes y no se permite existencia negativa.
- **Movimientos:** entradas, salidas, ajustes, traspasos entre almacenes y consumo clínico, todos registrados en el kárdex.
- **Inventario físico:** se abre un conteo y, al cerrarlo, las existencias se ajustan a lo contado.
- **Catálogos y alertas:** almacenes (general y por clínica) y proveedores; alertas de artículos bajo el mínimo, lotes por caducar y lotes caducados.

### Finanzas
- **Conceptos y adeudos:**
  - conceptos y cuotas;
  - adeudos de alumnos y pacientes, con descuentos o becas autorizados y cancelación;
  - cuotas masivas a los alumnos activos (sin duplicar);
  - cargos al paciente por los procedimientos realizados.
- **Cobro:**
  - estado de cuenta por alumno o paciente;
  - pagos con folio (`R-000001`) aplicados a uno o varios adeudos;
  - recibo imprimible;
  - cancelación de pagos, que conservan su folio.
- **Gastos:** con comprobante adjunto.
- **Cajas y cortes:** apertura con fondo; el efectivo exige caja abierta. El corte muestra lo esperado contra lo contado. Un corte cerrado ya no se modifica.

## Pendientes

### Reportes e historial de cambios (en curso)
- [ ] **Historial de cambios** (quién creó, modificó o eliminó qué y cuándo). Se hace con tabla y *trait* propios, sin `spatie/laravel-activitylog`.
  - Ya están `activity_log` (migración), `App\Models\ActivityLog` y `App\Models\Concerns\LogsActivity`.
  - Falta correr la migración, agregar el *trait* a los modelos principales, la pantalla de consulta en Configuración, el permiso `activity-log.view` y las pruebas.
- [ ] **Reportes exportables** con vista previa en pantalla y descarga en PDF y Excel:
  - alumnos, pacientes, citas, sesiones clínicas, incidencias;
  - procedimientos realizados por alumno;
  - existencias, movimientos;
  - pagos, adeudos, gastos;
  - participantes de actividades.
  - Ya se instaló `dompdf/dompdf` para los PDF. Excel saldrá como CSV (UTF-8), porque el PHP de Laragon no tiene `ext-zip` y sin ella no funcionan PhpSpreadsheet ni OpenSpout.

### Pendientes técnicos
- [ ] Probar todo en el navegador. Hay pruebas automáticas, pero no se ha revisado en pantalla.
- [ ] Hacer commit: nada de lo del 17 de noviembre está en git.
- [ ] Decidir si el rol activo también debe restringir las rutas. Hoy solo filtra el menú; las rutas validan con la suma de los roles del usuario.
- [ ] Eliminar las columnas de texto que ya tienen tabla normalizada: `teacher_profiles.specialty`, `teacher_profiles.highest_degree` y `staff_profiles.department`.
- [ ] Renombrar `app/Models/people.php` a `People.php`; en Linux la clase no carga.
- [ ] Usar PHP 8.4 o superior en el PATH. Con 8.1 fallan `npm run dev` y `npm run build` (Wayfinder). Mientras, usar `C:\laragon\bin\php\php-8.5.7-nts-Win32-vs17-x64\php.exe`.
- [ ] En el servidor:
  - configurar el cron de `php artisan schedule:run` (recordatorios de citas);
  - configurar el correo (`MAIL_*`) para los recordatorios y el restablecimiento de contraseñas.
- [ ] Arreglar las pruebas del kit inicial que ya fallaban: usan la ruta `dashboard` (ahora es `admin.dashboard`) y `HomeController` usa `App\Models\Banner`, que no existe.

### Pruebas
- Hay 38 pruebas en `tests/Feature/Admin/`, todas pasando. Cubren cuentas y Panel, clínicas, pacientes, diagnósticos y revisiones, tratamientos, citas, inventario, finanzas y actividades.
- Se corren con `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit tests/Feature/Admin`. El PHP 8.5 de Laragon no tiene `pdo_sqlite` en php.ini, y `artisan test` no pasa el `-d`.

### Ideas para después
- Historial de mantenimiento de equipos: se descartó por ahora; con las fechas de último y próximo mantenimiento alcanza.
- Cobro automático de inscripciones a cursos: hoy el estado de pago del participante se marca a mano, porque los adeudos solo se ligan a alumnos y pacientes.
