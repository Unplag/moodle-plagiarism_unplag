<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package   plagiarism_unplag
 * @author     Mikhail Grinenko <m.grinenko@p1k.co.uk>
 * @copyright  UKU Group, LTD, https://www.unplag.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Плагин UNPLAG для поиска плагиата';
$string['studentdisclosuredefault']  = 'Все загруженные файлы будут отправлены в систему поиска плагиата UNPLAG.';
$string['studentdisclosure'] = 'Ознакомить студентов с политикой конфиденциальности';
$string['studentdisclosure_help'] = 'Этот текст будет отображаться для всех студентов на странице для загрузки файлов.';
$string['unplag'] = 'Плагин UNPLAG для поиска плагиата';
$string['unplag_client_id'] = 'Client ID';
$string['unplag_client_id_help'] = 'ID пользователя, предоставленное UNPLAG для доступа к API. Значение параметра можно найти тут: <a href="https://unplag.com/profile/apisettings">https://unplag.com/profile/apisettings</a>';
$string['unplag_lang'] = 'Язык';
$string['unplag_lang_help'] = 'Language code provided by UNPLAG';
$string['unplag_api_secret'] = 'API Secret';
$string['unplag_api_secret_help'] = 'API SecretAPI предоставленный UNPLAG для доступа к API. Значение параметра можно найти тут: <a href="https://unplag.com/profile/apisettings">https://unplag.com/profile/apisettings</a>. Значение параметра можно найти тут: <a href="https://unplag.com/profile/apisettings">https://unplag.com/profile/apisettings</a>';
$string['useunplag'] = 'Использовать UNPLAG';
$string['unplag_enableplugin'] = 'Использовать UNPLAG для {$a}';
$string['savedconfigsuccess'] = 'Настройки сохранены';
$string['savedconfigfailed'] = 'Ошибка при сохранении настроек';
$string['unplag_show_student_score'] = 'Показывать студентам количественный показатель плагиата';
$string['unplag_show_student_score_help'] = 'Количественный показатель плагиата - это процент совпадений в работе студента и текстами других источников.
Количественный показатель плагиата - это процент совпадений в работе студента и текстами других источников.';
$string['unplag_show_student_report'] = 'Показывать студенту отчет проверки на плагиат';
$string['unplag_show_student_report_help'] = 'Отчет по плагиатупозволяет получить маску плагиата по частям текста работы, которые являются неоригинальными, а также оригинальным частями, которые нашел UNPLAG.';
$string['unplag_draft_submit'] = 'Когда следует отправлять файл в UNPLAG';
$string['showwhenclosed'] = 'Когда задание закрыто';
$string['submitondraft'] = 'Отправить файл при первой загрузке';
$string['submitonfinal'] = 'Отправлять файл на проверку, когда студент отправляет работу для оценивания';
$string['defaultupdated'] = 'Обновленные значения по умолчанию';
$string['defaultsdesc'] = 'Данные настройки будут показаны по умолчанию при подключении UNPLAG во время создания задачи.';
$string['unplagdefaults'] = 'Настройка UNPLAG по умолчанию';
$string['similarity'] = 'Плагиат';
$string['processing'] = 'Этот файл был отправлен в UNPLAG и ожидает проверки.';
$string['pending'] = 'Отправка этого файла в UNPLAG находится в режиме ожидания.';
$string['previouslysubmitted'] = 'Присланные ранее в качестве';
$string['report'] = 'Отчет';
$string['unknownwarning'] = 'При отправке этого файла в UNPLAG произошла ошибка';
$string['unsupportedfiletype'] = 'Этот тип файла UNPLAG не поддерживает';
$string['toolarge'] = 'Размер этого файла слишком большой для UNPLAG';
$string['plagiarism'] = 'Потенциальный плагиат';
$string['report'] = 'Посмотреть полный отчет';
$string['progress'] = 'Проверка';
$string['unplag_studentemail'] = 'Отправить по электронной почте студенту';
$string['unplag_studentemail_help'] = 'Это позволит отправить письмо студенту по электронной почте, после того как файл был проверен, чтобы сообщить ему о том, что отчет доступен.';
$string['studentemailsubject'] = 'Файл проверен UNPLAG';
$string['studentemailcontent'] = 'Файл, который Вы прислали {$ a-> modulename} в {$ a-> coursename} уже был проверен системой UNPLAG';

$string['filereset'] = 'Файл был отправлен для повторной проверки в UNPLAG';
$string['noreceiver'] = 'Адрес получателя не указан';
$string['unplag:enable'] = 'Позволить преподавателю подключить / отключить UNPLAG в заданиях, которые он создал.';
$string['unplag:resetfile'] = 'Позволить преподавателю повторно отправить файл в UNPLAG после ошибки';
$string['unplag:viewreport'] = 'Позволить преподавателю просмотреть полный отчет от UNPLAG';
$string['unplagdebug'] = 'Режим отладки';
$string['explainerrors'] = 'Эта страница предоставляет список файлов, которые в настоящее время находятся в состоянии ошибки. <br/> Когда файлы будут удалены, на этой странице они не смогут быть повторно загружены и преподаватели или студенты больше не смогут просмотреть ошибки.';
$string['id'] = 'ID';
$string['name'] = 'Имя';
$string['file'] = 'Файл';
$string['status'] = 'Статус';
$string['module'] = 'Модуль';
$string['resubmit'] = 'Переотправка';
$string['identifier'] = 'Идентификатор';
$string['fileresubmitted'] = 'Файл в очереди для повторной отправки';
$string['filedeleted'] = 'Файл был удален из очереди';
$string['cronwarning'] = 'Сценарий <a href="../../admin/cron.php">cron.php</a> не запускался по крайней мере 30 минут - Работу Cron нужно восстановить, чтобы UNPLAG функционировал правильно.';
$string['waitingevents'] = '{$a->countallevents} событий ожидающих запуска cron и {$a->countheld} файлов ожидают переотправки';
$string['deletedwarning'] = 'Этот файл не был найден - он мог быть удален пользователем';
$string['heldevents'] = 'Ожидающие события';
$string['heldeventsdescription'] = 'Это события, которые не были завершены при первой попытке и были поставлены в очередь для повторного выполнения - поэтому  выполнение следующих действий невозможно, может потребоваться дальнейший анализ. Некоторые из этих действий не могут иметь отношение к UNPLAG.';
$string['unplagfiles'] = 'Файлы UNPLAG';
$string['getscore'] = 'Получите количественный показатель';
$string['scorenotavailableyet'] = 'Этот файл еще не был проверен UNPLAG';
$string['scoreavailable'] = 'Этот файл был проверен UNPLAG и теперь отчет доступен';
$string['receivernotvalid'] = 'Адрес получателя указан неправильно';
$string['attempts'] = 'Сделано попыток';
$string['refresh'] = 'Обновите страницу для просмотра результатов';