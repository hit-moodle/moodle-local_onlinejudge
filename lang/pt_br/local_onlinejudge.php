<?php
///////////////////////////////////////////////////////////////////////////
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                       Online Judge Moodle 3.4+                        //
//                 Copyright (C) 2018 onwards Andrew Nagyeb              //
// This program is based on the work of Sun Zhigang (C) 2009 Moodle 2.6. //
//                                                                       //
//    Modifications were made in order to upgrade the program so that    //
//                     it is compatible to Moodle 3.4+.                  //
//                       Original License Follows                        //
///////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                   Analisador Online para Moodle                       //
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
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @translate Paulo Alexandre
 */
$string['about'] = 'Sobre';
$string['aboutcontent'] = '<a href="https://github.com/hit-moodle/moodle-local_onlinejudge">Analise Online</a> e desenvolvido por <a href="http://www.hit.edu.cn">Harbin Institute of Technology</a>, e licenciado por <a href="http://www.gnu.org/copyleft/gpl.html">GPL</a>.';
$string['badvalue'] = 'Valor incorreto';
$string['cannotrunsand'] = 'Nao e possivel executar a tarefa';
$string['compileroutput'] = 'Saida do compilador';
$string['cpuusage'] = 'Uso da CPU';
$string['defaultlanguage'] = 'Idioma padrao';
$string['defaultlanguage_help'] = 'Definicao de idioma padrao para as novas atribuicoes do Analise Online.';
$string['details'] = 'Detalhes';
$string['seautherror'] = 'Nome de usuario errado ou senha errada';
$string['sedelay'] = 'Atraso entre pedidos de ideone.com (segundo)';
$string['sedelay_help'] = 'Depois de enviar um pedido a Analise ideone.com, nao podemos obter o resultado imediatamente.�Quanto tempo devemos esperar antes de consultar o resultado?�5 segundos ou entao um pouco mais.';
$string['seerror'] = 'Ideone retornou um erro: {$a}';
$string['seresultlink'] = 'Veja detalhes em <a href="https://{$a->end_point}.compilers.sphere-engine.com/api/v4/submissions/{$a->submission_id}?access_token={$a->access_token}">https://{$a->end_point}.compilers.sphere-engine.com/api/v4/submissions/{$a->submission_id}?access_token={$a->access_token}</a>.';
$string['seclientidrequired'] = 'Necessario se o Analisador ideone.com for selecionado';
$string['info'] = 'Informacao';
$string['infostudent'] = 'Informacoes';
$string['infoteacher'] = 'Informacoes confidenciais';
$string['invalidlanguage'] = 'ID de idioma invalido: {$a}';
$string['invalidjudgeclass'] = 'Classe invalida do Analisador: {$a}';
$string['invalidtaskid'] = 'Id tarefa invalido: {$a}';
$string['judgedcrashnotify'] = 'A tarefa do Analisador falhou';
$string['judgedcrashnotify_help'] = 'Se a tarefa do Analisador parar de funcionar devido a bugs de software ou atualizacoes, quem ira receber a notificacao?�Deve ser uma pessoa que pode acessar o shell do servidor e iniciar a tarefa do Analisador.';
$string['judgednotifybody'] = 'Entre as {$a->count} tarefas pendentes, a tarefa mais antiga ficou na fila de espera desde {$a->period}.�

e possivel que o Analisador tenha parado de funcionar.

Voc� deve inicia-lo o mais rapidamente possivel!';
$string['judgednotifysubject'] = '{$a->count} tarefas pendentes que esperaram tempo de mais';
$string['judgestatus'] = 'Analisador on-line tem <strong> {$a->judged} </strong> tarefas julgadas  e <strong> {$a->pending} </strong> tarefas na fila de espera.';
$string['langc_sandbox'] = 'C (executado localmente)';
$string['langc_warn2err_sandbox'] = 'C (executado localmente, avisos como erros)';
$string['langcpp_sandbox'] = 'C + + (executado localmente)';
$string['langcpp_warn2err_sandbox'] = 'C + + (executado localmente, avisos como erros)';
$string['maxcpulimit'] = 'Maximo o uso da CPU (segundo)';
$string['maxcpulimit_help'] = 'Quanto tempo um programa pode continuar executando.';
$string['maxmemlimit'] = 'Utilizacao maxima da memoria (MB)';
$string['maxmemlimit_help'] = 'Quanto de memoria um programa em Analise pode usar.';
$string['memusage'] = 'Uso de memoria';
$string['messageprovider:judgedcrashed'] = 'O processo de Analise On-line parou.';
$string['mystat'] = 'Minhas Estatisticas';
$string['notesensitive'] = '* Exibido apenas para os professores';
$string['onefileonlyideone'] = 'Ideone.com nao suporta multi-arquivos';
$string['onlinejudge:viewjudgestatus'] = 'Exibir o status da Analise';
$string['onlinejudge:viewmystat'] = 'Ver estatisticas automaticas';
$string['onlinejudge:viewsensitive'] = 'Ver detalhes sensiveis';
$string['pluginname'] = 'Analisador on-line';
$string['sandboxerror'] = 'Sandbox gerou um erro: {$a}';
$string['settingsform'] = 'Configuracoes Analisador On-line';
$string['settingsupdated'] = 'Configuracoes atualizadas.';
$string['status0'] = 'Pendente ...';
$string['status1'] = '<span style="color: red; ">Aceito</span>';
$string['status2'] = 'Termino anormal';
$string['status3'] = 'Erro de compilacao';
$string['status4'] = 'Compilacao OK';
$string['status5'] = 'Limite de memoria Excedido';
$string['status6'] = 'Limite de saida excedido';
$string['status7'] = 'Erro de apresentacao';
$string['status9'] = 'Erro em tempo de execucao';
$string['status8'] = 'Funcoes restringidas';
$string['status10'] = 'Prazo Excedido';
$string['status11'] = 'Resposta errada';
$string['status21'] = 'Erro interno';
$string['status22'] = 'Submeter ...';
$string['status23'] = 'M�ltiplos status';
$string['status255'] = 'Nao-Submetido';
$string['stderr'] = 'Saida de erro padrao';
$string['stdout'] = 'Saida padrao';
$string['upgradenotify'] = 'Nao se esqueca de executar cli/install_assignment_type and cli/judged.php. Detalhes em <a href="https://github.com/hit-moodle/moodle-local_onlinejudge/blob/master/README.md" target="_blank">README</a>.';
// TODO: Fix/Add (existing/missing) translations.
