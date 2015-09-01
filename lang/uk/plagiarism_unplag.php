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

$string['pluginname'] = 'Плагін системи UNPLAG для пошуку плагіату';
$string['studentdisclosuredefault']  = 'Всі завантажені файли будуть відправлені до системи пошуку плагіату в систему UNPLAG.';
$string['studentdisclosure'] = 'Ознайомити студентів с політикою конфіденційності';
$string['studentdisclosure_help'] = 'Цей текст буде відображатись для всіх студентів на сторінці для завантаження файлів.';
$string['unplag'] = 'Плагін UNPLAG, для пошуку плагіату';
$string['unplag_client_id'] = 'Client ID';
$string['unplag_client_id_help'] = 'ID користувача, надане UNPLAG для доступа до API. Значення параметру можна знайти тут: <a href="https://unplag.com/profile/apisettings">https://unplag.com/profile/apisettings</a>';
$string['unplag_lang'] = 'Мова';
$string['unplag_lang_help'] = 'Код мови в системі UNPLAG';
$string['unplag_api_secret'] = 'API Secret';
$string['unplag_api_secret_help'] = 'API SecretAPI наданий UNPLAG для доступу к API. Значення параметру можна знайти тут: <a href="https://unplag.com/profile/apisettings">https://unplag.com/profile/apisettings</a>. Значение параметра можно найти тут: <a href="https://unplag.com/profile/apisettings">https://unplag.com/profile/apisettings</a>';
$string['useunplag'] = 'Використовувати систему UNPLAG';
$string['unplag_enableplugin'] = 'Використовувати UNPLAG для {$a}';
$string['savedconfigsuccess'] = 'Налаштування збережені';
$string['savedconfigfailed'] = 'Помилка при збереженні налаштувань';
$string['unplag_show_student_score'] = 'Показувати студентам кількісний показник плагіату';
$string['unplag_show_student_score_help'] = 'Кількісний показник плагіату - це відсоток збігів в работі студента і текстами з других джерел.';
$string['unplag_show_student_report'] = 'Показувати студенту звіт перевірки на плагіат';
$string['unplag_show_student_report_help'] = 'Звіт по плагіату дозволяє отримати маску плагіату по частинах тексту работи, які являются неоригінальними, і також оригинальным частями, які знайшов UNPLAG.';
$string['unplag_draft_submit'] = 'Коли потрібно відправити(відправляти) файл до системи UNPLAG';
$string['showwhenclosed'] = 'Коли завдання закрито';
$string['submitondraft'] = 'Відправити файл при першому завантаженні';
$string['submitonfinal'] = 'Відправляти файл на перевірку, коли студент відправляє роботу для оцінювання';
$string['defaultupdated'] = 'Оновлені значення за замовчуванням';
$string['defaultsdesc'] = 'Дані налаштування будуть показані за замовчуванням при підключенні до системи UNPLAG, під час створення завдання.';
$string['unplagdefaults'] = 'Налаштування системи UNPLAG за замовчуванням';
$string['similarity'] = 'Плагіат';
$string['processing'] = 'Цей файл був відправлений у систему UNPLAG і очікує перевірки.';
$string['pending'] = 'Відправка цього файлу в систему UNPLAG знаходиться в режимі очікування.';
$string['previouslysubmitted'] = 'Прийняті раніше в якості';
$string['report'] = 'Звіт';
$string['unknownwarning'] = 'При відправці цього файлу в систему UNPLAG сталася помилка';
$string['unsupportedfiletype'] = 'Данний тип файлу система UNPLAG не підтримує';
$string['toolarge'] = 'Розмір цього файлу занадто великий для відправки в систему UNPLAG';
$string['plagiarism'] = 'Потенційний плагіат';
$string['report'] = 'Подивитися повний звіт';
$string['progress'] = 'Документ перевіряється';
$string['unplag_studentemail'] = 'Надіслати звіт електронною поштою студенту';
$string['unplag_studentemail_help'] = 'Це дозволить відправити лист студенту по електронній пошті, після того як файл був перевірений, щоб повідомити йому про те, що звіт доступний.';
$string['studentemailsubject'] = 'Файл перевірений UNPLAG';
$string['studentemailcontent'] = 'Файл, який Ви прислали {$ a-> modulename} в {$ a-> coursename} уже був перевірений системою UNPLAG';

$string['filereset'] = 'Файл був надісланий для повторної перевірки в UNPLAG';
$string['noreceiver'] = 'Адресу одержувача не вказано';
$string['unplag:enable'] = 'Дозволити викладачу підключити / відключити UNPLAG в завданнях, які він створив.';
$string['unplag:resetfile'] = 'Дозволити викладачеві повторно відправити файл до системи UNPLAG після помилки';
$string['unplag:viewreport'] = 'Дозволити викладачеві переглянути повний звіт від системи UNPLAG';
$string['unplagdebug'] = 'Режим відладки';
$string['explainerrors'] = 'Ця сторінка відображає список файлів, які на даний момент перебувають у стані помилки. <br/> Коли файли будуть видалені на цій сторінці, вони не зможуть бути повторно завантажені і викладачі або студенти більше не зможуть переглянути помилки.';
$string['id'] = 'ID';
$string['name'] = 'Імя';
$string['file'] = 'Файл';
$string['status'] = 'Статус';
$string['module'] = 'Модуль';
$string['resubmit'] = 'Перевідправка';
$string['identifier'] = 'Ідентифікатор';
$string['fileresubmitted'] = 'Файл в черзі для повторної відправки';
$string['filedeleted'] = 'Файл був видалений з черги';
$string['cronwarning'] = 'Сценарій <a href="../../admin/cron.php">cron.php</a> не виконувався принаймні 30 хвилин. Роботу Cron потрібно відновити, щоб система UNPLAG функціонула правильно.';
$string['waitingevents'] = '{$a->countallevents} подій очікують запуску cron і {$a->countheld} файлів очікують перевідправки';
$string['deletedwarning'] = 'Цей файл не був знайдений - він міг бути видалений користувачем';
$string['heldevents'] = 'Очікують обробки';
$string['heldeventsdescription'] = 'Це події, які не були завершені при першій спробі і були поставлені в чергу для повторного виконання - тому виконання наступних дій неможливо, може знадобитися подальший аналіз. Деякі з цих дій не можуть мати відношення до системи UNPLAG.';
$string['unplagfiles'] = 'Файли UNPLAG';
$string['getscore'] = 'Отримайте кількісний показник';
$string['scorenotavailableyet'] = 'Цей файл ще не був перевірений системою UNPLAG';
$string['scoreavailable'] = 'Цей файл був перевірений UNPLAG і тепер звіт доступний';
$string['receivernotvalid'] = 'Адреса отримувача вказано неправильно';
$string['attempts'] = 'Зроблено спроб';
$string['refresh'] = 'Оновити сторінку для перегляду результатів';