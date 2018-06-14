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
 * Strings for Online Judge Assignment Type
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @translate Paulo Alexandre
 */

$string['addtestcases']				= 'Adicionar {$a} mais casos de teste(s)';
$string['assignmentlangs'] 			= 'A linguagem de programacao';
$string['badtestcasefile']			= 'Este arquivo nao existe ou nao pode ser lido ';
$string['cannotruncompiler'] 		= 'Nao foi possivel executar o script de compilador';
$string['case']						= 'Caso {$a}:';
$string['compileonly'] 				= 'Apenas compilar';
$string['compileonly_help'] 		= 'Selecionando sim, as submissoes serao compiladas, mas nao serão executadas. Os professores deverao executar manualmente.';
$string['compiler']					= 'Compilador';
$string['configmaxcpu'] 			= 'Taxa de utilizacao maxima de cpu para todas as atividades analisadas (sujeito a outras configuracoes locais)';
$string['configmaxmem']				= 'Quantidade maxima de uso de memoria para todas as atividades analisadas (sujeito a outras configuracoes locais)';
$string['cpulimit'] 				= 'Tempo maximo de processamento';
$string['denytoreadfile'] 			= 'Voce nao tem permissao para ler este arquivo.';
$string['download'] 				= 'Download';
$string['duejudge'] 				= 'analises pendentes';
$string['feedback'] 				= 'Feedback para a resposta errada';
$string['feedback_help'] 			= 'A mensagem de ajuda para os alunos que nao passaram nos casos de teste. E o que eles podem fazer para melhora os resultados, alguma dica ou instrucao.';
$string['filereaderror'] 			= 'Nao foi possivel ler este arquivo';
$string['forcejudge'] 				= 'Forcar a analise';
$string['ideoneuser'] 				= 'Nome de usuario do Ideone';
$string['ideoneuser_help'] 			= 'Se você escolher um idioma que e executado em ideone.com, você deve fornecer um <a href="http://ideone.com">ideone.com</a> username.';
$string['ideonepass'] 				= 'Senha do Ideone';
$string['ideonepass_help'] 			= 'Nao e a senha ideone mas o ideone <em>API</em> senha. Alterar a senha API em <a href="https://ideone.com/account/">https://ideone.com/account/</a>.';
$string['ideonepass2'] 				= 'Redigite a senha da API Ideone';
$string['ideonepassmismatch'] 		= 'As senhas nao conferem';
$string['input'] 					= 'Entrada';
$string['input_help'] 				= 'Os dados de entrada serao enviados para o stdin dos programas apresentados.';
$string['inputfile'] 				= 'Arquivo de entrada';
$string['inputfile_help'] 			= 'Os dados no arquivo serao enviados para o stdin dos programas apresentados. Se o arquivo estiver faltando, os casos de teste serao ignorados.';
$string['judgetime'] 				= 'Tempo de analise';
$string['managetestcases'] 			= 'Gerenciar os casos de teste';
$string['maxcpuusage'] 				= 'Uso da maximo de CPU';
$string['maximumfilesize'] 			= 'Tamanho maximo de arquivo fonte';
$string['maxmemusage'] 				= 'Uso de maximo de memoria';
$string['memlimit'] 				= 'Limite de memoria maxima';
$string['notestcases'] 				= 'Sem casos de teste definidos';
$string['onlinejudgeinfo']			= 'Informacoes do Analisador Online';
$string['output'] 					= 'Saida';
$string['output_help'] 				= 'A saida do programa sera comparada com os arquivos de entrada para a correta analise.';
$string['outputfile'] 				= 'Arquivo de saida';
$string['outputfile_help'] 			= 'Os dados no arquivo de saida serao comparados com as saidas das submissoes para a correta analise. Se nao houver nenhum arquivo, os casos de testes serao ignorados.';
$string['pluginname'] 				= 'Analisador Online';
$string['ratiope'] 					= 'Nota para uma apresentacao errada';
$string['ratiope_help'] 			= 'Nota de erro da apresentacao e igual aos casos de teste.

Apresentação de erro significa que os dados retornados pelo programa estão corretos, mas os seperadores entre cada token de dados são incompativeis com casos de teste. Geralmente e causado por espaços em branco ou quebras de linha. Se você quiser ser rigoroso, configura-lo para 0% um erro de apresentação sera no valor zero. Se você o formato da resposta nao e tão trivial, configure-o para 100% e um erro de apresentação sera aceito.';
$string['readytojudge'] 			= 'Pronto para ser analisado';
$string['rejudgeall'] 				= 'Analisar todos';
$string['rejudgeallnotice'] 		= 'Analisar todas as submissoes pode levar um longo tempo. Deseja continuar?';
$string['rejudgefailed'] 			= 'Nao foi possivel analisar seu pedido';
$string['rejudgesuccess']			= 'O pedido de analise foi enviado com sucesso';
$string['requestjudge'] 			= 'Requisitar analise';
$string['runtimeout'] 				= 'Tempo maximo de saida';
$string['statistics'] 				= 'Estatisticas';
$string['status'] 					= 'Status';
$string['status_help'] 				= 'Status indica os resultados obtidos pelo Analisador Online. Os significados estao a seguir:
		
* Termino anormal - O seu programa não retornou 0 depois de sair. Nota 0.
* Aceito - Passou. A Nota e a soma de todas as notas de todos os casos de teste disponivel.
* Erro de Compilação - O compilador não acredita que o código esteja correto. Nota 0.
* Compilação OK - Se a atribuição foi definido como compilar <em>somente</em>, entao seu codigo foi aprovado na compilacao, esse status e retornado. Nenhum Nota.
* Erro interno - O sistema foi configurado incorretamente ou o Analisador não esta funcionando. Somente o administrador pode resolver este problema. Nenhum Nota.
* Limite Maximo de Memoria Excedido - Seu programa utilizou o maximo de memória permitido. Nota 0.
* Mais de um Status - Ha mais de um caso de teste e os resultados do Analisador de cada caso de teste sao diferentes. Verifique <em>informacoes</em> para mais detalhes. A nota e a soma de todos as notas de cada caso de teste aprovado.
* Limite de saida excedido - Seu programa tem gerou uma saida muito grande. Verifique se existe algum loop infinito que gere essa saida. Nota 0.
* Pendente - O seu programa esta esperando na fila de analise. Aguarde por favor. No entanto, se você esta esperando por um muito tempo, talvez ha algo errado com o Analisador online. Nenhum Nota.
* Erro de Apresentação - Todos os tokens em sua saida estão corretos. Mas os separadores (por exemplo, espaços em branco, quebra de linha, tabulacoes) são diferentes da resposta padrão. A nota pode ser de 0 a 100%. Depende da definição de atribuicao.
* Funções Restritas - Seu programa tem chamado algumas funções internas do sistema. Nota 0.
* Erro de Acesso a memoria (Acess Violation) - Seu programa executou uma operação ilegal. Talvez tenha sido uma falha na tentativa de acesso à memória ou uma isntrucao sem logica. Nota 0.
* Tempo de execucao excedido - Seu programa utilizou o tempo maximo permitido de CPU. Nota 0.
* Resposta Errada - A saida do seu programa não coincide com a resposta padrao. Nota 0.';
$string['subgrade']					= 'Notas';
$string['subgrade_help'] 			= 'Total de pontos dos os alunos depois de passar nos casos de teste.
		
Se as tarefas teem nota maxima e 50, e os Casos de Teste teem a nota definida para 20%, os estudantes que passaram no teste vaao ganhar 10 pontos e quem nao conseguiu passar ira fica com nota zero. A nota final e a soma de todos os pontos obtido a partir de cada DE CASOS Teste. Se a soma for maior do que a atribuicao, a nota maxima sera usado como a nota final.
		
A soma de todas as categorias de casos de teste <em>nao</em> e necessario para ser 100%. Portanto, você pode deixar alguns pontos para a classificação manual, se a soma for inferior a 100%. E tambem, você pode fazer a soma seja superior a 100% para que nem todos os casos de teste sejam obrigados a passar.';
$string['successrate'] 				= 'A taxa de acertos';
$string['testcases'] 				= 'Casos de Teste ';
$string['testcases_help'] 			= 'Cada casos de teste sera aplicado aos argumentos e julgados separadamente. Por exemplo, se ha três casos de teste, uma submissao sera executado três vezes para testar caso diferente.';
$string['typeonlinejudge'] 			= 'Analisador OnLine';
$string['usefile'] 					= 'Utilizar arquivos para casos de teste';
$string['waitingforjudge'] 			= 'Aguardando pelo Analisador';

