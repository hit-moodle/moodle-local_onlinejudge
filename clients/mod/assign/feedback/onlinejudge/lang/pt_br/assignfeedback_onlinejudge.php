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
 * Strings for Online Judge Assignment Type
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @translate Paulo Alexandre
 */
$string['testcasemanagement'] = 'Gerenciamento de Casos de Teste';
$string['addtestcases'] = 'Adicionar {$a} mais casos de teste(s)';
$string['assignmentlangs'] = 'A linguagem de programacao';
$string['badtestcasefile'] = 'Este arquivo nao existe ou nao pode ser lido ';
$string['cannotruncompiler'] = 'Nao foi possivel executar o script de compilador';
$string['case'] = 'Caso {$a}:';
$string['compileonly'] = 'Apenas compilar';
$string['compileonly_help'] = 'Selecionando sim, as submissoes serao compiladas, mas nao ser�o executadas.�Os professores deverao executar manualmente.';
$string['compiler'] = 'Compilador';
$string['configmaxcpu'] = 'Taxa de utilizacao maxima de cpu para todas as atividades analisadas (sujeito a outras configuracoes locais)';
$string['configmaxmem'] = 'Quantidade maxima de uso de memoria para todas as atividades analisadas (sujeito a outras configuracoes locais)';
$string['cpulimit'] = 'Tempo maximo de processamento';
$string['denytoreadfile'] = 'Voce nao tem permissao para ler este arquivo.';
$string['download'] = 'Download';
$string['duejudge'] = 'analises pendentes';
$string['feedback'] = 'Feedback para a resposta errada';
$string['feedback_help'] = 'A mensagem de ajuda para os alunos que nao passaram nos casos de teste.�E o que eles podem fazer para melhora os resultados, alguma dica ou instrucao.';
$string['filereaderror'] = 'Nao foi possivel ler este arquivo';
$string['forcejudge'] = 'Forcar a analise';
$string['forcejudgerequestsent'] = 'The request of force judging submission of user <b>{$a}</b> is sent.';
$string['clientid'] = 'Nome de usuario do Ideone';
$string['clientid_help'] = 'Se voc� escolher um idioma que e executado em ideone.com, voc� deve fornecer um <a href="http://ideone.com">ideone.com</a> username.';
$string['accesstoken'] = 'Senha do Ideone';
$string['accesstoken_help'] = 'Nao e a senha ideone mas o ideone <em>API</em> senha.�Alterar a senha API em <a href="https://ideone.com/account/">https://ideone.com/account/</a>.';
$string['accesstoken2'] = 'Redigite a senha da API Ideone';
$string['accesstokenmismatch'] = 'As senhas nao conferem';
$string['input'] = 'Entrada';
$string['input_help'] = 'Os dados de entrada serao enviados para o stdin dos programas apresentados.';
$string['inputfile'] = 'Arquivo de entrada';
$string['inputfile_help'] = 'Os dados no arquivo serao enviados para o stdin dos programas apresentados.�Se o arquivo estiver faltando, os casos de teste serao ignorados.';
$string['judgetime'] = 'Tempo de analise';
$string['managetestcases'] = 'Gerenciar os casos de teste';
$string['maxcpuusage'] = 'Uso da maximo de CPU';
$string['maximumfilesize'] = 'Tamanho maximo de arquivo fonte';
$string['maxmemusage'] = 'Uso de maximo de memoria';
$string['memlimit'] = 'Limite de memoria maxima';
$string['notestcases'] = 'Sem casos de teste definidos';
$string['onlinejudgeinfo'] = 'Informacoes do Analisador Online';
$string['output'] = 'Saida';
$string['output_help'] = 'A saida do programa sera comparada com os arquivos de entrada para a correta analise.';
$string['outputfile'] = 'Arquivo de saida';
$string['outputfile_help'] = 'Os dados no arquivo de saida serao comparados com as saidas das submissoes para a correta analise.�Se nao houver nenhum arquivo, os casos de testes serao ignorados.';
$string['pluginname'] = 'Analisador Online';
$string['ratiope'] = 'Nota para uma apresentacao errada';
$string['ratiope_help'] = 'Nota de erro da apresentacao e igual aos casos de teste.

Apresenta��o de erro significa que os dados retornados pelo programa est�o corretos, mas os seperadores entre cada token de dados s�o incompativeis com casos de teste. Geralmente e causado por espa�os em branco ou quebras de linha. Se voc� quiser ser rigoroso, configura-lo para 0% um erro de apresenta��o sera no valor zero. Se voc� o formato da resposta nao e t�o trivial, configure-o para 100% e um erro de apresenta��o sera aceito.';
$string['readytojudge'] = 'Pronto para ser analisado';
$string['rejudgeall'] = 'Analisar todos';
$string['rejudgeallnotice'] = 'Analisar todas as submissoes pode levar um longo tempo.�Deseja continuar?';
$string['rejudgefailed'] = 'Nao foi possivel analisar seu pedido';
$string['rejudgesuccess'] = 'O pedido de analise foi enviado com sucesso';
$string['requestjudge'] = 'Requisitar analise';
$string['runtimeout'] = 'Tempo maximo de saida';
$string['statistics'] = 'Estatisticas';
$string['status'] = 'Status';
$string['status_help'] = 'Status indica os resultados obtidos pelo Analisador Online.�Os significados estao a seguir:
		
* Termino anormal - O seu programa n�o retornou 0 depois de sair. Nota 0.
* Aceito - Passou. A Nota e a soma de todas as notas de todos os casos de teste disponivel.
* Erro de Compila��o - O compilador n�o acredita que o c�digo esteja correto. Nota 0.
* Compila��o OK - Se a atribui��o foi definido como compilar <em>somente</em>, entao seu codigo foi aprovado na compilacao, esse status e retornado. Nenhum Nota.
* Erro interno - O sistema foi configurado incorretamente ou o Analisador n�o esta funcionando. Somente o administrador pode resolver este problema. Nenhum Nota.
* Limite Maximo de Memoria Excedido - Seu programa utilizou o maximo de mem�ria permitido. Nota 0.
* Mais de um Status - Ha mais de um caso de teste e os resultados do Analisador de cada caso de teste sao diferentes. Verifique <em>informacoes</em> para mais detalhes. A nota e a soma de todos as notas de cada caso de teste aprovado.
* Limite de saida excedido - Seu programa tem gerou uma saida muito grande. Verifique se existe algum loop infinito que gere essa saida. Nota 0.
* Pendente - O seu programa esta esperando na fila de analise. Aguarde por favor. No entanto, se voc� esta esperando por um muito tempo, talvez ha algo errado com o Analisador online. Nenhum Nota.
* Erro de Apresenta��o - Todos os tokens em sua saida est�o corretos. Mas os separadores (por exemplo, espa�os em branco, quebra de linha, tabulacoes) s�o diferentes da resposta padr�o. A nota pode ser de 0 a 100%. Depende da defini��o de atribuicao.
* Fun��es Restritas - Seu programa tem chamado algumas fun��es internas do sistema. Nota 0.
* Erro de Acesso a memoria (Acess Violation) - Seu programa executou uma opera��o ilegal. Talvez tenha sido uma falha na tentativa de acesso � mem�ria ou uma isntrucao sem logica. Nota 0.
* Tempo de execucao excedido - Seu programa utilizou o tempo maximo permitido de CPU. Nota 0.
* Resposta Errada - A saida do seu programa n�o coincide com a resposta padrao. Nota 0.';
$string['subgrade'] = 'Notas';
$string['subgrade_help'] = 'Total de pontos dos os alunos depois de passar nos casos de teste.
		
Se as tarefas teem nota maxima e 50, e os Casos de Teste teem a nota definida para 20%, os estudantes que passaram no teste vaao ganhar 10 pontos e quem nao conseguiu passar ira fica com nota zero. A nota final e a soma de todos os pontos obtido a partir de cada DE CASOS Teste. Se a soma for maior do que a atribuicao, a nota maxima sera usado como a nota final.
		
A soma de todas as categorias de casos de teste <em>nao</em> e necessario para ser 100%. Portanto, voc� pode deixar alguns pontos para a classifica��o manual, se a soma for inferior a 100%. E tambem, voc� pode fazer a soma seja superior a 100% para que nem todos os casos de teste sejam obrigados a passar.';
$string['successrate'] = 'A taxa de acertos';
$string['testcases'] = 'Casos de Teste ';
$string['testcases_help'] = 'Cada casos de teste sera aplicado aos argumentos e julgados separadamente.�Por exemplo,�se ha tr�s casos de teste, uma submissao sera executado tr�s vezes para testar caso diferente.';
$string['typeonlinejudge'] = 'Analisador OnLine';
$string['usefile'] = 'Utilizar arquivos para casos de teste';
$string['waitingforjudge'] = 'Aguardando pelo Analisador';
$string['enabled'] = 'Analisador Online';
$string['enabled_help'] = 'Allowing submissions to be programmatically judged';
$string['user_help_heading'] = 'User Help';
$string['user_help'] = '<a href="https://github.com/hit-moodle/moodle-local_onlinejudge">Source Control URL</a><hr>';

$string['compile_warnings_option'] = 'Allow Warnings';
$string['compile_lm_option'] = 'Link Math Library?';
$string['compile_static_option'] = 'Link Static Libraries Only';

$string['compile_warnings_option_help'] = 'if select yes, compiler warning messages will be allowed to be shown.';
$string['compile_lm_option_help'] = 'if select yes, math library will be linked while judging submissions.';
$string['compile_static_option_help'] = 'if select yes, only static libraries will be linked.';
// TODO: Fix translations to this language