# Pendientes del sistema

Sistema integral de gestión interna – Facultad de Odontología
Última actualización: 21 de noviembre de 2026

## Hecho

### Base del sistema
- **Administración general:** usuarios (con varios roles), roles, permisos, departamentos y coordinaciones, parámetros generales y catálogos.
- **Cuentas:** no hay registro público (`/register` está desactivado); las cuentas las crea Administración desde Usuarios. Una cuenta desactivada no puede iniciar sesión y, si ya tenía sesión abierta, se cierra en la siguiente petición. Se registra el último acceso. Desde Usuarios se puede activar o desactivar una cuenta y enviar el enlace para restablecer la contraseña.
- **Selector de rol:** el menú lateral y los permisos de las rutas se toman del rol elegido.
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
  - consultas con motivo, padecimiento actual, síntomas bucales (dolor, sensibilidad, encías y tejidos, otros), intensidad del dolor 0–10, signos vitales y exploración, y notas de evolución;
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

### Historial de cambios
- Registra quién creó, modificó, eliminó o restauró un registro, cuándo, desde qué IP y qué campos cambiaron (antes y después). Usa tabla y *trait* propios (`LogsActivity`), sin `spatie/laravel-activitylog`.
- Se aplica a usuarios, personas, inscripciones, docentes, clínicas, incidencias, sesiones clínicas, pacientes, diagnósticos, planes y procedimientos, citas, artículos y conteos de inventario, adeudos, pagos, gastos, turnos de caja y actividades académicas.
- No guarda contraseñas, tokens, campos ocultos ni el último acceso.
- Pantalla en Configuración → Historial de cambios (permiso `activity-log.view`), con filtros por tipo de registro, acción, usuario y fechas, y el historial completo de un registro.

### Reportes
- Menú Reportes → catálogo por sección. Cada reporte tiene filtros, indicadores, vista previa paginada con totales y descarga en **PDF** (dompdf, con encabezado institucional y número de página) y **Excel como CSV** (UTF-8 con BOM; los números van sin formato para que Excel los sume).
- 13 reportes: alumnos, participantes de actividades, pacientes, procedimientos realizados, procedimientos por alumno (productividad), sesiones clínicas, incidencias, citas, existencias, movimientos de inventario, pagos, adeudos y gastos.
- Permiso `reports.view` para el menú, y además cada reporte exige el permiso de consulta de su módulo (p. ej. `payments.view`).
- Periodo con atajos (hoy, semana, mes, mes pasado, año, todo). Por omisión se usa el mes en curso en los reportes de movimientos.
- El PDF llega hasta 2,000 renglones y avisa si hay más; el CSV no tiene límite.
- Para agregar un reporte: crear una clase en `app/Reports/Definitions` y registrarla en `App\Reports\ReportRegistry`.
- Las etiquetas de estados viven en `resources/js/lib/labels.json`, que comparten el frontend y PHP (`App\Support\Labels`).

### Rol activo y permisos
- El rol elegido en el selector restringe también las rutas, no solo el menú. Los permisos heredados se toman solo del rol activo y SuperAdmin aplica solo cuando es el rol activo. Los permisos asignados directo al usuario siguen aplicando.
- Se corrigió un error en las rutas `Route::resource`: `->middleware([...])` exigía los cuatro permisos (ver, crear, editar, eliminar) en todas las acciones. Por ejemplo, un alumno no podía abrir la lista de pacientes por no tener `patients.delete`. Ahora se usa `->permissions([...])`, un *macro* que aplica un permiso por acción.
- Los adjuntos validan el permiso del módulo del registro: ver para descargar, crear o editar para adjuntar, editar o eliminar para borrar.

### Requerimientos completados el 19 de noviembre de 2026
Se compararon contra el documento "Desglose de actividades por módulo" (el número es el del requerimiento) y ya están todos en el código:

- **1.10 Bitácora de accesos:** inicios y cierres de sesión, e intentos fallidos, con IP y dispositivo. Está en Configuración → Historial de cambios → pestaña "Accesos al sistema".
- **2.13 Fotografía del alumno:** se sube desde su expediente y se guarda en el disco privado. El componente `PersonPhoto` se puede reutilizar para docentes y pacientes.
- **4.12 Participación del docente:** actividades que coordinó o en las que fue ponente, clínicas que supervisa, sesiones supervisadas, citas supervisadas y procedimientos validados.
- **5.5, 5.6, 5.13 y 5.15 Actividades:** tipo "jornada académica", objetivos, evaluación interna de 0 a 10 con comentarios, y resultados y observaciones.
- **6.13 Pacientes por alumno o especialidad:** "atendido por" quiere decir que el alumno aparece en una cita, consulta, plan o procedimiento del paciente. La especialidad es la del programa del alumno.
- **7.2 Síntomas en la consulta:** padecimiento actual, síntomas bucales e intensidad del dolor.
- **7.5 y 9.4 Especialidad en el catálogo de diagnósticos y de procedimientos**, con filtro.
- **8.2 Áreas de clínica ligadas a una especialidad.**
- **8.4 Equipo o instrumental** en los equipos de la clínica.
- **8.13 Historial de uso del consultorio:** últimos 90 días de sesiones, citas, actividades e incidencias, en la vista del espacio.
- **9.13 Indicaciones para el paciente** en el plan de tratamiento y en cada procedimiento.
- **10.12, 10.13 y 10.14 Agenda:** vistas de día, semana, mes y lista; botón "Citas de hoy"; filtro por docente supervisor.
- **11.2 y 11.4 Inventario:** tipo de artículo (material, instrumental o equipo) y categorías tomadas del catálogo "Categorías de inventario".
- **11.11 Almacenes de laboratorio:** un almacén se liga a un laboratorio; el material se le asigna con un traspaso.
- **11.12, 11.14, 11.15 y 11.17 Movimientos de inventario:** entrega a un alumno, devolución (puede regresar a su lote original), merma o pérdida, y producto dañado. El kárdex se filtra por alumno.
- **12.4 y 12.9 Cobros sin adeudo, capturados a mano:**
  - en Pagos, el botón "Otro ingreso" sirve para externos, donativos, laboratorio, etc.;
  - en la actividad, el botón "Cobrar" junto a cada participante con pago pendiente;
  - sale recibo con folio, entra al corte de caja y el participante queda como pagado;
  - si el pago se cancela, el participante vuelve a "pendiente".
- **12.6 Trabajos de laboratorio:** categoría "laboratorio" en conceptos de cobro.
- **12.11 Gastos de laboratorio:** un gasto se liga a un laboratorio.
- **12.12 Movimientos de caja:** depósitos y retiros de efectivo en el turno; entran al efectivo esperado del corte. No se permite retirar más de lo que hay.

**Regla de cobro:** nada se cobra en automático. Una persona cobra y lo registra en el sistema.

### Horarios de grupo (21 de noviembre de 2026)
- **Disponibilidad del docente:** si el docente registró su disponibilidad (general o del periodo), cada clase debe caber completa en uno de sus bloques. El error dice qué horario tiene ese día. Si no registró ninguna para el periodo, se le puede programar a cualquier hora.
- **Cambio de docente de una materia que ya tiene horario:** ahora revisa que el nuevo docente no tenga otra clase a esa hora y que esté disponible. Antes no revisaba nada.
- **Horario imprimible en PDF** (carta horizontal, una hoja, con resumen de horas por materia):
  - del grupo, desde su vista;
  - del docente, para el periodo que se elija;
  - del espacio, para el periodo que se elija.

  Botón "Imprimir horario" en cada vista. El código está en `SchedulePrintController`, `App\Support\WeeklyScheduleGrid` y `resources/views/schedules/pdf.blade.php`.
- **Expediente del docente:** el horario de clases tiene selector de periodo (por omisión, el vigente), igual que Espacios. Antes juntaba en una sola semana las clases de todos los periodos.
- **`App\Support\PdfRenderer`:** genera los PDF con dompdf y número de página. Lo usan los reportes y los horarios.
- Pruebas en `tests/Feature/Admin/ClassScheduleTest.php` (14): choques de aula, docente y grupo, periodos distintos, edición, disponibilidad, cambio de docente, periodo en el expediente del docente, los PDF y sus permisos.

### Sitio público (en curso, 19 de noviembre de 2026)
Páginas de varias secciones con información real del sistema (se actualizan solas al registrar datos). Solo muestran información pública: nunca pacientes, alumnos ni participantes.

- **`/` Inicio:** portada, cifras, oferta académica por nivel, próximos cursos y actividades recientes, especialidades, atención clínica (pasos y clínicas), la facultad y contacto.
- **`/oferta-academica`:** programas por nivel (licenciatura, especialidades, maestrías, doctorados y diplomados).
- **`/oferta-academica/{clave}`:** ficha del programa con presentación, objetivos, competencias, perfiles de ingreso y egreso, plan de estudios por periodo (asignaturas, créditos, horas, optativas) y programas relacionados.
- **`/cursos-y-actividades`** (con filtro por tipo) y **`/cursos-y-actividades/{id}`:** ficha con fechas, lugar, cupo disponible, costo, constancia, ponentes y cómo inscribirse. Las canceladas no se muestran.
- **`/atencion-clinica`:** cómo se atiende (pasos), servicios agrupados por especialidad (catálogo de procedimientos, sin costos), clínicas con áreas y consultorios, y qué llevar a la primera cita.
- Layout público compartido (`resources/js/layouts/PublicLayout.vue`) con menú para celular. Los datos de la facultad están en `resources/js/lib/institution.ts`.
- Se agregó el tipo **licenciatura** a los programas académicos.
- Pruebas en `tests/Feature/PublicSiteTest.php`.

## Pendientes

### Sitio público: lo que falta
- [ ] **Revisarlo en el navegador.** Compila y tiene pruebas, pero no se ha visto en pantalla.
- [ ] **Capturar el contenido real** en el sistema: programas (con descripción, duración, objetivos, perfiles y plan de estudios activo), especialidades con descripción, clínicas con ubicación, descripción y áreas, y actividades con descripción. Sin datos, cada sección muestra un mensaje de "próximamente".
- [ ] **Verificar los textos fijos con la facultad:** el mensaje de la portada, los pasos de "¿Cómo me atienden?" y la lista de "¿Qué debo llevar?" (`resources/js/pages/public/Clinical.vue`) son una propuesta y no vienen de una fuente oficial.
- [ ] **Contenido de demostración (opcional):** `DemoPublicContentSeeder` crea programas, un plan de estudios, clínicas y actividades de ejemplo (todos marcados "(demo)" o con clave `DEMO-`) para ver el sitio con datos. No está en `DatabaseSeeder` y no se ha corrido. Crear: `php artisan db:seed --class=DemoPublicContentSeeder`. Borrar: `php artisan tinker --execute="Database\Seeders\DemoPublicContentSeeder::remove();"`. **No correrlo en producción.**
- [ ] **Ideas que quedaron fuera:** horario de atención de cada clínica, galería de fotos de las instalaciones, noticias o avisos, y formulario de contacto o de preinscripción en línea (hoy todo se dirige al correo y al teléfono de la facultad).

### Falta decidir o hacer
- [ ] **Probar todo en el navegador.** Hay pruebas automáticas de todo, pero las pantallas nuevas no se han revisado a mano. Lo más nuevo es la agenda de día y mes, los cobros "Otro ingreso" y "Cobrar", y las operaciones nuevas de inventario.
- [ ] **Foto de la fachada en alta resolución.** La página de inicio (`/`) y el inicio de sesión ya tienen la identidad de la FAOD/UJED (guinda, logo UJED, datos de la facultad). Por ahora usan la panorámica del campus que publica la UJED (1800×300 px), que en la mitad vertical del inicio de sesión se ve borrosa. Pon una foto vertical de la fachada en `public/images/faod/` y cambia la ruta en `resources/js/lib/institution.ts`. Si la facultad tiene su propio logo, también va ahí.
- [ ] **Errores de TypeScript del kit inicial:** quedan 20 en componentes de Laravel que no se tocaron (`AppHeader`, `NavMain`, `NavFooter`, `NavUser`, `settings/Layout`, `settings/Profile`). No impiden compilar; el código de la facultad no tiene errores.
- [ ] **Usar PHP 8.4 o superior en el PATH.** Con 8.1 fallan `npm run dev` y `npm run build` (Wayfinder). Mientras, usar `C:\laragon\bin\php\php-8.5.7-nts-Win32-vs17-x64\php.exe`.
- [ ] **En el servidor:**
  - correr `php artisan migrate` y los seeders `RolesAndPermissionsSeeder`, `MenuSeeder` y `CatalogSeeder`;
  - configurar el cron de `php artisan schedule:run` (recordatorios de citas);
  - configurar el correo (`MAIL_*`) para los recordatorios y el restablecimiento de contraseñas.

### Limpieza técnica (19 de noviembre de 2026)
- **Restos de Husky eliminados:** componentes, imágenes, el correo de cotizaciones y hojas de estilo sin uso. Los íconos (favicon, ícono de celular), el manifiesto, la imagen para compartir (`public/images/faod/og-faod.jpg`) y `config/seo.php` (sitemap y `llms.txt`) ahora son de la FAOD.
- **`app/Models/people.php` se renombró a `People.php`**, así que la clase ya carga en Linux.
- **Columnas viejas eliminadas:** `teacher_profiles.specialty`, `teacher_profiles.highest_degree` y `staff_profiles.department`. La migración pasa antes sus datos a "Títulos y grados", a las especialidades del docente y a `department_id`. El grado máximo ya no se captura en Personas, sino en el expediente del docente.
- **Folio del paciente:** el consecutivo ahora sale del último folio, no del `id` (con saltos de `id` daba folios no consecutivos).
- **Existencia de inventario** siempre como número, también en MySQL.
- Las 105 pruebas pasan tanto en SQLite como en MySQL.

### Pruebas
- Hay 67 pruebas en `tests/Feature/Admin/`, todas pasando. Cubren cuentas y Panel, rol activo y permisos, clínicas, pacientes, diagnósticos y revisiones, tratamientos, citas, inventario, finanzas, actividades, historial de cambios, reportes y los requerimientos completados (`RequirementsGapTest`).
- Con las del kit inicial, las del sitio público y las de horarios son 124 pruebas, todas pasando (21-nov, en SQLite).
- Para correrlas contra MySQL: crear una base vacía y usar `DB_CONNECTION=mysql DB_DATABASE=<base> php vendor/phpunit/phpunit/phpunit`.
- Se corren con `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit tests/Feature/Admin`. El PHP 8.5 de Laragon no tiene `pdo_sqlite` en php.ini, y `artisan test` no pasa el `-d`.

### Ideas para después
- Historial de mantenimiento de equipos: se descartó por ahora; con las fechas de último y próximo mantenimiento alcanza.
