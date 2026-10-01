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
 * local_feedbackinsights.php
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


$string['analyse'] = 'Analisar respostas';
$string['analysescleared'] = 'As análises salvas foram apagadas.';
$string['analysis'] = 'Análise de feedback';
$string['analysiscreated'] = 'Criada em {$a->date}; {$a->responses} respostas não vazias analisadas e {$a->empty} respostas vazias ignoradas.';
$string['analysissaved'] = 'Análise estruturada salva como #{$a}. O prompt bruto e a resposta bruta da IA não foram persistidos.';
$string['anonymous'] = 'Resposta anônima';
$string['assignment:onlinetext'] = 'Respostas de texto on-line';
$string['backtoanalyses'] = 'Voltar aos Insights de feedback';
$string['changesource'] = 'Trocar fonte';
$string['clearconfirm'] = 'Apagar todas as análises do Insights de feedback e os mapeamentos de respostas deste curso? As respostas originais do Feedback e das Tarefas não serão alteradas.';
$string['clearcourseanalyses'] = 'Apagar análises salvas deste curso';
$string['confidence'] = 'Confiança semântica';
$string['datefrom'] = 'De';
$string['dateuntil'] = 'Até';
$string['divergences'] = 'Divergências';
$string['emptyresponsesremoved'] = '{$a} resposta(s) vazia(s) foram removidas localmente antes da análise por IA.';
$string['error:anonymousseparategroups'] = 'Feedback anônimo não pode ser analisado por um usuário restrito a grupos separados, pois o plugin não tentará reidentificar respondentes anônimos para inferir o grupo.';
$string['error:daterange'] = 'A data final não pode ser anterior à data inicial.';
$string['error:invalidquestion'] = 'Uma das perguntas selecionadas não está disponível nesta fonte.';
$string['error:invalidsource'] = 'Fonte de respostas inválida ou indisponível.';
$string['error:malformedairesponse'] = 'O bridge de IA retornou uma resposta malformada. Nenhuma análise foi salva.';
$string['error:noresponses'] = 'Nenhuma resposta textual não vazia foi encontrada para esta seleção.';
$string['error:notextquestions'] = 'Esta fonte não contém perguntas abertas suportadas.';
$string['error:responsenotfound'] = 'A resposta original não existe mais ou não está acessível.';
$string['error:storedanalysis'] = 'A análise estruturada armazenada é inválida.';
$string['error:toomanyresponses'] = 'Esta seleção contém mais de {$a} respostas. Reduza as perguntas ou o período em vez de fazer amostragem silenciosa.';
$string['evidence'] = 'Evidências';
$string['intro'] = 'Agrupa respostas abertas em temas rastreáveis e insights recorrentes. As contagens são calculadas localmente a partir de IDs de respostas validados; a IA é usada apenas no agrupamento semântico e nos resumos.';
$string['invalididsdiscarded'] = '{$a} ID(s) de resposta inválido(s) devolvido(s) pela IA foram descartados antes do cálculo das métricas.';
$string['nosources'] = 'Não há uma fonte de respostas suportada e acessível para você neste curso.';
$string['nothemes'] = 'Nenhum tema confiável foi retornado para as respostas selecionadas.';
$string['originalresponse'] = 'Resposta original';
$string['pluginname'] = 'Insights de feedback';
$string['privacy:metadata:analysis'] = 'Armazena análises estruturadas criadas por profissionais de ensino autorizados.';
$string['privacy:metadata:analysis:courseid'] = 'Curso que contém a análise.';
$string['privacy:metadata:analysis:createdby'] = 'Usuário que solicitou a análise.';
$string['privacy:metadata:analysis:questionids'] = 'Identificadores das perguntas selecionadas.';
$string['privacy:metadata:analysis:resultsjson'] = 'Temas derivados, métricas e referências às respostas de origem em formato estruturado.';
$string['privacy:metadata:analysis:source'] = 'Tipo de fonte analisada.';
$string['privacy:metadata:analysis:sourceinstanceid'] = 'Identificador da instância da atividade de origem.';
$string['privacy:metadata:analysis:timecreated'] = 'Momento em que a análise foi criada.';
$string['privacy:metadata:bridge'] = 'Texto selecionado, normalizado e parcialmente anonimizado é enviado através do local_ai_bridge para agrupamento semântico. IDs de usuário Moodle, nomes e IDs de banco da resposta não fazem parte do prompt.';
$string['privacy:metadata:bridge:questionid'] = 'Identificador artificial da pergunta válido apenas na requisição.';
$string['privacy:metadata:bridge:responseid'] = 'Identificador artificial da resposta válido apenas na requisição.';
$string['privacy:metadata:bridge:text'] = 'Texto normalizado da resposta com identificadores diretos comuns mascarados quando possível.';
$string['privacy:metadata:member'] = 'Relaciona uma análise a respostas da origem e, para fontes não anônimas, ao ID do respondente para atendimento à Privacy API.';
$string['privacy:metadata:member:analysisid'] = 'Análise relacionada.';
$string['privacy:metadata:member:sourceanswerid'] = 'Identificador da resposta na fonte.';
$string['privacy:metadata:member:themesjson'] = 'Chaves dos temas associados à resposta.';
$string['privacy:metadata:member:userid'] = 'ID do respondente quando a fonte não é anônima.';
$string['questionnumber'] = 'Pergunta #{$a}';
$string['questions'] = 'Perguntas';
$string['recentanalyses'] = 'Análises salvas recentemente';
$string['recurringsuggestions'] = 'Sugestões recorrentes';
$string['responseid'] = 'Resposta #{$a}';
$string['responses'] = 'Respostas';
$string['setting:batchsize'] = 'Tamanho do lote para IA';
$string['setting:batchsize_desc'] = 'Quantidade de respostas não vazias em cada requisição de análise semântica. O valor é limitado entre 10 e 100.';
$string['setting:maxchars'] = 'Máximo de caracteres por resposta enviado à IA';
$string['setting:maxchars_desc'] = 'Respostas longas são normalizadas localmente e truncadas nesse tamanho antes do prompt semântico.';
$string['setting:maxresponses'] = 'Máximo de respostas por análise';
$string['setting:maxresponses_desc'] = 'Falha explicitamente e pede que o professor reduza a seleção, em vez de fazer amostragem silenciosa quando o limite é ultrapassado.';
$string['setting:mintrendresponses'] = 'Mínimo de respostas para tendências';
$string['setting:mintrendresponses_desc'] = 'As faixas de tendência só aparecem quando o período selecionado possui pelo menos essa quantidade de respostas não vazias e cobre ao menos dois dias.';
$string['setting:persistanalyses'] = 'Persistir análises estruturadas';
$string['setting:persistanalyses_desc'] = 'Armazena temas estruturados, contagens e IDs das respostas de origem para permitir reabrir relatórios. O plugin não persiste prompt bruto, resposta bruta da IA nem o texto original das respostas.';
$string['setting:retentiondays'] = 'Dias de retenção';
$string['setting:retentiondays_desc'] = 'Análises salvas mais antigas que este período são removidas pela tarefa agendada.';
$string['source'] = 'Fonte';
$string['source:assignment'] = 'Tarefa';
$string['source:feedback'] = 'Feedback';
$string['task:cleanup'] = 'Apagar análises expiradas do Insights de feedback';
$string['themes'] = 'Temas';
$string['trend'] = 'Tendência';
