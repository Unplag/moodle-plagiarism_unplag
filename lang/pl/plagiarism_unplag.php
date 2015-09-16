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
 * @authors     Dan Marsden <Dan@danmarsden.com>, Mikhail Grinenko <m.grinenko@p1k.co.uk>
 * @copyright 2014 Dan Marsden <Dan@danmarsden.com>, 
 * @copyright   UKU Group, LTD, https://www.unplag.com 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Wtyczka plagiatowa UNPLAG';
$string['studentdisclosuredefault']  = '"Wszelkie załadowane pliki będą przesłane do serwisu wykrywania plagiatów o nazwie UNPLAG';
$string['studentdisclosure'] = 'Ujawnienie dla studenta';
$string['studentdisclosure_help'] = 'Ten tekst wyświetli się wszystkim studentom na stronie ładowania plików.';
$string['unplag'] = 'UNPLAG plagiarism plugin';
$string['unplag_client_id'] = 'ID Klienta';
$string['unplag_client_id_help'] = 'Nazwa użytkownika dostarczona przez UNPLAG w celu uzyskania dostępu do API. <a href="https://unplag.com/profile/apisettings">https://unplag.com/profile/apisettings</a>';
$string['unplag_lang'] = 'Język';
$string['unplag_lang_help'] = 'Kod języka dostarczony przez UNPLAG';
$string['unplag_api_secret'] = 'API Secret';
$string['unplag_api_secret_help'] = 'Hasło dostarczone przez UNPLAG w celu uzyskania dostępu do API. <a href="https://unplag.com/profile/apisettings">https://unplag.com/profile/apisettings</a>';
$string['useunplag'] = 'Aktywuj UNPLAG';
$string['unplag_enableplugin'] = 'Aktywuj UNPLAG dla {$a}';
$string['savedconfigsuccess'] = 'Ustawienia Plagiatu Zapisane';
$string['savedconfigfailed'] = 'Została wpisana niepoprawna kombinacja nazwy użytkownika/hasła, UNPLAG został dezaktywowany, spróbuj ponownie.';
$string['unplag_show_student_score'] = 'Pokaż wynik podobieństwa studentowi';
$string['unplag_show_student_score_help'] = 'Wynik podobieństwa jest procentem treści, która pasuje do innej treści w bazie.';
$string['unplag_show_student_report'] = 'Pokaż raport podobieństwa studentowi';
$string['unplag_show_student_report_help'] = 'Raport podobieństwa pokazuje, które części treści zostały splagiatowane i lokalizację, gdzie UNPLAG po raz pierwszy zobaczył tą treść.';
$string['unplag_draft_submit'] = 'Kiedy pliki powinny być przesłane do UNPLAG';
$string['showwhenclosed'] = 'Kiedy Aktywność jest zakończona';
$string['submitondraft'] = 'Prześlij plik kiedy jest załadowany po raz pierwszy';
$string['submitonfinal'] = 'Prześlij plik kiedy student wysyła go do oceny';
$string['defaultupdated'] = 'Wartości domyślne zaktualizowane';
$string['defaultsdesc'] = 'Poniższe ustawienia są domyślnymi, kiedy aktywuje się UNPLAG w ramach Modułu Aktywności';
$string['unplagdefaults'] = 'Podstawowowe ustawienia UNPLAG';
$string['similarity'] = 'Podobieństwo';
$string['processing'] = 'Ten plik został wysłany do UNPLAG, teraz czeka na analizę, która będzie dostępna';
$string['pending'] = 'Ten plik jest w trakcie przesyłania do UNPLAG';
$string['previouslysubmitted'] = 'Poprzednio przesłany przez';
$string['report'] = 'raport';
$string['unknownwarning'] = 'Wystąpił błąd w czasie przesyłania tego pliku do UNPLAG';
$string['unsupportedfiletype'] = 'Ten typ pliku nie jest obsługiwany przez UNPLAG';
$string['toolarge'] = 'Ten plik jest za duży by UNPLAG go procesował';
$string['plagiarism'] = 'Potencjalny plagiat';
$string['report'] = 'Zobacz pełen raport';
$string['progress'] = 'Skan';
$string['unplag_studentemail'] = 'Wyśli mail do studenta';
$string['unplag_studentemail_help'] = 'To wyśle e-mail do studenta, kiedy plik został przeprocesowany, by dać mu znać, że dostępny jest raport.';
$string['studentemailsubject'] = 'Plik procesowany przez UNPLAG';
$string['studentemailcontent'] = 'Plik, który wysłałeś do {$a->modulename} w ramach {$a->coursename} został przeprocesowany przez narzędzie plagiatowe  UNPLAG
{$a->modulelink}';

$string['filereset'] = 'Plik został zresetowany do ponownego przesłania do UNPLAG';
$string['noreceiver'] = 'Nie określono adresu odbiorcy';
$string['unplag:enable'] = 'Pozwól nauczycielowi by aktywował/dezaktywował UNPLAG wewnątrz aktywności';
$string['unplag:resetfile'] = 'Pozwól nauczycielowi by przesłał ponownie plik do UNPLAG po wystąpieniu błędu';
$string['unplag:viewreport'] = 'Pozwól nauczycielowi by zobaczył pełen raport z UNPLAG';
$string['unplagdebug'] = 'Debugowanie';
$string['explainerrors'] = 'Ta strona listuje jakiekolwiek pliki, które są obecnie w stanie błędu.  <br/>Kiedy pliki zostaną usunięte na tej stronie, nie będą w stanie zostać przesłane do ponownego rozpatrzenia i błędy nie będą się już wyświetlać nauczycielom i studentom. ';
$string['id'] = 'ID';
$string['name'] = 'Nazwa';
$string['file'] = 'Plik';
$string['status'] = 'Status';
$string['module'] = 'Moduł';
$string['resubmit'] = 'Ponowne rozpatrzenie';
$string['identifier'] = 'Identyfikator';
$string['fileresubmitted'] = 'Plik Zakolejkowany do ponownego rozpatrzenia';
$string['filedeleted'] = 'Plik usunięty z kolejki';
$string['cronwarning'] = 'Skrypt utrzymania <a href="../../admin/cron.php">cron.php</a> nie działał przez przynajmniej 30 min - Cron musi być skonfigurowany by umożliwić  UNPLAG by działał poprawnie.';
$string['waitingevents'] = 'Istnieją {$a->countallevents} wydarzenia czekające na cron i{$a->countheld} wydarzenia, które skierowane są do ponownego rozpatrzenia ';
$string['deletedwarning'] = 'Nie można było odnaleźć pliku - mógł zostać usunięty przez użytkownika';
$string['heldevents'] = 'Odbywające się wydarzenia';
$string['heldeventsdescription'] = 'Są to wydarzenia, które nie zakończyły się w pierwszej próbie i zostały zakolejkowane ponownie - to uniemożliwia kolejnym wydarzeniom by zostały ukończone i mogą wymagać dalszego przyjrzenia się. Niektóre z tych wydarzeń mogą nie być zgodne z UNPLAG.';
$string['unplagfiles'] = 'Pliki Unplag';
$string['getscore'] = 'Otrzymaj wynik';
$string['scorenotavailableyet'] = 'Ten plik nie był jeszcze procesowany przez UNPLAG.';
$string['scoreavailable'] = 'Ten plik był procesowany przez UNPLAG i raport jest teraz dostępny.';
$string['receivernotvalid'] = 'Nie jest to poprawny adres odbiorcy';
$string['attempts'] = 'Liczba dokonanych prób';
$string['refresh'] = 'Odśwież stronę, aby zobaczyć wyniki';