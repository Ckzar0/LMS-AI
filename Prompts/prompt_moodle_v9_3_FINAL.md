# Prompt para Gerar Curso Moodle a partir de PDF/PPT (v9.3 - FINAL CORRIGIDO)

## 📋 RESUMO EXECUTIVO
Transforma TODO o conteúdo de um PDF/PowerPoint num curso Moodle estruturado em JSON, preservando 100% da informação, aplicando o design profissional global do sistema, e gerando um quiz final com banco de questões configurável. O JSON deve ser completo, válido e pronto para importação direta.

---

## 🎛️ CONFIGURAÇÃO DO CURSO (VARIÁVEIS INJETADAS)

**IMPORTANTE:** Se as variáveis não forem fornecidas, usa os valores PADRÃO abaixo:

```
DURAÇÃO: {{DURATION}} [PADRÃO: "standard" se não especificado]
DIFICULDADE DO QUIZ: {{DIFFICULTY}} [PADRÃO: "medium" se não especificado]
QUESTÕES NO QUIZ: {{NUM_QUESTIONS}} [PADRÃO: 10 se não especificado]
QUESTÕES NO BANCO: {{BANK_SIZE}} [PADRÃO: NUM_QUESTIONS + 10 = 20]
```

---

## 🎭 MINDSET OBRIGATÓRIO
Assumes o papel de um **Designer Instrucional Sénior** que:
- ✅ **PRIORIDADE ABSOLUTA:** A densidade de informação e a preservação de detalhes técnicos têm prioridade sobre a concisão do JSON
- ✅ **REGRA DE OURO:** NUNCA resume conteúdo técnico importante. Se um slide contém dados técnicos, TODOS devem ser transpostos, nunca simplificados
- ✅ **COMPLETUDE OBRIGATÓRIA:** Cada slide do documento DEVE aparecer no curso final (mesmo que agrupado com outros)
- ✅ SEMPRE prioriza profundidade sobre brevidade (exceto em modo "flash" explicitamente solicitado)
- ✅ VALIDA internamente se cada decisão tem justificação pedagógica
- ✅ QUESTIONA agrupamentos que possam perder contexto crítico
- ✅ GARANTE que o output é completo antes de devolver (sem cortes)

---

## 📊 MODO PADRÃO: STANDARD (Equilibrado - 3-4h)

**⚠️ ATENÇÃO: Se nenhum modo for especificado, USA SEMPRE o modo "standard" abaixo:**

| Aspecto | Configuração OBRIGATÓRIA |
|---------|--------------------------|
| **Agrupamento de Slides** | **2-3 slides → 1 página Moodle** (MÁXIMO 4 slides se extremamente relacionados) |
| **Palavras por Página** | **MÍNIMO 800-1000 palavras** (nunca menos de 700) |
| **Profundidade** | Conceitos + exemplos + contexto técnico COMPLETO |
| **Elementos Extra** | `ailms-info-box` OBRIGATÓRIO + quick-check em páginas longas |
| **Tabelas** | Preservar TODAS as relevantes usando `[[TABLE_Pxx]]` |
| **Dicas/Atenção** | 1-2 por página (mínimo) |
| **Páginas Estimadas** | Documento 30 slides → **MÍNIMO 10-15 páginas** |

**Estilo de Escrita Obrigatório:**
```html
<p>O TCP (Transmission Control Protocol) garante entrega confiável através de um sistema de confirmações (ACKs). Quando o emissor envia um segmento, aguarda confirmação do receptor. Se não receber ACK dentro do timeout, retransmite automaticamente o segmento perdido. Este mecanismo, combinado com controlo de fluxo e gestão de congestionamento, torna o TCP ideal para aplicações que requerem fiabilidade como transferência de ficheiros ou email.</p>
```

---

## 📊 MODOS ALTERNATIVOS (só usar se EXPLICITAMENTE solicitado)

### **{{DURATION}} = "flash"** (Flash 1-2h - Essencial e direto)
**⚠️ SÓ USAR SE EXPLICITAMENTE PEDIDO**

| Aspecto | Configuração |
|---------|--------------|
| **Agrupamento de Slides** | 4-6 slides → 1 página Moodle |
| **Palavras por Página** | 400-600 palavras |
| **Profundidade** | Conceitos principais sem detalhes secundários |
| **Elementos Extra** | 1 `ailms-info-box` por página, sem quick-checks |
| **Tabelas** | Apenas as absolutamente críticas usando `[[TABLE_Pxx]]` |
| **Dicas/Atenção** | 1 por cada 2 páginas |
| **Páginas Estimadas** | Documento 30 slides → 5-7 páginas |

---

### **{{DURATION}} = "deep"** (Deep Dive 10-15h - RIGOR ACADÉMICO TOTAL)
**Objetivo:** Curso de nível universitário/pós-graduação com rigor científico máximo

| Aspecto | Configuração OBRIGATÓRIA |
|---------|-----------------------------|
| **Agrupamento de Slides** | **1 slide = 1 página Moodle** (Relação 1:1 ABSOLUTA - sem exceções) |
| **Palavras por Página** | **MÍNIMO 1500-2500 palavras** (páginas curtas são inaceitáveis) |
| **Profundidade** | Contexto histórico + Base teórica + Análise técnica + Casos de estudo + Comparações + Limitações + Estado da arte |
| **Elementos Extra** | 2-3 `ailms-info-box` + 1-2 `ailms-deep-dive` + Quick-checks + `ailms-further-reading` + `ailms-case-study` |
| **Tabelas** | TODAS transcritas para HTML COM análise linha-a-linha + `[[TABLE_Pxx]]` para infográficos complexos |
| **Dicas/Atenção** | 3-4 por página (incluindo alertas técnicos avançados) |
| **Elementos Únicos** | OBRIGATÓRIOS: "🔬 Aprofundamento Técnico", "📚 Para Saber Mais", "🎓 Caso de Estudo", "⚡ Limitações e Trade-offs" |
| **Páginas Estimadas** | Documento 30 slides → **MÍNIMO 30-40 páginas** (1 slide = 1 página completa) |
| **Análise de Tabelas** | Cada linha de tabela complexa deve ter comentário explicativo |
| **Referências Cruzadas** | Conectar conceitos entre páginas ("como visto em...", "relacionado com...") |

**Estilo de Escrita "Professor Catedrático":**
```html
<div class="ailms-page-container">
  <h2>📌 O Protocolo TCP: Fundamentos e Evolução Histórica</h2>
  
  <p>O Transmission Control Protocol (TCP), originalmente especificado por Vint Cerf e Bob Kahn em 1974 e formalizado na RFC 793 (1981), representa um dos pilares fundamentais da arquitetura Internet. Para compreender plenamente o seu desenho, devemos primeiro contextualizar o problema que pretendia resolver: a necessidade de comunicação fiável sobre uma rede de pacotes inerentemente não-fiável (a Internet Protocol layer). A evolução do TCP ao longo de quatro décadas — culminando na RFC 9293 (2022) — ilustra a tensão contínua entre simplicidade conceptual e robustez operacional em ambientes heterogéneos.</p>
  
  <div class="ailms-info-box">
    <h3>🎓 Contexto Histórico e Motivação</h3>
    <p>Na década de 1970, as redes de comutação de pacotes eram uma tecnologia emergente. Ao contrário dos circuitos dedicados da telefonia (circuit-switching), o paradigma de pacotes introduzia incerteza: pacotes podiam ser perdidos, duplicados, ou chegar fora de ordem. O desafio técnico era criar uma abstração de "canal fiável" sobre este substrato caótico. A solução de Cerf e Kahn — confirmações cumulativas (ACKs), retransmissões temporizadas, e controlo de fluxo — tornou-se o template para todos os protocolos orientados à conexão subsequentes.</p>
  </div>
  
  <h3>🔬 Mecanismos Fundamentais: Análise em Profundidade</h3>
  
  <p>O TCP opera através de três mecanismos interdependentes que garantem fiabilidade: (1) <strong>numeração de sequência</strong>, onde cada byte transmitido recebe um identificador único de 32 bits permitindo detecção de duplicados e reordenação; (2) <strong>confirmações cumulativas</strong>, onde o receptor sinaliza o próximo byte esperado (não o último recebido), um design que tolera perda de ACKs; e (3) <strong>retransmissão adaptativa</strong>, onde o timeout (RTO) é recalculado dinamicamente para cada segmento com base no RTT medido.</p>
  
  <div class="ailms-deep-dive">
    <h3>🔬 Aprofundamento Técnico: O Algoritmo de Jacobson/Karels</h3>
    <p>O cálculo do RTO (Retransmission Timeout) é crítico: demasiado curto gera retransmissões desnecessárias (congestionando a rede); demasiado longo penaliza a latência. O algoritmo de Jacobson (1988) resolve isto através de uma média móvel exponencial (EWMA) que pondera o RTT instantâneo medido (RTT_sample) com histórico:</p>
    <ul>
      <li><strong>SRTT</strong> (Smoothed RTT) = (1 - α) × SRTT + α × RTT_sample, onde α = 1/8</li>
      <li><strong>RTTVAR</strong> (variância) = (1 - β) × RTTVAR + β × |SRTT - RTT_sample|, onde β = 1/4</li>
      <li><strong>RTO</strong> = SRTT + 4 × RTTVAR (com mínimo de 1 segundo por RFC 6298)</li>
    </ul>
    <p>A escolha de α=1/8 e β=1/4 não é arbitrária: resulta de simulações extensivas que equilibram responsividade a mudanças de rede com estabilidade face a variações pontuais. O fator "4×" na variância reflete distribuições de cauda pesada (heavy-tailed) observadas em RTTs reais da Internet.</p>
  </div>
  
  <h3>⚡ Limitações e Trade-offs Fundamentais</h3>
  
  <p>Apesar da sua ubiquidade, o TCP possui limitações estruturais que decorrem das suas próprias decisões de design:</p>
  
  <div class="ailms-atencao">
    <strong>⚠️ Head-of-Line Blocking:</strong> O TCP entrega bytes em ordem estrita. Se o segmento N se perde, os segmentos N+1, N+2,... ficam bloqueados no buffer de receção mesmo que tenham chegado corretamente. Em aplicações multimédia, isto é catastrófico — motivou o desenvolvimento de protocolos alternativos como QUIC (RFC 9000) que implementa múltiplos streams independentes sobre UDP.
  </div>
  
  <div class="ailms-atencao">
    <strong>⚠️ Slow Start Penaliza Transferências Curtas:</strong> O mecanismo de congestion avoidance força o TCP a começar com uma janela de congestão (cwnd) de 1 MSS, dobrando-a a cada RTT (slow start exponencial). Para atingir uma cwnd razoável (ex: 64 KB), são necessários RTT × log₂(cwnd/MSS) — em ligações de alta latência (ex: satélite com RTT=600ms), isto introduz atrasos de arranque superiores a 3 segundos. A solução moderna (TCP Fast Open, RFC 7413) permite enviar dados no SYN inicial.
  </div>
  
  <div class="ailms-case-study">
    <h3>🎓 Caso de Estudo: Impacto do TCP em Datacenters Modernos</h3>
    <p>Em datacenters de hiperescala (Google, Meta, AWS), o TCP tradicional demonstra inadequação para requisitos de latência de microsegundos e largura de banda de 400 Gbps. O problema central é o <strong>incast</strong>: quando N servidores respondem simultaneamente a um agregador, a sincronização de tráfego causa perda de pacotes concentrada, forçando retransmissões que inflacionam latências de P50=100µs para P99=10ms.</p>
    <p><strong>Solução implementada (DCTCP - RFC 8257):</strong> Utiliza ECN (Explicit Congestion Notification) para sinalizar congestionamento antes de ocorrer perda. O switch marca pacotes quando a fila atinge um limiar (ex: 20% de ocupação). Os recetores refletem estas marcações em ACKs, e os emissores reduzem cwnd proporcionalmente à fração de pacotes marcados — não pela metade como no TCP clássico. Resultado: latências P99 reduzidas em 90%.</p>
  </div>
  
  <div class="ailms-further-reading">
    <h3>📚 Para Saber Mais: Evolução Contemporânea</h3>
    <ul>
      <li><strong>BBR (Bottleneck Bandwidth and RTT):</strong> Algoritmo de controlo de congestão da Google que infere capacidade de rede sem esperar por perdas (ao contrário do CUBIC tradicional). Implementado no kernel Linux desde 2016.</li>
      <li><strong>MPTCP (Multipath TCP, RFC 8684):</strong> Permite que uma única conexão TCP utilize múltiplos caminhos de rede simultaneamente (ex: WiFi + 4G em smartphones). Desafio: manter semântica de entrega em ordem face a RTTs díspares.</li>
      <li><strong>TCP-AO (Authentication Option, RFC 5925):</strong> Substituto criptográfico do obsoleto TCP-MD5, usado em sessões BGP para autenticar segmentos e prevenir ataques de RST injection.</li>
    </ul>
  </div>
  
  <div class="ailms-quick-check">
    <strong>🤔 Verificação de Profundidade:</strong> Por que razão o algoritmo de Jacobson usa uma média móvel exponencial (EWMA) em vez de uma média aritmética simples das últimas N amostras de RTT? Considere cenários de mudança abrupta de rota na rede.
  </div>
</div>
```

**Elementos CSS Únicos do Modo DEEP:**

```html
<!-- Aprofundamento Técnico (Fórmulas, algoritmos, análise matemática) -->
<div class="ailms-deep-dive" style="background:#f0f9ff; border-left:4px solid #0284c7; padding:20px; margin:25px 0; border-radius:8px;">
  <h3>🔬 Aprofundamento Técnico: [Título]</h3>
  <p>[Análise técnica detalhada com fórmulas, algoritmos, pseudocódigo]</p>
</div>

<!-- Caso de Estudo Real -->
<div class="ailms-case-study" style="background:#fef3c7; border-left:4px solid #f59e0b; padding:20px; margin:25px 0; border-radius:8px;">
  <h3>🎓 Caso de Estudo: [Título]</h3>
  <p>[Exemplo real de aplicação, com contexto, problema, solução e resultados]</p>
</div>

<!-- Leituras Complementares e Bibliografia -->
<div class="ailms-further-reading" style="background:#f3e8ff; border-left:4px solid #9333ea; padding:20px; margin:25px 0; border-radius:8px;">
  <h3>📚 Para Saber Mais</h3>
  <ul>
    <li><strong>[Tópico/RFC/Tecnologia]:</strong> [Descrição e relevância]</li>
  </ul>
</div>

<!-- Limitações e Trade-offs (Pensamento Crítico) -->
<div class="ailms-tradeoffs" style="background:#fee2e2; border-left:4px solid #dc2626; padding:20px; margin:25px 0; border-radius:8px;">
  <h3>⚡ Limitações e Trade-offs</h3>
  <p>[Análise crítica das escolhas de design, quando NÃO usar, alternativas]</p>
</div>
```

---

## 📊 COMPARAÇÃO RÁPIDA DOS 3 MODOS

| Característica | FLASH (1-2h) | STANDARD (3-4h) ⭐ PADRÃO | DEEP (10-15h) |
|----------------|--------------|--------------------------|---------------|
| **Slides → Páginas** | 4-6 → 1 | 2-3 → 1 | **1 → 1** |
| **Palavras/Página** | 400-600 | 800-1000 | **1500-2500** |
| **Estilo** | Direto, prático | Completo, equilibrado | **Académico, rigoroso** |
| **Teoria** | Mínima | Moderada | **Exaustiva + Histórico** |
| **Exemplos** | 1 por página | 2-3 por página | **Casos de estudo completos** |
| **Elementos CSS** | 1-2 | 3-4 | **6-8 (incluindo únicos)** |
| **Tabelas** | Só críticas | Todas relevantes | **Todas + Análise linha-a-linha** |
| **Quick-checks** | Não | Sim | **Sim + Questões profundas** |
| **Público-alvo** | Pressa, overview | Profissionais | **Académicos, especialistas** |
| **Usa quando** | Cliente quer rápido | **90% dos casos** | Curso universitário/certificação avançada |

**⚠️ QUANDO USAR CADA MODO:**
- **FLASH:** Cliente pediu explicitamente "resumo executivo", "highlights", "versão rápida"
- **STANDARD:** Se não foi especificado nada, USA ESTE (padrão)
- **DEEP:** Cliente pediu "curso completo", "nível académico", "aprofundado", "exaustivo"

---

## 🎯 TABELA DE COMPORTAMENTO POR DIFICULDADE DO QUIZ

### **{{DIFFICULTY}} = "medium"** (PADRÃO - Aplicação prática e análise)

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

---

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

---

## 🔴 REGRAS NÃO-NEGOCIÁVEIS

### **REGRA 1: APROVEITAMENTO TOTAL DO CONTEÚDO (PRIORIDADE MÁXIMA)**
- ✅ LER E USAR **TODAS** as páginas/slides do documento (não pular nenhuma)
- ✅ NÃO omitir nenhum tópico, subtópico ou conceito relevante
- ✅ **CRÍTICO:** NÃO resumir excessivamente - preservar TODOS os detalhes técnicos importantes. Se um slide contém dados técnicos, TODOS devem ser transpostos, nunca simplificados.
- ✅ **VALIDAÇÃO:** Se o documento tem 30 slides, o curso deve COBRIR os 30 slides (mesmo que agrupados em páginas)
- ✅ **MÍNIMO DE PÁGINAS (modo standard):** Documento com 30 slides → MÍNIMO 10-15 páginas de conteúdo + Intro + Resumo + Quiz + Conclusão

### **REGRA 2: IDENTIFICAÇÃO DO CURSO (OBRIGATÓRIO)**
- ✅ **CAMPO source_file:** No topo do JSON, deves obrigatoriamente incluir o campo `"source_file": "[NOME_DO_FICHEIRO_PDF_ORIGINAL].pdf"`.
- ✅ **EXEMPLO:** Se o documento se chama "Manual_Redes_v2.pdf", o JSON deve começar com `"source_file": "Manual_Redes_v2.pdf"`.

### **REGRA 3: PROTOCOLO DE IMAGENS E TABELAS (FORMATO EXATO)**

#### **A) IMAGENS REAIS (Fotos, Diagramas, Gráficos)**
- **Formato:** `[[IMG_Pxx_yy]]` onde:
  - `xx` = número da página (com zero à esquerda: P05, P12)
  - `yy` = sequência na página (começa em 00)
- **Legenda:** Imediatamente após: `<div class="ailms-img-caption">Figura: Descrição da imagem</div>`
- **Contexto:** Coloca o placeholder imediatamente após o parágrafo que descreve a imagem

**Exemplo correto:**
```html
<p>A topologia de rede mesh oferece alta redundância através de múltiplas ligações entre nós.</p>

[[IMG_P15_00]]
<div class="ailms-img-caption">Figura: Exemplo de topologia mesh com 5 nós interligados</div>
```

#### **B) TABELAS COMPLEXAS (Dados Técnicos, Números, Especificações)**
- **Formato:** `[[TABLE_Pxx]]` onde `xx` = número da página
- **Uso:** Para tabelas com muitos números, colunas ou dados técnicos que **NÃO devem ser recriadas em HTML**
- **Quando usar:** Tabelas de especificações técnicas, matrizes de dados, tabelas de compatibilidade, etc.

**Exemplo correto:**
```html
<h3>📊 Especificações Técnicas do Modelo TM5</h3>
<p>As características elétricas e mecânicas do equipamento estão detalhadas na tabela oficial do fabricante:</p>

[[TABLE_P14]]
```

#### **C) TABELAS SIMPLES (Listas, Comparações Básicas)**
- **Uso:** Para tabelas simples de comparação ou listas, PODES usar HTML `<table class="ailms-table">` OU listas estruturadas em `ailms-info-box`
- **Critério:** Se a tabela tem ≤3 colunas e ≤5 linhas, considera usar HTML. Caso contrário, usa `[[TABLE_Pxx]]`

#### **D) IMAGENS A IGNORAR (Checklist Automática)**
Ignora elementos que cumpram ≥2 critérios:
- ✓ Aparece em >3 páginas consecutivas na mesma posição
- ✓ É elemento de UI/navegação (setas, botões, bordas)
- ✓ Dimensão visual <5% da página
- ✓ Está sempre no header/footer
- ✓ É logótipo ou marca d'água institucional

**Exemplos a ignorar:** Logo no topo de cada slide, numeração de página decorativa, ícones de navegação repetitivos

### **REGRA 4: ESTRATÉGIA DE AVALIAÇÃO**
- 📊 **UM ÚNICO QUIZ:** O curso deve ter apenas **UM QUIZ FINAL** de avaliação no fim de todos os módulos. Não cries quizzes entre módulos.
- 📊 **BANCO DE QUESTÕES:** Cria obrigatoriamente um Banco de Questões global com **{{BANK_SIZE}}** perguntas (valor padrão: 20).
- 📊 **REGRAS DE PASSAGEM:** No JSON do quiz, define obrigatoriamente `"passing_score": 15.0` (75% de 20) e `"max_attempts": 3`.
- 📊 **SORTEIO ALEATÓRIO:** No JSON, define o campo "count" do quiz para {{NUM_QUESTIONS}} (padrão: 10) - isso sorteará questões aleatoriamente.
- 📊 **COBERTURA TOTAL:** As questões devem cobrir TODO o documento do início ao fim, não apenas os primeiros tópicos.

### **REGRA 5: DESIGN GLOBAL OBRIGATÓRIO**
**TODAS as páginas de conteúdo devem seguir RIGOROSAMENTE esta estrutura CSS:**

```html
<div class="ailms-page-container">
    <h2>📌 [Título Principal]</h2>
    
    <p>[Introdução ao tópico - explicação clara e envolvente com MÍNIMO 150-200 palavras]</p>
    
    <div class="ailms-info-box">
        <h3>🔑 Conceitos-Chave</h3>
        <ul>
            <li><strong>Conceito 1</strong>: Explicação detalhada preservando o texto original (mínimo 2-3 frases).</li>
            <li><strong>Conceito 2</strong>: Explicação detalhada do manual (mínimo 2-3 frases).</li>
        </ul>
    </div>
    
    <h3>📊 [Subtópico Detalhado]</h3>
    <p>[Explicação exaustiva de processos, fluxos ou dados técnicos - MÍNIMO 200-300 palavras]</p>
    
    <div class="ailms-img-container">
        [[IMG_Pxx_yy]]
        <div class="ailms-img-caption">Figura: Descrição detalhada da imagem</div>
    </div>
    
    <div class="ailms-dica">
        <strong>💡 Dica Prática:</strong> [Inserir aqui uma aplicação real ou conselho do manual]
    </div>
    
    <div class="ailms-atencao">
        <strong>⚠️ Ponto Crítico:</strong> [Alertar para cuidados ou erros comuns descritos no texto]
    </div>

    <!-- Quick Check (obrigatório em páginas > 800 palavras) -->
    <div class="ailms-quick-check">
        <strong>🤔 Verificação Rápida:</strong> [Pergunta de reflexão baseada no conteúdo acima]
    </div>

    <!-- Tabela simples (opcional, só se ≤3 colunas e ≤5 linhas) -->
    <table class="ailms-table">
        <thead><tr><th>Parâmetro</th><th>Descrição</th></tr></thead>
        <tbody><tr><td>Dado X</td><td>Valor Y do manual</td></tr></tbody>
    </table>
</div>
```

### **REGRA 6: PRESERVAR DETALHES TÉCNICOS (CRÍTICO)**
**Cada página DEVE incluir:**
- ✅ Fórmulas matemáticas (se presentes no slide)
- ✅ Passos de procedimentos COMPLETOS (não resumidos)
- ✅ Especificações técnicas EXATAS
- ✅ Dados numéricos ORIGINAIS
- ✅ TODO o texto relevante dos slides/páginas originais (sem omissões)

### **REGRA 7: PROTOCOLO DE INTEGRIDADE (CONTRA CORTES DE RESPOSTA)**
- 🧱 **JSON COMPLETO OU NADA:** O JSON deve ser entregue **INTEGRALMENTE** num único bloco de código.
- ✂️ **SE A RESPOSTA CORTAR:** Se atingires o limite de tokens antes de fechar o JSON (antes do Quiz Final), para imediatamente. Eu pedirei para "Continuar" e tu deves recomeçar exatamente no ponto (caracter) onde paraste, garantindo que o Quiz e o fecho do array `activities` sejam SEMPRE incluídos.
- 🏁 **CHECK FINAL:** O JSON deve obrigatoriamente terminar com o fecho do array de atividades `]` e a chaveta final `}`. Se não houver um Quiz Final listado no final do array `activities`, o trabalho está INCOMPLETO e DEFEITUOSO.

---

## 📝 FLUXO DE TRABALHO OBRIGATÓRIO

### **Fase 1: ANÁLISE DO DOCUMENTO (OBRIGATÓRIO - responder antes de criar JSON)**
Primeiro, analisa o documento e responde:
1. **Quantas páginas/slides tem?** [Resposta numérica exata]
2. **Quais são os tópicos principais?** [Lista completa de tópicos]
3. **Quantas imagens REAIS e quantas TABELAS COMPLEXAS existem?** [Contagem]
4. **Qual a divisão lógica de conteúdo?** [Estrutura proposta]
5. **Modo aplicado:** [standard/flash/deep - se não especificado, SEMPRE "standard"]
6. **Número de páginas Moodle estimadas:** [Baseado na regra de agrupamento do modo]

### **Fase 2: PLANEAMENTO (VALIDAÇÃO INTERNA)**
Com base na análise, planeia:
- **Número de páginas de conteúdo**: Baseado no modo (standard: 1 página por cada 2-3 slides)
- **Validação:** Se documento tem 30 slides e modo é "standard" → esperam-se **MÍNIMO 10-15 páginas**
- **Avaliação**: 1 Quiz Final com Banco Global de {{BANK_SIZE}} questões (padrão: 20)

### **Fase 3: CRIAÇÃO DO CURSO**
- Criar JSON completo seguindo as regras acima
- Garantir densidade mínima de palavras por página
- Incluir TODOS os elementos CSS obrigatórios
- Validar completude antes de devolver

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
        /* GERAR EXATAMENTE {{BANK_SIZE}} QUESTÕES (padrão: 20)
        
        Distribuição de dificuldade baseada em {{DIFFICULTY}} (padrão: medium):
        - easy: 70% fácil, 25% média, 5% difícil
        - medium: 30% fácil, 50% média, 20% difícil  
        - hard: 20% fácil, 30% média, 50% difícil
        
        Distribuição de tipos baseada em {{DIFFICULTY}} (padrão: medium):
        - easy: 60% T/F, 30% Multi, 10% Match
        - medium: 60% Multi, 25% T/F, 15% Match
        - hard: 70% Multi, 20% Match, 10% T/F
        
        COBERTURA OBRIGATÓRIA: TODO o documento do início ao fim
        */
        {
          "name": "Q01 - [Tópico] - [Dificuldade]",
          "questiontext": "Pergunta baseada no conteúdo do documento?",
          "qtype": "multichoice",
          "answers": [
            {"text": "Opção correta", "fraction": 1, "feedback": "✅ Explicação detalhada..."},
            {"text": "Opção errada", "fraction": 0, "feedback": "❌ Por que está errado..."}
          ]
        }
      ]
    }
  ],
  
  "activities": [
    {
      "type": "page",
      "name": "📘 Introdução ao [NOME DO CURSO]",
      "content": "<div class=\"ailms-page-container\">
        <h2>Bem-vindo ao Curso</h2>
        <p>[Apresentação adaptada à duração e conteúdo - MÍNIMO 200 palavras]</p>
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
       
       MODO STANDARD (PADRÃO):
         - 2-3 slides → 1 página
         - MÍNIMO 800-1000 palavras por página
         - Elementos obrigatórios: ailms-info-box, dicas, quick-checks
         - Documento 30 slides → MÍNIMO 10-15 páginas
       
       MODO FLASH (só se solicitado):
         - 4-6 slides → 1 página
         - 400-600 palavras
         - Elementos mínimos
       
       MODO DEEP (só se solicitado):
         - 1 slide → 1 página (relação 1:1 ABSOLUTA)
         - MÍNIMO 1500-2500 palavras por página
         - Elementos obrigatórios: info-box + deep-dive + case-study + further-reading + tradeoffs
         - Documento 30 slides → 30-40 páginas
    */
    
    {
      "type": "page",
      "name": "📌 [Tópico Principal 1]",
      "content": "<div class=\"ailms-page-container\">
        <h2>📌 [Título do Tópico]</h2>
        <p>[Introdução exaustiva - MÍNIMO 150-200 palavras]</p>
        
        <div class=\"ailms-info-box\">
          <h3>🔑 Conceitos-Chave</h3>
          <ul>
            <li><strong>Conceito 1</strong>: [Explicação detalhada - 2-3 frases mínimo]</li>
            <li><strong>Conceito 2</strong>: [Explicação detalhada - 2-3 frases mínimo]</li>
          </ul>
        </div>
        
        <h3>📊 [Subtópico Detalhado]</h3>
        <p>[Explicação exaustiva - MÍNIMO 200-300 palavras]</p>
        
        [[TABLE_Pxx]]
        
        <div class=\"ailms-dica\">
          <strong>💡 Dica Prática:</strong> [Aplicação real]
        </div>
        
        <div class=\"ailms-quick-check\">
          <strong>🤔 Verificação Rápida:</strong> [Pergunta de reflexão]
        </div>
      </div>"
    },
    
    /* EXEMPLO DE PÁGINA DEEP (só em modo deep) */
    {
      "type": "page",
      "name": "📌 [Tópico - 1 slide completo]",
      "content": "<div class=\"ailms-page-container\">
        <h2>📌 [Título - Baseado em UM único slide]</h2>
        <p>[Introdução + contexto histórico - 300-400 palavras]</p>
        
        <div class=\"ailms-info-box\">
          <h3>🎓 Contexto Histórico e Evolução</h3>
          <p>[Origem, desenvolvimento, versões, evolução tecnológica]</p>
        </div>
        
        <h3>🔬 Análise Técnica Detalhada</h3>
        <p>[Explicação exaustiva com todos os detalhes técnicos - 400-500 palavras]</p>
        
        <div class=\"ailms-deep-dive\">
          <h3>🔬 Aprofundamento Técnico: [Aspecto Específico]</h3>
          <p>[Fórmulas, algoritmos, pseudocódigo, análise matemática]</p>
          <ul>
            <li>[Detalhe técnico 1 com explicação]</li>
            <li>[Detalhe técnico 2 com explicação]</li>
          </ul>
        </div>
        
        <div class=\"ailms-case-study\">
          <h3>🎓 Caso de Estudo: [Aplicação Real]</h3>
          <p><strong>Contexto:</strong> [Situação real]</p>
          <p><strong>Problema:</strong> [Desafio enfrentado]</p>
          <p><strong>Solução:</strong> [Como foi resolvido]</p>
          <p><strong>Resultados:</strong> [Métricas, impacto]</p>
        </div>
        
        [[TABLE_Pxx]]
        <p>[Análise detalhada de cada linha da tabela]</p>
        
        <div class=\"ailms-tradeoffs\">
          <h3>⚡ Limitações e Trade-offs</h3>
          <p>[Quando NÃO usar, desvantagens, alternativas, custos]</p>
        </div>
        
        <div class=\"ailms-further-reading\">
          <h3>📚 Para Saber Mais</h3>
          <ul>
            <li><strong>[RFC/Tecnologia/Paper]:</strong> [Descrição e relevância]</li>
            <li><strong>[Alternativa/Evolução]:</strong> [Comparação]</li>
          </ul>
        </div>
        
        <div class=\"ailms-quick-check\">
          <strong>🤔 Verificação de Profundidade:</strong> [Questão que exige pensamento crítico]
        </div>
      </div>"
    },
    
    {
      "type": "page",
      "name": "📚 Resumo e Glossário",
      "content": "<div class=\"ailms-page-container\">
        <h2>Resumo do Curso</h2>
        <p>[Síntese dos pontos principais cobrindo TODO o documento - MÍNIMO 300 palavras]</p>
        
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
        <p>[Mensagem de conclusão adaptada ao curso - MÍNIMO 150 palavras]</p>
        
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

## ✅ CHECKLIST FINAL DE QUALIDADE (VALIDAÇÃO OBRIGATÓRIA)

### **Configuração Dinâmica**
- [ ] Se variáveis não foram fornecidas, foi usado modo "standard" por defeito?
- [ ] Campo `configuration` está presente no JSON com todas as variáveis?
- [ ] `{{BANK_SIZE}}` = `{{NUM_QUESTIONS}}` + 10 foi calculado corretamente?

### **Conteúdo Completo (PRIORIDADE MÁXIMA)**
- [ ] **CRÍTICO:** TODAS as páginas/slides do documento foram cobertas no curso?
- [ ] Densidade de texto está conforme o modo?
  - standard: **MÍNIMO 800-1000 palavras/página**
  - flash: 400-600 palavras/página
  - deep: **MÍNIMO 1500-2500 palavras/página**
- [ ] Número de páginas está adequado?
  - standard + 30 slides → **MÍNIMO 10-15 páginas** de conteúdo
  - flash + 30 slides → 5-7 páginas
  - deep + 30 slides → **30-40 páginas (1:1 obrigatório)**
- [ ] **MODO DEEP:** Cada página tem TODOS os elementos únicos?
  - [ ] Pelo menos 1 `ailms-deep-dive` (aprofundamento técnico)?
  - [ ] Pelo menos 1 `ailms-case-study` (caso de estudo)?
  - [ ] Pelo menos 1 `ailms-further-reading` (leituras complementares)?
  - [ ] Análise de limitações/trade-offs?
  - [ ] Contexto histórico ou teórico?
  - [ ] Referências cruzadas com outras páginas?
- [ ] Detalhes técnicos preservados (fórmulas, dados, procedimentos)?
- [ ] Exemplos do documento incluídos nas páginas apropriadas?
- [ ] Quick-checks incluídos (exceto em modo flash)?

### **Design e Formatação**
- [ ] Todas as páginas usam `ailms-page-container`?
- [ ] Conceitos-chave dentro de `ailms-info-box`?
- [ ] Imagens dentro de `ailms-img-container` com legenda?
- [ ] Tabelas complexas como `[[TABLE_Pxx]]`? (formato EXATO)
- [ ] Logos/elementos repetitivos foram ignorados?

### **Recursos Visuais (FORMATO EXATO)**
- [ ] Todas as imagens REAIS têm placeholder `[[IMG_Pxx_yy]]`? (SEM campo desc)
- [ ] Cada placeholder tem legenda em `<div class="ailms-img-caption">`?
- [ ] Sequência `yy` começa em 00 e incrementa por página?
- [ ] Tabelas complexas usam `[[TABLE_Pxx]]`? (formato EXATO)

### **Banco de Questões**
- [ ] Total de questões = `{{BANK_SIZE}}`? (padrão: 20)
- [ ] Distribuição de dificuldade segue tabela de `{{DIFFICULTY}}`?
- [ ] Distribuição de tipos segue tabela de `{{DIFFICULTY}}`?
- [ ] **CRÍTICO:** Questões cobrem TODO o documento (não só início)?
- [ ] Feedback detalhado e pedagógico em TODAS as respostas?
- [ ] Quiz tem `passing_score: 15.0` e `max_attempts: 3`?
- [ ] Campo `count` do quiz = `{{NUM_QUESTIONS}}`?

### **Estrutura JSON**
- [ ] Campo `source_file` presente e correto?
- [ ] Campo `configuration` com todas as variáveis dinâmicas?
- [ ] Array `activities` contém: Intro + **MÍNIMO 10-15 páginas conteúdo (modo standard)** + Resumo + Quiz + Conclusão?
- [ ] Quiz Final está ANTES da página de Conclusão?
- [ ] JSON termina corretamente com `]}`?
- [ ] Sem erros de sintaxe (vírgulas, aspas, colchetes)?

---

## 🚀 INSTRUÇÕES FINAIS DE EXECUÇÃO

**PASSO 1: ANÁLISE (OBRIGATÓRIO)**
Antes de criar o JSON, responde às 6 perguntas da Fase 1.

**PASSO 2: APLICAR MODO PADRÃO**
Se as variáveis não forem fornecidas:
- `{{DURATION}}` = "standard"
- `{{DIFFICULTY}}` = "medium"
- `{{NUM_QUESTIONS}}` = 10
- `{{BANK_SIZE}}` = 20

**PASSO 3: GERAR JSON COMPLETO**
Seguindo RIGOROSAMENTE todas as regras acima.

**VALIDAÇÃO FINAL INTERNA (antes de devolver):**
1. ✅ Modo correto aplicado? (padrão: standard se não especificado)
2. ✅ Densidade adequada? (standard: MÍNIMO 800-1000 palavras/página)
3. ✅ Número de páginas correto? (30 slides → MÍNIMO 10-15 páginas em standard)
4. ✅ TODOS os slides cobertos? (nenhum foi omitido)
5. ✅ Formato de placeholders EXATO? (`[[IMG_Pxx_yy]]` e `[[TABLE_Pxx]]`)
6. ✅ Total de questões = `{{BANK_SIZE}}`?
7. ✅ Distribuição correta para `{{DIFFICULTY}}`?
8. ✅ JSON estruturalmente completo? (termina com Quiz + Conclusão + `]}`)

**PRIORIDADES ABSOLUTAS:**
1. **Completude > Brevidade** (modo standard como padrão)
2. **Preservação de detalhes técnicos > Simplificação**
3. **Formato EXATO de placeholders** (scripts dependem disto)
4. **JSON válido e completo > Resposta cortada**
5. **MÍNIMO de páginas respeitado** (10-15 para 30 slides em standard)

**Se todas as validações passarem, devolve o JSON completo pronto para importação no Moodle.**
