<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                      Online Judge for Moodle                          //
//        https://github.com/hit-moodle/moodle-local_onlinejudge         //
//                                                                       //
// Copyright (C) 2009 onwards  Sun Zhigang  http://sunner.cn             //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Strings for local_onlinejudge
 * 
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @translator Janisz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['about'] = 'O';
$string['badvalue'] = 'Zła wartość';
$string['cannotrunsand'] = 'Nie można uruchomić sprawdzaczki';
$string['compileroutput'] = 'Wyjście kompilatora';
$string['cpuusage'] = 'Użycie CPU';
$string['defaultlanguage'] = 'Domyślny język';
$string['defaultlanguage_help'] = 'Domyślny język ustawiany dla nowych zadań online.';
$string['details'] = 'Szczegóły';
$string['ideoneautherror'] = 'Zła nazwa użytkownika lub hasło';
$string['ideonedelay'] = 'Opóźnienie pomiędzy zgłoszeniami do ideone.com (w sekundach)';
$string['ideonedelay_help'] = 'Jeśli opóźnienie pomiędzy zgloszeniami jest zbyt małe, ideone może odżucić zgloszenie.';
$string['ideoneerror'] = 'Ideone zwrócił błąd: {$a}';
$string['ideoneresultlink'] = 'Zobacz szczegóły na <a href="http://ideone.com/{$a}">http://ideone.com/{$a}</a>.';
$string['ideoneuserrequired'] = 'Wymagane jeśli zaznaczono ideone.com';
$string['info'] = 'Informacje';
$string['info0'] = 'Jesli czekasz zbyt długo, poinformuj administratora';
$string['info1'] = 'Gratulacje!!!';
$string['info2'] = 'Dobry program musi zwracać 0 jeśli nie nastąpił żaden błąd';
$string['info3'] = 'The compiler dislikes your code';
$string['info4'] = 'It seems that the compiler likes your code';
$string['info5'] = 'Zużywasz zbyt dużo zasobów';
$string['info6'] = 'Twój kod wypisuje zbyt duzo na stdout';
$string['info7'] = 'Prawie dobrze, za wyjątkiem spacji, tabulatorów, znaków nowej linni itp';
$string['info8'] = 'Twój program wywolał funkcje które <em>nie</em> są dozwolone';
$string['info9'] = '[SIGSEGV, Segment fault, Naruszenie ochrony pamięci] Zły index w tablicy, zły wskaźnik albo jeszcze coś gorszego';
$string['info10'] = 'Program wykonywał się zbyt długo';
$string['info11'] = 'Sprawdż swój kod jeszcze raz. Nie wypisuj żadnych dodatkowych znaków';
$string['info21'] = 'Sprawdzaczka nie działa dobrze. Poinformuj administratora';
$string['info22'] = 'Jesli czekasz zbyt długo, poinformuj administratora';
$string['infostudent'] = 'Informacje';
$string['infoteacher'] = 'Ważne informacje';
$string['invalidlanguage'] = 'Złe ID języka: {$a}';
$string['invalidjudgeclass'] = 'Invalid judge class: {$a}';
$string['invalidtaskid'] = 'Złe id zadania: {$a}';
$string['judgednotifysubject'] = '{$a->count} oczekujących zadań, oczekuje zbyt dług';
$string['judgestatus'] = 'Online Judge ocenił <strong>{$a->judged}</strong> zadań i teraz jest <strong>{$a->pending}</strong> zadan oczekujących.';
$string['langc_sandbox'] = 'C (wykonaj lokalnie)';
$string['langc_warn2err_sandbox'] = 'C (wykonaj lokalnie, ostrzeżenia traktuj jako błędy)';
$string['langcpp_sandbox'] = 'C++ (wykonaj lokalnie)';
$string['langcpp_warn2err_sandbox'] = 'C++ (wykonaj lokalnie, ostrzeżenia traktuj jako błędy)';
$string['maxcpulimit'] = 'Maksymalne użycie CPU (w sekundach)';
$string['maxcpulimit_help'] = 'How long can a program been judged keep running.';
$string['maxmemlimit'] = 'Maksymalne użycie pamięci (MB)';
$string['maxmemlimit_help'] = 'How many memory can a program been judged use.';
$string['memusage'] = 'Uzycie pamięci';
$string['mystat'] = 'Moje statystyki';
$string['notesensitive'] = '* Pokazuj tylko nauczycielą';
$string['onefileonlyideone'] = 'Ideone.com does not support multi-files';
$string['onlinejudge:viewjudgestatus'] = 'Pokaż status sędziego';
$string['settingsupdated'] = 'Zaktualizowano ustawienia.';
$string['status0'] = 'Czekaj...';
$string['status1'] = 'Zaakceptowano';
$string['status2'] = 'Abnormal Termination';
$string['status3'] = 'Błąd kompilacji';
$string['status4'] = 'Compilation Ok';
$string['status5'] = 'Przekroczono limit pamięci';
$string['status6'] = 'Przekroczono limit wyjścia';
$string['status7'] = 'Presentation Error';
$string['status9'] = 'Błąd wykonania';
$string['status8'] = 'Niedozwolone funkcje';
$string['status10'] = 'Przekroczono limit czasu';
$string['status11'] = 'Zła odpowiedź';
$string['status22'] = 'Ocenianie...';

