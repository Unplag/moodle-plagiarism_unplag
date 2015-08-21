<?php
// Este archivo es parte de Moodle - http://moodle.org/
//
// Moodle es software libre: usted lo puede redistribuir y/o modificar 
// bajo las condiciones de la Licencia Pública General de GNU como está publicado en 
// la Fundación para el software libre, en su versión 3 de la Licencia, o
// (a su decisión) cualquier versión más reciente.
//
// Moodle está distribuido en espera de que va a ser útil,
// pero  SIN NINGUNA GARANTÍA; ni siquiera la garantía implícita de
// COMERCIALIZACIÓN o IDONEIDAD PARA UN PROPÓSITO PARTICULAR.  Ver 
// la Licencia Pública General de GNU para más detalles.
//
// Usted debería recibir una copia de la Licencia Pública General de GNU
// juntos con Moodle.  De lo contrario, vea <http://www.gnu.org/licenses/licenses.es.html>.

/**
 *
 * @package   plagiarism_unplag
 * @author     Dan Marsden <dan@danmarsden.com>
 * @copyright  2011 Dan Marsden http://danmarsden.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 o mas reciente
 */

$string['pluginname'] = 'UNPLAG plugin antiplagio';
$string['studentdisclosuredefault']  = 'Todos archivos subidos serán comprobados por el servicio de detección de plagio UNPLAG,
si no desea que su archivo sea utilizado como fuente para análisis fuera de este sitio puede seleccionar la opción de enlace de excluir proporcionado después de que el Reporte se ha generado.';
$string['studentdisclosure'] = 'Notificar al estudiante';
$string['studentdisclosure_help'] = 'Este texto será mostrado a todos los estudiantes en la página de comprobación del archivo.';
$string['unplag'] = 'UNPLAG plugin antiplagio';
$string['unplag_api'] = 'UNPLAG Dirección de integración';
$string['unplag_api_help'] = 'Esta es la dirección de UNPLAG API <a href="https://unplag.com/profile/apisettings">https://unplag.com/profile/apisettings</a>';
$string['unplag_client_id'] = 'ID de cliente';
$string['unplag_client_id_help'] = 'Nombre de usuario proporcionado por UNPLAG para acceder al  API <a href="https://unplag.com/profile/apisettings">https://unplag.com/profile/apisettings</a>';
$string['unplag_lang'] = 'Idioma';
$string['unplag_lang_help'] = 'Codigo de idioma proporcionado por UNPLAG';
$string['unplag_api_secret'] = 'API Secret';
$string['unplag_api_secret_help'] = 'Contraseña proporcionada por UNPLAG para acceder al API';
$string['useunplag'] = 'UNPLAG Activado';
$string['unplag_enableplugin'] = 'UNPLAG activado para {$a}';
$string['savedconfigsuccess'] = 'Ajustes antiplagio guardados';
$string['savedconfigfailed'] = 'Se ha ingresado combinación incorrecta de nombre de usuario/contraseña, UNPLAG fue desactivado, por favor vuelve a intentar.';
$string['unplag_show_student_score'] = 'Mostrar taza de similitud al estudiante';
$string['unplag_show_student_score_help'] = 'La taza de similitud es el porcentaje del contenido que coincide con el contenido de otras fuentes.';
$string['unplag_show_student_report'] = 'Mostrar reporte de similitud al estudiante.';
$string['unplag_show_student_report_help'] = 'Reporte de similitud proporciona la lista sobre las partes de la información que fueron plagiados y las fuentes donde el UNPLAG ha encontrado este contenido por primera vez';
$string['unplag_draft_submit'] = 'Cuando el archivo debe de ser comprobado por UNPLAG';
$string['showwhenclosed'] = 'Cuando se cierra la Actividad';
$string['submitondraft'] = 'Comprobar cada vez que se sube archivo';
$string['submitonfinal'] = 'Comprobar cundo estudiante pone documento a comprobar';
$string['unplag_receiver'] = 'Dirección de recipiente';
$string['unplag_receiver_help'] = 'Es la única dirección proporcionada por UNPLAG para los profesores';
$string['defaultupdated'] = 'Los datos predeterminados han sido actualizados';
$string['defaultsdesc'] = 'Los siguientes ajustes son predeterminados cuando está activado el Módulo de Actividades UNPLAG';
$string['unplagdefaults'] = 'UNPLAG ajustes predeterminados';
$string['similarity'] = 'UNPLAG';
$string['processing'] = 'Este archivo se ha comprobado con UNPLAG, esperando para que el análisis sea viable';
$string['pending'] = 'Archivo esperando de ser comprobado por UNPLAG';
$string['previouslysubmitted'] = 'Comprobado previamente como';
$string['report'] = 'reporte';
$string['unknownwarning'] = 'Se ha ocurrido un fallo al intentar de enviar este archivo al UNPLAG';
$string['unsupportedfiletype'] = 'Tipo de archivo no soportado por UNPLAG';
$string['toolarge'] = 'Este archivo es demasiado grande para ser procesado con UNPLAG';
$string['plagiarism'] = 'Probabilidad de plagio';
$string['report'] = 'Ver reporte completo';
$string['progress'] = 'Comprobando';
$string['unplag_studentemail'] = 'Enviar correo al estudiante';
$string['unplag_studentemail_help'] = 'Se enviará un correo al estudiante de que su archivo ha sido procesado y que un reporte está disponible. El correo también incluye el enlace de excluir.';
$string['studentemailsubject'] = 'Archivo procesado por UNPLAG';
$string['studentemailcontent'] = 'El archivo enviado a comprobación a {$a->modulename} en {$a->coursename} está procesando por detector de plagio UNPLAG.
{$a->modulelink}';

$string['filereset'] = 'Archivo fue reseteado para reenvío al  UNPLAG';
$string['noreceiver'] = 'No hay dirección de destinatario';
$string['unplag:enable'] = 'Permitir a los profesores de activar/desactivar UNPLAG para sus actividades';
$string['unplag:resetfile'] = 'Permitir al profesor de volver a comprobar un archivo con UNPLAG después de un error.';
$string['unplag:viewreport'] = 'Permitir a los profesores de ver reporte completo de UNPLAG';
$string['unplagdebug'] = 'Debugging';
$string['explainerrors'] = 'Esta página muestra todos los archivos que se encuentran en el estado de error.<br/>Cuando los archivos son eliminados en esta página, no podrán ser recomprobados y los errores no se mostrarán a los profesores o estudiantes';
$string['id'] = 'ID';
$string['name'] = 'Nombre';
$string['file'] = 'Archivo';
$string['status'] = 'Estado';
$string['module'] = 'Módulo';
$string['resubmit'] = 'Recomprobar';
$string['identifier'] = 'Identificador';
$string['fileresubmitted'] = 'Archivo puesto en la fila para recomprobación';
$string['filedeleted'] = 'Archivo eliminado de la fila';
$string['cronwarning'] = 'La <a href="../../admin/cron.php">cron.php</a> secuencia de mantenimiento no ha sido arrancada por lo menos 30minutos – Cron tiene que ser configurado para permitir a UNPLAG de funcionar correctamente.';
$string['waitingevents'] = 'Hay {$a->countallevents} eventos esperando el Cron y {$a->countheld} eventos que estaban aplazados por recomprobación';
$string['deletedwarning'] = 'Archivo no puede ser encontrado – probablemente fue eliminado por el usuario';
$string['heldevents'] = 'Eventos aplazados';
$string['heldeventsdescription'] = 'Estos son eventos que no se completaron en el primer intento y se pusieron en la fila para ser recomprobados – esto impide los acontecimientos posteriores de comprobar y pueden necesitar una investigación más detallada. Algunos de estos eventos pueden no ser relevantes para UNPLAG.';
$string['unplagfiles'] = 'Archivos Unplag';
$string['getscore'] = 'Obtener puntuación';
$string['scorenotavailableyet'] = 'Este archivo no fue procesado por UNPLAG todavía.';
$string['scoreavailable'] = 'Este archivo fue procesado por UNPLAG y el Reporte está disponible.';
$string['receivernotvalid'] = 'Esto no es la dirección de destinatario válida.';
$string['attempts'] = 'Intentos hechos';