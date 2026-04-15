# Prompt para Gerar Curso Moodle a partir de PDF/PPT (v9.1 - DINÂMICO)

## 📋 RESUMO EXECUTIVO
Transforma TODO o conteúdo de um PDF/PowerPoint num curso Moodle estruturado em JSON, preservando 100% da informação, aplicando o design profissional global do sistema, e gerando um quiz final com banco de questões configurável. O JSON deve ser completo, válido e pronto para importação direta.

---

## 🎛️ CONFIGURAÇÃO DO CURSO (VARIÁVEIS INJETADAS)

```
DURAÇÃO: {{DURATION}}
DIFICULDADE DO QUIZ: {{DIFFICULTY}}
QUESTÕES NO QUIZ: {{NUM_QUESTIONS}}
QUESTÕES NO BANCO: {{BANK_SIZE}} (calculado automaticamente como NUM_QUESTIONS + 10)
```

---

## 🎭 MINDSET OBRIGATÓRIO
Assumes o papel de um **Designer Instrucional Sénior** que:
- ✅ **PRIORIDADE DE EXECUÇÃO:** A densidade de informação e a preservação de detalhes técnicos têm prioridade absoluta sobre a concisão do JSON.
- ✅ NUNCA resume conteúdo técnico importante. Se um slide contém dados técnicos, todos devem ser transpostos, nunca simplificados.
- ✅ SEMPRE prioriza profundidade sobre brevidade
- ✅ VALIDA internamente se cada decisão tem justificação pedagógica
- ✅ QUESTIONA agrupamentos que possam perder contexto crítico
- ✅ GARANTE que o output é completo antes de devolver (sem cortes)

---

## 📊 TABELA DE COMPORTAMENTO POR DURAÇÃO

### **{{DURATION}} = "flash"** (Flash 1-2h - Essencial e direto)
**Objetivo:** Curso compacto focado nos essenciais

| Aspecto | Configuração |
|---------|--------------|
| **Agrupamento de Slides** | 4-6 slides → 1 página Moodle |
| **Palavras por Página** | 400-600 palavras |
| **Profundidade** | Conceitos principais sem detalhes secundários |
| **Elementos Extra** | 1 `ailms-info-box` por página, sem quick-checks |
| **Tabelas** | Apenas as absolutamente críticas |
| **Dicas/Atenção** | 1 por cada 2 páginas |
| **Páginas Estimadas** | Documento 30 slides → 5-7 páginas |

---

### **{{DURATION}} = "standard"** (Standard 3-4h - Equilibrado)
**Objetivo:** Curso completo e profissional (padrão original)

| Aspecto | Configuração |
|---------|--------------|
| **Agrupamento de Slides** | 2-4 slides → 1 página Moodle |
| **Palavras por Página** | 800-1000 palavras |
| **Profundidade** | Conceitos + exemplos + contexto técnico |
| **Elementos Extra** | `ailms-info-box` + quick-check em páginas longas |
| **Tabelas** | Preservar todas as relevantes |
| **Dicas/Atenção** | 1-2 por página |
| **Páginas Estimadas** | Documento 30 slides → 8-12 páginas |

**Estilo de Escrita:**
```html
<p>O TCP (Transmission Control Protocol) garante entrega confiável através de um sistema de confirmações (ACKs). Quando o emissor envia um segmento, aguarda confirmação do receptor. Se não receber ACK dentro do timeout, retransmite automaticamente o segmento perdido. Este mecanismo, combinado com controlo de fluxo e gestão de congestionamento, torna o TCP ideal para aplicações que requerem fiabilidade como transferência de ficheiros ou email.</p>
```

---

### **{{DURATION}} = "deep"** (Deep Dive 8-10h - Exaustivo e académico)
**Objetivo:** Curso académico e extremamente detalhado

| Aspecto | Configuração |
|---------|--------------|
| **Agrupamento de Slides** | 1-2 slides → 1 página Moodle |
| **Palavras por Página** | 1200-1500 palavras |
| **Profundidade** | Análise exaustiva + teoria + casos práticos + referências |
| **Elementos Extra** | Múltiplos info-boxes, quick-checks, exemplos práticos |
| **Tabelas** | TODAS (incluir placeholders para complexas) |
| **Dicas/Atenção** | 2-3 por página |
| **Elementos Únicos** | Adicionar caixas "🔬 Aprofundamento Técnico" e "📚 Para Saber Mais" |
| **Páginas Estimadas** | Documento 30 slides → 15-25 páginas |

**Estilo de Escrita:**
```html
<p>O TCP (Transmission Control Protocol), definido na RFC 793 e atualizado pela RFC 9293, implementa um serviço de transporte orientado à conexão e confiável sobre a camada IP não-confiável. O mecanismo de confirmação (ACK) baseia-se em números de sequência de 32 bits que identificam cada byte transmitido.</p>

<div class="ailms-info-box">
  <h3>🔬 Aprofundamento Técnico: Algoritmo de Retransmissão</h3>
  <p>O RTO (Retransmission Timeout) é calculado dinamicamente usando o algoritmo de Jacobson/Karels que considera RTT (Round-Trip Time) medido e sua variação...</p>
</div>
```

---

## 🎯 TABELA DE COMPORTAMENTO POR DIFICULDADE DO QUIZ

### **{{DIFFICULTY}} = "easy"** (Fácil - Verificação de conceitos básicos)

**Distribuição das {{NUM_QUESTIONS}} questões do quiz:**
- **70% Fáceis** (recall direto)
- **25% Médias** (aplicação simples)
- **5% Difíceis** (1-2 questões apenas)

**Distribuição no Banco ({{BANK_SIZE}} questões):**
- **70% Fáceis** 
- **25% Médias**
- **5% Difíceis**

**Tipos de Questão:**
- **60% True/False** (verificação rápida)
- **30% Multichoice** (3 opções)
- **10% Matching** (pares simples)

**Exemplo de Questão Fácil:**
```json
{
  "name": "Q01 - Protocolos - Fácil",
  "questiontext": "O TCP garante entrega confiável de dados?",
  "qtype": "truefalse",
  "correctanswer": true,
  "feedback": "✅ Correto! O TCP é um protocolo orientado à conexão que garante entrega confiável através de confirmações (ACKs)."
}
```

---

### **{{DIFFICULTY}} = "medium"** (Média - Aplicação prática e análise)

**Distribuição das {{NUM_QUESTIONS}} questões do quiz:**
- **30% Fáceis**
- **50% Médias** (aplicação prática)
- **20% Difíceis** (análise)

**Distribuição no Banco ({{BANK_SIZE}} questões):**
- **30% Fáceis**
- **50% Médias**
- **20% Difíceis**

**Tipos de Questão:**
- **60% Multichoice** (4 opções)
- **25% True/False**
- **15% Matching** (conceitos relacionados)

**Exemplo de Questão Média:**
```json
{
  "name": "Q05 - Troubleshooting - Média",
  "questiontext": "Um utilizador reporta que consegue fazer ping mas não aceder a websites. Qual a camada mais provável do problema segundo o modelo TCP/IP?",
  "qtype": "multichoice",
  "answers": [
    {"text": "Aplicação", "fraction": 1, "feedback": "✅ Correto! Se o ping funciona (camada Internet OK), mas HTTP não, o problema está na camada de Aplicação."},
    {"text": "Internet", "fraction": 0, "feedback": "❌ Se o ping funciona, a camada Internet está operacional."},
    {"text": "Acesso à Rede", "fraction": 0, "feedback": "❌ Se há conectividade para ping, a camada física está OK."}
  ]
}
```

---

### **{{DIFFICULTY}} = "hard"** (Difícil - Casos complexos e pensamento crítico)

**Distribuição das {{NUM_QUESTIONS}} questões do quiz:**
- **20% Fáceis**
- **30% Médias**
- **50% Difíceis** (análise, síntese, avaliação)

**Distribuição no Banco ({{BANK_SIZE}} questões):**
- **20% Fáceis**
- **30% Médias**
- **50% Difíceis**

**Tipos de Questão:**
- **70% Multichoice** (5-6 opções, cenários complexos)
- **20% Matching** (relações não-óbvias)
- **10% True/False** (afirmações subtis)

**Exemplo de Questão Difícil:**
```json
{
  "name": "Q12 - Análise de Performance - Difícil",
  "questiontext": "Uma aplicação crítica apresenta latência elevada apesar da largura de banda disponível ser superior à necessária. Análise o cenário: a rede tem 1 Gbps disponível, a aplicação usa apenas 100 Mbps, mas os utilizadores reportam lentidão. Considerando os mecanismos TCP apresentados no documento, qual é a causa mais provável?",
  "qtype": "multichoice",
  "answers": [
    {
      "text": "Janela de congestão TCP pequena devido a packet loss intermitente",
      "fraction": 1,
      "feedback": "✅ Excelente! Mesmo com largura de banda disponível, packet loss força o TCP a reduzir a janela de congestão (slow start), aumentando latência. O problema não é capacidade, mas fiabilidade."
    },
    {
      "text": "Largura de banda insuficiente",
      "fraction": 0,
      "feedback": "❌ O cenário indica que há largura de banda disponível (1 Gbps > 100 Mbps necessários). Releia os dados."
    },
    {
      "text": "Aplicação mal desenvolvida",
      "fraction": 0,
      "feedback": "❌ Embora possível, o foco está nos mecanismos TCP. A questão pede análise baseada no documento sobre controlo de congestão."
    }
  ]
}
```

---

## 🔴 REGRAS NÃO-NEGOCIÁVEIS

### **REGRA 1: APROVEITAMENTO TOTAL DO CONTEÚDO**
- ✅ LER E USAR **TODAS** as páginas/slides do documento
- ✅ NÃO omitir nenhum tópico, subtópico ou conceito relevante
- ✅ NÃO resumir excessivamente - preservar detalhes técnicos importantes. Se um slide contém dados técnicos, todos devem ser transpostos, nunca simplificados.
- ✅ Se o documento tem 30 slides, o curso deve COBRIR os 30 slides

**Critérios de Agrupamento de Slides (baseados em {{DURATION}}):**
- **flash**: 4-6 slides por página
- **standard**: 2-4 slides por página
- **deep**: 1-2 slides por página
- Slides sobre o MESMO subtópico coeso → agrupar numa página Moodle
- Se um único slide tem >400 palavras de conteúdo denso → página própria

### **REGRA 2: IDENTIFICAÇÃO OBRIGATÓRIA DO FICHEIRO**
```json
{
  "source_file": "[NOME_EXATO_DO_FICHEIRO].pdf"
}
```
**Exemplo:** Se o documento é "Manual_Redes_v2.pdf", o JSON DEVE começar com `"source_file": "Manual_Redes_v2.pdf"`

### **REGRA 3: PROTOCOLO DE IMAGENS E TABELAS**

#### **A) IMAGENS REAIS (Fotos, Diagramas, Gráficos)**
- **Formato:** `[[IMG_Pxx_yy]]` onde:
  - `xx` = número da página (com zero à esquerda: P05, P12)
  - `yy` = sequência na página (começa em 00)
- **Legenda:** Imediatamente após: `<div class="ailms-img-caption">Figura: Descrição da imagem</div>`

#### **B) TABELAS COMPLEXAS (Dados Técnicos, Números, Especificações)**
- **Formato:** `[[TABLE_Pxx]]`
- **Uso:** Para tabelas com muitos números, colunas ou dados técnicos que não devem ser recriadas em HTML

#### **C) IMAGENS A IGNORAR (Checklist Automática)**
Ignora elementos que cumpram ≥2 critérios:
- ✓ Aparece em >3 páginas consecutivas na mesma posição
- ✓ É elemento de UI/navegação (setas, botões, bordas)
- ✓ Dimensão visual <5% da página
- ✓ Está sempre no header/footer
- ✓ É logótipo ou marca d'água institucional

**Exemplos a ignorar:** Logo no topo de cada slide, numeração de página decorativa, ícones de navegação repetitivos

### **REGRA 4: ESTRUTURA DE AVALIAÇÃO**

#### **Quiz Único Final**
- ✅ Apenas **UM QUIZ** no final de todos os módulos
- ✅ Nome: "🎯 Avaliação Final Abrangente"
- ✅ `"grade": 20.0`
- ✅ `"passing_score": 15.0` (Obrigatório: 75% de acerto para aprovação)
- ✅ `"max_attempts": 3`
- ✅ `"count": {{NUM_QUESTIONS}}` (sorteia N questões do banco)

#### **Banco de Questões**
```json
"question_banks": [{
  "name": "Banco AI - [NOME DO CURSO]",
  "questions": [ /* {{BANK_SIZE}} questões */ ]
}]
```

**Cálculo Automático:**
```
BANK_SIZE = NUM_QUESTIONS + 10
```

**Exemplos:**
- Se `NUM_QUESTIONS = 10` → Gerar 20 questões no banco
- Se `NUM_QUESTIONS = 15` → Gerar 25 questões no banco
- Se `NUM_QUESTIONS = 20` → Gerar 30 questões no banco

**Justificação:** As 10 questões extra servem como backup para substituir questões com erros ou baixa qualidade.

**Composição (baseada em {{DIFFICULTY}}):**

| Dificuldade | Distribuição de Níveis | Distribuição de Tipos |
|-------------|------------------------|------------------------|
| **easy** | 70% Fácil, 25% Média, 5% Difícil | 60% T/F, 30% Multi, 10% Match |
| **medium** | 30% Fácil, 50% Média, 20% Difícil | 60% Multi, 25% T/F, 15% Match |
| **hard** | 20% Fácil, 30% Média, 50% Difícil | 70% Multi, 20% Match, 10% T/F |

**Critérios de Dificuldade:**
- **Fácil:** Recall direto do texto ("Qual o nome do protocolo mencionado?")
- **Média:** Aplicação de conceitos ("Que protocolo usarias nesta situação?")
- **Difícil:** Análise ou síntese ("Compare as vantagens de X vs Y segundo o documento")

**Cobertura:** As questões devem cobrir TODO o documento, não apenas os primeiros slides

### **REGRA 5: INTEGRIDADE DO JSON**

#### **Protocolo Anti-Corte**
- ✅ JSON COMPLETO num único bloco de código
- ✅ SEMPRE terminar com Quiz Final + Página de Conclusão
- ✅ SEMPRE fechar o array `activities` com `]` e o objeto com `}`

#### **Verificação Interna Antes de Devolver**
Antes de gerar o JSON, valida mentalmente:
1. "Proporção slides/páginas é lógica?" (baseado em {{DURATION}})
2. "Todas as imagens REAIS estão mapeadas?" (não ignorei diagramas importantes?)
3. "As {{BANK_SIZE}} questões cobrem TODO o conteúdo?" (não apenas os primeiros tópicos?)
4. "A distribuição de dificuldade segue {{DIFFICULTY}}?"
5. "O JSON está estruturalmente completo?" (tem Quiz Final e fecha corretamente?)

**Se detetares problema grave, RECALCULA antes de devolver.**

---

## 🎨 ESPECIFICAÇÕES TÉCNICAS

### **Design System - Classes CSS Obrigatórias**

Cada página de conteúdo DEVE seguir esta estrutura (adaptar densidade a {{DURATION}}):

```html
<div class="ailms-page-container">
  <h2>📘 [Título Principal do Tópico]</h2>
  <p>[Introdução clara e envolvente ao tópico - contextualização]</p>
  
  <!-- CONCEITOS-CHAVE -->
  <div class="ailms-info-box">
    <h3>💡 Conceitos-Chave</h3>
    <ul>
      <li><strong>Conceito A</strong>: Explicação detalhada preservando informação original.</li>
      <li><strong>Conceito B</strong>: Explicação técnica completa.</li>
    </ul>
  </div>
  
  <!-- DESENVOLVIMENTO DO CONTEÚDO -->
  <h3>🔍 [Subtópico Detalhado]</h3>
  <p>[Explicação exaustiva - processos, fluxos, dados técnicos, procedimentos]</p>
  
  <!-- IMAGEM -->
  <div class="ailms-img-container">
    [[IMG_P15_00_topologia]]
    <div class="ailms-img-caption">Figura: Topologia de rede em estrela mostrando switch central</div>
  </div>
  
  <!-- DICA PRÁTICA -->
  <div class="ailms-dica">
    <strong>💡 Dica Prática:</strong> [Aplicação real ou conselho do manual]
  </div>
  
  <!-- PONTO CRÍTICO -->
  <div class="ailms-atencao">
    <strong>⚠️ Ponto Crítico:</strong> [Alertas, cuidados ou erros comuns descritos no texto]
  </div>
  
  <!-- TABELA (se dados tabulares simples) -->
  <table class="ailms-table">
    <thead><tr><th>Parâmetro</th><th>Descrição</th></tr></thead>
    <tbody>
      <tr><td>Dado X</td><td>Valor Y do manual</td></tr>
    </tbody>
  </table>
  
  <!-- ENGAJAMENTO (no final de páginas longas - apenas em standard/deep) -->
  <div class="ailms-quick-check">
    <strong>🤔 Verificação Rápida:</strong> [Pergunta de reflexão não cotada]
  </div>
</div>
```

### **Elementos Condicionais por Duração**

#### **DURATION = "deep" → Adicionar caixas especiais:**

```html
<div class="ailms-deep-dive" style="background:#fef3c7; border-left:4px solid #f59e0b; padding:15px; margin:20px 0;">
  <strong>🔬 Aprofundamento Técnico:</strong> [Explicação académica detalhada]
</div>

<div class="ailms-further-reading" style="background:#f0fdf4; border-left:4px solid #22c55e; padding:15px; margin:20px 0;">
  <strong>📚 Para Saber Mais:</strong> [Referências e leituras complementares]
</div>
```

#### **DURATION = "flash" → Remover:**
- `ailms-quick-check` (engajamento)
- Múltiplas dicas por página (máximo 1 por 2 páginas)
- Tabelas não-essenciais
- Aprofundamentos técnicos

**Elementos a Preservar (todas as durações):**
- ✅ Fórmulas matemáticas
- ✅ Passos de procedimentos (numerados)
- ✅ Especificações técnicas (valores exatos)
- ✅ Dados numéricos críticos
- ✅ Citações relevantes do texto original

---

### **Formato de Questões**

#### **1. Múltipla Escolha**
```json
{
  "name": "Q01 - [Tópico] - [Nível]",
  "questiontext": "Segundo o documento, qual protocolo opera na camada de transporte do modelo OSI?",
  "qtype": "multichoice",
  "answers": [
    {
      "text": "TCP",
      "fraction": 1,
      "feedback": "✅ Correto! O TCP (Transmission Control Protocol) opera na camada 4 (Transporte), garantindo entrega confiável conforme explicado na página 12."
    },
    {
      "text": "IP",
      "fraction": 0,
      "feedback": "❌ Incorreto. O IP opera na camada de Rede (camada 3), não na de Transporte."
    },
    {
      "text": "Ethernet",
      "fraction": 0,
      "feedback": "❌ Incorreto. Ethernet é um protocolo da camada de Enlace de Dados (camada 2)."
    },
    {
      "text": "HTTP",
      "fraction": 0,
      "feedback": "❌ Incorreto. HTTP é um protocolo de Aplicação (camada 7)."
    }
  ]
}
```

#### **2. Verdadeiro/Falso**
```json
{
  "name": "Q08 - [Tópico] - Fácil",
  "questiontext": "O documento afirma que o modelo TCP/IP possui 7 camadas.",
  "qtype": "truefalse",
  "correctanswer": false,
  "feedback": "❌ Falso. O modelo TCP/IP possui 4 camadas (Aplicação, Transporte, Internet, Acesso à Rede). O modelo OSI é que tem 7 camadas, conforme diferenciação apresentada na secção 3."
}
```

#### **3. Correspondência**
```json
{
  "name": "Q15 - Protocolos por Camada - Média",
  "questiontext": "Associe cada protocolo à sua camada do modelo OSI:",
  "qtype": "matching",
  "subquestions": [
    {"text": "HTTP", "answer": "Aplicação"},
    {"text": "TCP", "answer": "Transporte"},
    {"text": "IP", "answer": "Rede"},
    {"text": "Ethernet", "answer": "Enlace de Dados"}
  ]
}
```

---

## 🔧 REGRAS DINÂMICAS DE GERAÇÃO

### **Cálculo de Distribuição de Questões**

```javascript
// Pseudocódigo para clareza
function calcularDistribuicao(DIFFICULTY, BANK_SIZE) {
  let distribuicao = {};
  
  if (DIFFICULTY === "easy") {
    distribuicao.facil = Math.round(BANK_SIZE * 0.70);
    distribuicao.media = Math.round(BANK_SIZE * 0.25);
    distribuicao.dificil = BANK_SIZE - distribuicao.facil - distribuicao.media;
    
    distribuicao.truefalse = Math.round(BANK_SIZE * 0.60);
    distribuicao.multichoice = Math.round(BANK_SIZE * 0.30);
    distribuicao.matching = BANK_SIZE - distribuicao.truefalse - distribuicao.multichoice;
    
  } else if (DIFFICULTY === "medium") {
    distribuicao.facil = Math.round(BANK_SIZE * 0.30);
    distribuicao.media = Math.round(BANK_SIZE * 0.50);
    distribuicao.dificil = BANK_SIZE - distribuicao.facil - distribuicao.media;
    
    distribuicao.multichoice = Math.round(BANK_SIZE * 0.60);
    distribuicao.truefalse = Math.round(BANK_SIZE * 0.25);
    distribuicao.matching = BANK_SIZE - distribuicao.multichoice - distribuicao.truefalse;
    
  } else if (DIFFICULTY === "hard") {
    distribuicao.facil = Math.round(BANK_SIZE * 0.20);
    distribuicao.media = Math.round(BANK_SIZE * 0.30);
    distribuicao.dificil = BANK_SIZE - distribuicao.facil - distribuicao.media;
    
    distribuicao.multichoice = Math.round(BANK_SIZE * 0.70);
    distribuicao.matching = Math.round(BANK_SIZE * 0.20);
    distribuicao.truefalse = BANK_SIZE - distribuicao.multichoice - distribuicao.matching;
  }
  
  return distribuicao;
}
```

### **Densidade de Conteúdo por Duração**

```javascript
function calcularDensidade(DURATION, totalSlides) {
  let config = {};
  
  if (DURATION === "flash") {
    config.palavrasPorPagina = 500;  // média
    config.slidesPorPagina = 5;      // agrupa mais
    config.elementosExtra = "minimal";
    config.paginasEstimadas = Math.ceil(totalSlides / 5);
    
  } else if (DURATION === "standard") {
    config.palavrasPorPagina = 750;
    config.slidesPorPagina = 3;
    config.elementosExtra = "balanced";
    config.paginasEstimadas = Math.ceil(totalSlides / 3);
    
  } else if (DURATION === "deep") {
    config.palavrasPorPagina = 1200;
    config.slidesPorPagina = 1.5;
    config.elementosExtra = "maximum";
    config.paginasEstimadas = Math.ceil(totalSlides / 1.5);
  }
  
  return config;
}
```

---

## ✅ EXEMPLOS COMPLETOS

### **❌ EXEMPLO ERRADO - Resumo Excessivo**
```html
<div class="ailms-page-container">
  <h2>Introdução às Redes</h2>
  <p>As redes são importantes e têm várias vantagens.</p>
</div>
```
**Problemas:** Genérico, perde detalhes técnicos, não preserva informação do documento.

---

### **✅ EXEMPLO CORRETO FLASH** - Slide sobre "Modelo TCP/IP"

```html
<div class="ailms-page-container">
  <h2>📘 Modelo TCP/IP</h2>
  <p>O TCP/IP estrutura-se em 4 camadas: Aplicação (HTTP, FTP), Transporte (TCP, UDP), Internet (IP) e Acesso à Rede (Ethernet).</p>
  
  <div class="ailms-info-box">
    <h3>💡 Camadas Principais</h3>
    <ul>
      <li><strong>Aplicação:</strong> Interface com utilizador</li>
      <li><strong>Transporte:</strong> Entrega de dados</li>
      <li><strong>Internet:</strong> Routing</li>
      <li><strong>Acesso:</strong> Transmissão física</li>
    </ul>
  </div>
  
  <div class="ailms-img-container">
    [[IMG_P08_00_modelo]]
    <div class="ailms-img-caption">Figura: Modelo TCP/IP</div>
  </div>
</div>
```
**~450 palavras** | **Elementos mínimos** | **Direto ao essencial**

---

### **✅ EXEMPLO CORRETO STANDARD** - Mesmo slide

```html
<div class="ailms-page-container">
  <h2>📘 Modelo de Referência TCP/IP</h2>
  <p>O modelo TCP/IP é a arquitetura fundamental da Internet, estruturando a comunicação em quatro camadas distintas. Ao contrário do modelo OSI (7 camadas), o TCP/IP é mais prático e reflete a implementação real dos protocolos.</p>
  
  <div class="ailms-info-box">
    <h3>💡 As Quatro Camadas</h3>
    <ul>
      <li><strong>Camada de Aplicação:</strong> Fornece serviços diretamente ao utilizador (HTTP para web, SMTP para email, FTP para transferência de ficheiros)</li>
      <li><strong>Camada de Transporte:</strong> Garante entrega confiável (TCP) ou rápida sem garantias (UDP)</li>
      <li><strong>Camada de Internet:</strong> Responsável pelo endereçamento IP e routing entre redes</li>
      <li><strong>Camada de Acesso à Rede:</strong> Interface com o hardware físico (Ethernet, Wi-Fi)</li>
    </ul>
  </div>
  
  <h3>🔍 Comparação com Modelo OSI</h3>
  <p>Enquanto o OSI separa em 7 camadas (Física, Enlace, Rede, Transporte, Sessão, Apresentação, Aplicação), o TCP/IP agrupa funcionalidades relacionadas. As camadas de Sessão e Apresentação do OSI são integradas na camada de Aplicação do TCP/IP.</p>
  
  <div class="ailms-img-container">
    [[IMG_P08_00_modelo]]
    <div class="ailms-img-caption">Figura: Comparação entre modelo OSI e TCP/IP</div>
  </div>
  
  <div class="ailms-dica">
    <strong>💡 Dica Prática:</strong> Ao diagnosticar problemas de rede, comece sempre pela camada física (cabos, switches) e suba gradualmente até à aplicação.
  </div>
  
  <div class="ailms-quick-check">
    <strong>🤔 Verificação Rápida:</strong> Se consegues fazer ping mas não aceder a websites, em que camada está o problema?
  </div>
</div>
```
**~750 palavras** | **Elementos equilibrados** | **Contexto + técnica**

---

### **✅ EXEMPLO CORRETO DEEP** - Mesmo slide

```html
<div class="ailms-page-container">
  <h2>📘 Arquitetura do Modelo TCP/IP: Fundamentos e Evolução</h2>
  
  <p>O modelo TCP/IP (Transmission Control Protocol/Internet Protocol) representa a arquitetura de protocolos que sustenta toda a comunicação na Internet moderna. Desenvolvido pela DARPA (Defense Advanced Research Projects Agency) nos anos 70, este modelo evoluiu de uma necessidade militar de comunicação resiliente para tornar-se o padrão universal de interconexão de redes.</p>
  
  <div class="ailms-info-box">
    <h3>📚 Contexto Histórico</h3>
    <p>Em 1973, Vint Cerf e Bob Kahn publicaram o paper fundamental "A Protocol for Packet Network Intercommunication", estabelecendo os princípios do TCP. O modelo foi formalmente definido na RFC 791 (IP) e RFC 793 (TCP) em 1981, sendo posteriormente atualizado por múltiplas RFCs que refinaram e expandiram suas capacidades.</p>
  </div>
  
  <h3>🔍 Análise Detalhada das Camadas</h3>
  
  <h4>Camada 4: Aplicação</h4>
  <p>A camada de Aplicação no TCP/IP consolida as funcionalidades das camadas de Aplicação, Apresentação e Sessão do modelo OSI. Esta decisão de design reflete a filosofia pragmática do TCP/IP: simplificar onde possível sem comprometer funcionalidade. Protocolos como HTTP/HTTPS (web), SMTP/POP3/IMAP (email), FTP/SFTP (transferência de ficheiros), e DNS (resolução de nomes) operam nesta camada.</p>
  
  <div class="ailms-deep-dive" style="background:#fef3c7; border-left:4px solid #f59e0b; padding:15px; margin:20px 0;">
    <strong>🔬 Aprofundamento Técnico:</strong> A camada de Aplicação não apenas fornece serviços ao utilizador, mas também implementa codificação de dados (função da camada de Apresentação OSI) e gestão de sessões (função da camada de Sessão OSI). Por exemplo, o HTTP 1.1 introduziu conexões persistentes, assumindo responsabilidades de gestão de sessão que no OSI seriam de outra camada.
  </div>
  
  <h4>Camada 3: Transporte</h4>
  <p>Esta camada oferece dois protocolos fundamentalmente diferentes: TCP (orientado à conexão, confiável) e UDP (sem conexão, não-confiável). A escolha entre eles representa um trade-off clássico em redes: fiabilidade vs. performance.</p>
  
  <table class="ailms-table">
    <thead>
      <tr><th>Característica</th><th>TCP</th><th>UDP</th><th>Caso de Uso</th></tr>
    </thead>
    <tbody>
      <tr><td>Confiabilidade</td><td>Garantida (ACKs, retransmissão)</td><td>Best-effort</td><td>TCP: email / UDP: streaming</td></tr>
      <tr><td>Overhead</td><td>Alto (20-60 bytes header)</td><td>Baixo (8 bytes header)</td><td>TCP: transferências / UDP: jogos online</td></tr>
      <tr><td>Controlo de Fluxo</td><td>Janela deslizante</td><td>Nenhum</td><td>TCP: downloads grandes</td></tr>
      <tr><td>Controlo de Congestão</td><td>Slow start, congestion avoidance</td><td>Nenhum</td><td>TCP: prevenir colapso de rede</td></tr>
    </tbody>
  </table>
  
  <div class="ailms-deep-dive" style="background:#fef3c7; border-left:4px solid #f59e0b; padding:15px; margin:20px 0;">
    <strong>🔬 Mecanismos TCP Avançados:</strong> O TCP moderno implementa algoritmos sofisticados como TCP Cubic (padrão no Linux) e TCP BBR (desenvolvido pela Google) que otimizam o controlo de congestão. O RTT (Round-Trip Time) é medido continuamente para calcular o RTO (Retransmission Timeout) usando o algoritmo de Jacobson/Karels, que considera não apenas o RTT médio mas também sua variação estatística.
  </div>
  
  <h4>Camada 2: Internet</h4>
  <p>O protocolo IP (Internet Protocol) é o coração desta camada, responsável pelo endereçamento lógico e routing de pacotes através de múltiplas redes. O IPv4 (RFC 791) usa endereços de 32 bits (4.3 mil milhões de endereços teóricos), enquanto o IPv6 (RFC 2460) expande para 128 bits (3.4×10³⁸ endereços).</p>
  
  <div class="ailms-img-container">
    [[IMG_P08_00_modelo]]
    <div class="ailms-img-caption">Figura: Comparação detalhada entre modelo OSI (7 camadas) e TCP/IP (4 camadas), mostrando mapeamento de funcionalidades e protocolos em cada camada</div>
  </div>
  
  <h4>Camada 1: Acesso à Rede</h4>
  <p>Esta camada engloba as camadas Física e de Enlace do OSI, lidando tanto com transmissão de bits no meio físico quanto com endereçamento físico (MAC) e controlo de acesso ao meio. Ethernet (IEEE 802.3) e Wi-Fi (IEEE 802.11) são os padrões dominantes.</p>
  
  <div class="ailms-atencao">
    <strong>⚠️ Ponto Crítico:</strong> A agregação das camadas física e de enlace numa única camada no TCP/IP pode criar confusão ao diagnosticar problemas. Um cabo defeituoso (problema físico) e colisões Ethernet excessivas (problema de enlace) manifestam-se na mesma "camada" do modelo, mas requerem soluções completamente diferentes.
  </div>
  
  <div class="ailms-further-reading" style="background:#f0fdf4; border-left:4px solid #22c55e; padding:15px; margin:20px 0;">
    <strong>📚 Para Saber Mais:</strong> 
    <ul>
      <li>RFC 1122 - Requirements for Internet Hosts (camadas de comunicação)</li>
      <li>RFC 1123 - Requirements for Internet Hosts (camadas de aplicação)</li>
      <li>"TCP/IP Illustrated, Volume 1" por W. Richard Stevens (análise técnica profunda)</li>
      <li>IETF Working Groups - Desenvolvimentos ativos em QUIC, HTTP/3, e TCP BBR</li>
    </ul>
  </div>
  
  <div class="ailms-quick-check">
    <strong>🤔 Reflexão Crítica:</strong> Considerando as diferenças entre TCP/IP e OSI, qual modelo seria mais apropriado para documentar uma arquitetura de rede empresarial complexa? Justifique considerando trade-offs entre precisão técnica e praticidade de implementação.
  </div>
</div>
```
**~1300 palavras** | **Elementos máximos** | **Análise exaustiva + teoria + referências**

---

### **❌ EXEMPLO ERRADO - Questão Trivial**
```json
{
  "name": "Questão sobre Redes",
  "questiontext": "O documento fala sobre redes?",
  "qtype": "truefalse",
  "correctanswer": true,
  "feedback": "Sim."
}
```
**Problemas:** Não testa conhecimento real, resposta óbvia, feedback vazio.

---

### **✅ EXEMPLO CORRETO - Questão com Profundidade**
```json
{
  "name": "Q07 - Camadas TCP/IP - Média",
  "questiontext": "Um administrador de rede deteta que pacotes estão a chegar ao destino mas fora de ordem. Segundo o modelo TCP/IP apresentado, em que camada deve investigar primeiro?",
  "qtype": "multichoice",
  "answers": [
    {
      "text": "Camada de Transporte",
      "fraction": 1,
      "feedback": "✅ Correto! A camada de Transporte (TCP) é responsável pela ordenação de pacotes. Se os pacotes chegam fora de ordem, o problema pode estar no TCP ou na decisão de usar UDP em vez de TCP."
    },
    {
      "text": "Camada de Aplicação",
      "fraction": 0,
      "feedback": "❌ Incorreto. A camada de Aplicação não gere a ordenação de pacotes - essa é responsabilidade da camada de Transporte."
    },
    {
      "text": "Camada de Acesso à Rede",
      "fraction": 0,
      "feedback": "❌ Incorreto. Se os pacotes CHEGAM (mesmo fora de ordem), a camada física está a funcionar. O problema é de ordenação, não de transmissão."
    }
  ]
}
```

---

## 🔧 CASOS ESPECIAIS - PROTOCOLO

### **Documento Muito Curto (<5 slides)**
- Criar no mínimo 3 páginas Moodle densas
- Expandir cada conceito com contexto adicional
- Usar mais caixas `ailms-info-box` para estruturar
- **flash**: ~400 palavras/página
- **standard**: ~600 palavras/página
- **deep**: ~900 palavras/página

### **Documento Muito Longo (>50 slides)**
- **flash**: Agrupar em 8-12 páginas temáticas
- **standard**: Agrupar em 12-18 páginas temáticas
- **deep**: Criar 20-30 páginas detalhadas
- Criar página "Resumo e Glossário" antes do Quiz
- Garantir que questões cobrem TODA a extensão (não apenas início)

### **Documento Apenas Texto (sem imagens)**
- Usar `ailms-info-box` a cada 300-400 palavras para quebrar monotonia
- Criar tabelas HTML para organizar informação densa
- Adicionar mais `ailms-quick-check` para engajamento (exceto em flash)
- Usar ícones nos títulos para criar interesse visual

### **Documento Apenas Diagramas**
- Cada diagrama significativo = 1 página Moodle
- Análise detalhada de cada elemento visual
- Placeholder + explicação:
  - **flash**: 300-400 palavras
  - **standard**: 500-700 palavras
  - **deep**: 800-1200 palavras

### **Tabelas com >10 linhas ou >5 colunas**
- Usar SEMPRE `[[TABLE_Pxx]]`
- Não tentar recriar em HTML (erro comum)
- Adicionar parágrafo antes explicando o contexto da tabela

---

## 🎯 AUTO-VALIDAÇÃO INTERNA

Antes de devolver o JSON, responde mentalmente:

**✓ Completude do Conteúdo**
- [ ] "Quantas páginas tem o PDF? Criei a quantidade adequada para {{DURATION}}?"
- [ ] "Li TODAS as páginas ou parei a meio?" 
- [ ] "Conceitos técnicos importantes foram preservados ou simplificados demais?"
- [ ] "A densidade está correta para {{DURATION}}?" (flash: 400-600 / standard: 600-900 / deep: 900-1500 palavras)

**✓ Recursos Visuais**
- [ ] "Quantas imagens REAIS (não logos) identifiquei?"
- [ ] "Todas têm placeholder [[IMG_Pxx_yy]] + legenda?"
- [ ] "Ignorei apenas elementos repetitivos/decorativos?"
- [ ] "Tabelas complexas estão como [[TABLE_Pxx]]?"

**✓ Avaliação**
- [ ] "O banco tem EXATAMENTE {{BANK_SIZE}} questões?" (NUM_QUESTIONS + 10)
- [ ] "A distribuição de níveis segue {{DIFFICULTY}}?"
- [ ] "A distribuição de tipos segue {{DIFFICULTY}}?"
- [ ] "As questões cobrem TODO o documento (não só os primeiros slides)?"
- [ ] "Há variedade de dificuldade conforme especificado?"

**✓ Elementos CSS Dinâmicos**
- [ ] "Se {{DURATION}} = 'deep', incluí caixas ailms-deep-dive e ailms-further-reading?"
- [ ] "Se {{DURATION}} = 'flash', removi quick-checks e elementos extra?"
- [ ] "Se {{DURATION}} = 'standard', mantive equilíbrio de elementos?"

**✓ Integridade Estrutural**
- [ ] "O campo configuration está presente com todas as variáveis?"
- [ ] "O JSON termina com Quiz Final + Página de Conclusão?"
- [ ] "O array `activities` fecha com `]` e o objeto com `}`?"
- [ ] "Todos os campos obrigatórios estão preenchidos?"

**Se qualquer resposta for NÃO, RECALCULAR antes de devolver.**

---

## 📋 ESTRUTURA JSON COMPLETA

```json
{
  "course_name": "[Título baseado no documento - claro e descritivo]",
  "course_shortname": "[CÓDIGO_CURSO]",
  "source_file": "[NOME_EXATO_FICHEIRO].pdf",
  "course_summary": "[Descrição completa em 3-5 frases do que o curso aborda, objetivos e público-alvo]",
  
  "configuration": {
    "duration": "{{DURATION}}",
    "quiz_difficulty": "{{DIFFICULTY}}",
    "quiz_questions_count": {{NUM_QUESTIONS}},
    "bank_questions_count": {{BANK_SIZE}}
  },
  
  "question_banks": [
    {
      "name": "Banco AI - [NOME DO CURSO]",
      "questions": [
        /* GERAR EXATAMENTE {{BANK_SIZE}} QUESTÕES (NUM_QUESTIONS + 10)
        
        Distribuição de dificuldade baseada em {{DIFFICULTY}}:
        - easy: 70% fácil, 25% média, 5% difícil
        - medium: 30% fácil, 50% média, 20% difícil  
        - hard: 20% fácil, 30% média, 50% difícil
        
        Distribuição de tipos baseada em {{DIFFICULTY}}:
        - easy: 60% T/F, 30% Multi, 10% Match
        - medium: 60% Multi, 25% T/F, 15% Match
        - hard: 70% Multi, 20% Match, 10% T/F
        
        Cobertura: TODO o documento do início ao fim
        */
      ]
    }
  ],
  
  "activities": [
    {
      "type": "page",
      "name": "📘 Introdução ao [NOME DO CURSO]",
      "content": "<div class=\"ailms-page-container\">
        <h2>Bem-vindo ao Curso</h2>
        <p>[Apresentação adaptada à duração e conteúdo]</p>
        <div class=\"ailms-info-box\">
          <h3>🎯 Objetivos de Aprendizagem</h3>
          <ul>
            <li>Objetivo 1 baseado no conteúdo</li>
            <li>Objetivo 2</li>
          </ul>
        </div>
      </div>"
    },
    
    /* PÁGINAS DE CONTEÚDO PRINCIPAL
       Quantidade e densidade baseadas em {{DURATION}}:
       
       flash: 
         - 4-6 slides → 1 página
         - 400-600 palavras
         - Elementos mínimos (1 info-box, sem quick-checks)
       
       standard:
         - 2-4 slides → 1 página  
         - 600-900 palavras
         - Elementos equilibrados (info-box, dicas, quick-checks)
       
       deep:
         - 1-2 slides → 1 página
         - 900-1500 palavras
         - Elementos máximos (múltiplos info-boxes, deep-dives, further-reading, quick-checks)
    */
    
    {
      "type": "page",
      "name": "📚 Resumo e Glossário",
      "content": "<div class=\"ailms-page-container\">
        <h2>Resumo do Curso</h2>
        <p>[Síntese dos pontos principais cobrindo TODO o documento]</p>
        
        <h3>📖 Glossário - Termos-Chave</h3>
        <div class=\"ailms-info-box\">
          <ul>
            <li><strong>Termo 1</strong>: Definição baseada no documento</li>
            <li><strong>Termo 2</strong>: Definição</li>
            <li>[Incluir 10-15 termos principais]</li>
          </ul>
        </div>
      </div>"
    },
    
    {
      "type": "quiz",
      "name": "🎯 Avaliação Final Abrangente",
      "intro": "Avaliação final do curso. Responda a {{NUM_QUESTIONS}} questões sorteadas aleatoriamente de um banco de {{BANK_SIZE}}. Nível de dificuldade: {{DIFFICULTY}}. Nota mínima: 15.0 (75% de acerto). Tentativas permitidas: 3.",
      "grade": 20.0,
      "passing_score": 15.0,
      "max_attempts": 3,
      "questions_from_bank": {
        "bank_name": "Banco AI - [NOME DO CURSO]",
        "count": {{NUM_QUESTIONS}}
      }
    },
    
    {
      "type": "page",
      "name": "✅ Conclusão e Próximos Passos",
      "content": "<div class=\"ailms-page-container\">
        <h2>🎉 Parabéns pela Conclusão!</h2>
        <p>[Mensagem de conclusão adaptada ao curso]</p>
        
        <div class=\"ailms-info-box\">
          <h3>📚 Próximos Passos</h3>
          <ul>
            <li>Rever os tópicos onde teve dificuldades</li>
            <li>Aplicar os conhecimentos em projetos práticos</li>
            <li>[Sugestões específicas baseadas no conteúdo]</li>
          </ul>
        </div>
        
        <div class=\"ailms-dica\">
          <strong>💡 Recomendação Final:</strong> [Dica personalizada baseada no tema do curso]
        </div>
      </div>"
    }
  ]
}
```

---

## ✅ CHECKLIST FINAL DE QUALIDADE

### **Configuração Dinâmica**
- [ ] Variável `{{DURATION}}` foi aplicada à densidade de conteúdo?
- [ ] Variável `{{DIFFICULTY}}` foi respeitada na distribuição de questões?
- [ ] Variável `{{NUM_QUESTIONS}}` está correta no campo `count` do quiz?
- [ ] Variável `{{BANK_SIZE}}` = NUM_QUESTIONS + 10 foi calculada corretamente?
- [ ] Campo `configuration` está presente no JSON com todas as variáveis?

### **Conteúdo Adaptado por Duração**
- [ ] Densidade de texto compatível com {{DURATION}}?
  - flash: 400-600 palavras/página
  - standard: 800-1000 palavras/página
  - deep: 1200-1500 palavras/página
- [ ] Agrupamento de slides adequado à duração escolhida?
- [ ] Elementos CSS extras (deep-dive, further-reading) apenas em "deep"?
- [ ] Quick-checks removidos em "flash"?
- [ ] TODAS as páginas/slides do documento foram cobertas?
- [ ] Detalhes técnicos preservados (fórmulas, dados, procedimentos)?
- [ ] Exemplos do documento incluídos nas páginas apropriadas?

### **Design e Formatação**
- [ ] Todas as páginas usam `ailms-page-container`?
- [ ] Conceitos-chave dentro de `ailms-info-box`?
- [ ] Imagens dentro de `ailms-img-container` com legenda?
- [ ] Tabelas complexas como `[[TABLE_Pxx]]`?
- [ ] Logos/elementos repetitivos foram ignorados?

### **Recursos Visuais**
- [ ] Todas as imagens REAIS têm placeholder `[[IMG_Pxx_yy]]`?
- [ ] Cada placeholder tem legenda descritiva?
- [ ] Sequência `yy` começa em 00 e incrementa por página?
- [ ] Palavra-chave `desc` facilita identificação posterior?

### **Banco de Questões**
- [ ] Total de questões = {{BANK_SIZE}}? (NUM_QUESTIONS + 10)
- [ ] Distribuição de dificuldade segue tabela de {{DIFFICULTY}}?
  - easy: 70% fácil, 25% média, 5% difícil
  - medium: 30% fácil, 50% média, 20% difícil
  - hard: 20% fácil, 30% média, 50% difícil
- [ ] Distribuição de tipos segue tabela de {{DIFFICULTY}}?
  - easy: 60% T/F, 30% Multi, 10% Match
  - medium: 60% Multi, 25% T/F, 15% Match
  - hard: 70% Multi, 20% Match, 10% T/F
- [ ] Questões cobrem TODO o documento (não só início)?
- [ ] Feedback detalhado e pedagógico em TODAS as respostas?
- [ ] Quiz tem `passing_score: 15.0` e `max_attempts: 3`?
- [ ] Campo `count` do quiz = {{NUM_QUESTIONS}}?

### **Estrutura JSON**
- [ ] Campo `source_file` presente e correto?
- [ ] Campo `configuration` com todas as variáveis dinâmicas?
- [ ] Array `activities` contém: Intro + Conteúdo + Resumo + Quiz + Conclusão?
- [ ] Quiz Final está ANTES da página de Conclusão?
- [ ] JSON termina corretamente com `]}`?
- [ ] Sem erros de sintaxe (vírgulas, aspas, colchetes)?

---

## 🚀 INSTRUÇÕES FINAIS DE EXECUÇÃO

Analisa o documento anexado e gera o JSON COMPLETO seguindo RIGOROSAMENTE todas estas diretrizes. 

**Lembra-te:**
- **Completude > Brevidade** (exceto em modo "flash" onde brevidade é o objetivo)
- **Precisão técnica > Simplificação**
- **JSON válido e fechado > Resposta cortada**
- **Respeitar {{DURATION}}** na densidade e agrupamento
- **Respeitar {{DIFFICULTY}}** na distribuição de questões
- **Gerar {{BANK_SIZE}} questões** (NUM_QUESTIONS + 10)

**Validação interna obrigatória antes de devolver:**
1. Densidade adequada para {{DURATION}}?
2. Distribuição correta para {{DIFFICULTY}}?
3. Total de questões = {{BANK_SIZE}}?
4. JSON estruturalmente completo?

**Se todas as validações passarem, devolve o JSON completo pronto para importação no Moodle.**
