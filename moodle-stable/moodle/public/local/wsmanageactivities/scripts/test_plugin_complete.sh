#!/bin/bash
# Teste completo do plugin local_wsmanageactivities
# Compatível com macOS e Linux

# Configurações
MOODLE_URL="http://localhost:8888/moodle500"
CREATION_TOKEN="4199cc05600eb0e28c4f6947b362aa98"
MOBILE_TOKEN="d1fcf3a7a21bb341c2831c90abd0d334"

# Detecção de SO para compatibilidade
detect_os() {
    if [[ "$OSTYPE" == "darwin"* ]]; then
        echo "macos"
    else
        echo "linux"
    fi
}

# Função curl compatível
safe_curl() {
    local url="$1"
    shift
    
    if [[ "$(detect_os)" == "macos" ]]; then
        curl -s "$url" "$@"
    else
        timeout 30 curl -s "$url" "$@"
    fi
}

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo_success() { echo -e "${GREEN}✅ $1${NC}"; }
echo_error() { echo -e "${RED}❌ $1${NC}"; }
echo_warning() { echo -e "${YELLOW}⚠️ $1${NC}"; }
echo_info() { echo -e "${BLUE}ℹ️ $1${NC}"; }

# Verificar se jq está disponível
if ! command -v jq &> /dev/null; then
    echo_error "jq não encontrado. Instalar: brew install jq (macOS) ou apt-get install jq (Linux)"
    exit 1
fi

echo "🧪 TESTE COMPLETO DO PLUGIN LOCAL_WSMANAGEACTIVITIES"
echo "=================================================="
echo "URL: $MOODLE_URL"
echo "SO: $(detect_os)"
echo ""

# Passo 1: Criar curso para testes
echo_info "Passo 1: Criando curso de teste..."
COURSE_RESULT=$(safe_curl "${MOODLE_URL}/webservice/rest/server.php" \
  -d "wstoken=${CREATION_TOKEN}" \
  -d "wsfunction=core_course_create_courses" \
  -d "courses[0][fullname]=Teste Plugin Activities $(date +%s)" \
  -d "courses[0][shortname]=PLUGIN-TEST-$(date +%s)" \
  -d "courses[0][categoryid]=2" \
  -d "moodlewsrestformat=json")

if echo "$COURSE_RESULT" | jq -e '.[0].id' > /dev/null 2>&1; then
    COURSE_ID=$(echo "$COURSE_RESULT" | jq -r '.[0].id')
    echo_success "Curso criado com ID: $COURSE_ID"
else
    echo_error "Falha ao criar curso: $COURSE_RESULT"
    exit 1
fi

# Passo 2: Configurar seções do curso
echo_info "Passo 2: Configurando seções do curso..."
SECTIONS_CONFIG=$(safe_curl "${MOODLE_URL}/webservice/rest/server.php" \
  -d "wstoken=${CREATION_TOKEN}" \
  -d "wsfunction=local_wsmanagesections_update_sections" \
  -d "courseid=${COURSE_ID}" \
  -d "sections[0][type]=num" \
  -d "sections[0][section]=0" \
  -d "sections[0][name]=Introdução" \
  -d "sections[0][summary]=<p>Seção de introdução ao curso</p>" \
  -d "sections[0][summaryformat]=1" \
  -d "sections[0][visible]=1" \
  -d "moodlewsrestformat=json")

if echo "$SECTIONS_CONFIG" | jq -e '.warnings' > /dev/null 2>&1; then
    echo_success "Seções configuradas"
else
    echo_warning "Configuração de seções pode ter falhado (plugin wsmanagesections necessário)"
fi

# Passo 3: Testar get_module_types
echo_info "Passo 3: Testando listagem de tipos de módulos..."
MODULE_TYPES=$(safe_curl "${MOODLE_URL}/webservice/rest/server.php" \
  -d "wstoken=${CREATION_TOKEN}" \
  -d "wsfunction=local_wsmanageactivities_get_module_types" \
  -d "courseid=${COURSE_ID}" \
  -d "filter=supported" \
  -d "moodlewsrestformat=json")

if echo "$MODULE_TYPES" | jq -e '.modules' > /dev/null 2>&1; then
    SUPPORTED_COUNT=$(echo "$MODULE_TYPES" | jq -r '.supported_count')
    echo_success "Tipos de módulos obtidos: $SUPPORTED_COUNT suportados"
    echo "$MODULE_TYPES" | jq -r '.modules[] | "  - \(.name): \(.displayname)"'
else
    echo_error "Falha ao obter tipos de módulos: $MODULE_TYPES"
fi

# Passo 4: Criar página
echo_info "Passo 4: Criando página via API..."
PAGE_RESULT=$(safe_curl "${MOODLE_URL}/webservice/rest/server.php" \
  -d "wstoken=${CREATION_TOKEN}" \
  -d "wsfunction=local_wsmanageactivities_create_page" \
  -d "courseid=${COURSE_ID}" \
  -d "sectionnum=0" \
  -d "name=Página de Boas-Vindas" \
  -d "content=<h1>Bem-vindos ao Curso</h1><p>Esta página foi criada automaticamente via API do plugin <strong>local_wsmanageactivities</strong>.</p><p>Funcionalidades testadas:</p><ul><li>Criação de conteúdo HTML</li><li>Integração com sistema de seções</li><li>Validação de permissões</li></ul>" \
  -d "options[intro]=Página de introdução criada via API" \
  -d "options[introformat]=1" \
  -d "options[visible]=1" \
  -d "options[completion][completionview]=1" \
  -d "moodlewsrestformat=json")

if echo "$PAGE_RESULT" | jq -e '.success' > /dev/null 2>&1 && [ "$(echo "$PAGE_RESULT" | jq -r '.success')" = "true" ]; then
    PAGE_ID=$(echo "$PAGE_RESULT" | jq -r '.id')
    PAGE_URL=$(echo "$PAGE_RESULT" | jq -r '.url')
    echo_success "Página criada com ID: $PAGE_ID"
    echo_info "URL da página: $PAGE_URL"
else
    echo_error "Falha ao criar página: $PAGE_RESULT"
fi

# Passo 5: Criar quiz básico
echo_info "Passo 5: Criando quiz básico via API..."
QUIZ_RESULT=$(safe_curl "${MOODLE_URL}/webservice/rest/server.php" \
  -d "wstoken=${CREATION_TOKEN}" \
  -d "wsfunction=local_wsmanageactivities_create_quiz" \
  -d "courseid=${COURSE_ID}" \
  -d "sectionnum=1" \
  -d "name=Quiz de Demonstração" \
  -d "config[intro]=<p>Este quiz foi criado automaticamente para demonstrar as capacidades do plugin.</p>" \
  -d "config[introformat]=1" \
  -d "config[grade]=10" \
  -d "config[attempts]=3" \
  -d "config[timelimit]=1800" \
  -d "config[questionsperpage]=1" \
  -d "config[shufflequestions]=0" \
  -d "config[shuffleanswers]=1" \
  -d "options[visible]=1" \
  -d "moodlewsrestformat=json")

if echo "$QUIZ_RESULT" | jq -e '.success' > /dev/null 2>&1 && [ "$(echo "$QUIZ_RESULT" | jq -r '.success')" = "true" ]; then
    QUIZ_ID=$(echo "$QUIZ_RESULT" | jq -r '.id')
    QUIZ_INSTANCE=$(echo "$QUIZ_RESULT" | jq -r '.instance')
    QUIZ_URL=$(echo "$QUIZ_RESULT" | jq -r '.url')
    echo_success "Quiz criado com ID: $QUIZ_ID (instance: $QUIZ_INSTANCE)"
    echo_info "URL do quiz: $QUIZ_URL"
else
    echo_error "Falha ao criar quiz: $QUIZ_RESULT"
fi

# Passo 6: Adicionar questões ao quiz
if [ ! -z "$QUIZ_ID" ]; then
    echo_info "Passo 6: Adicionando questões ao quiz..."
    
    # Questão 1: Multiple Choice
    QUESTIONS_RESULT=$(safe_curl "${MOODLE_URL}/webservice/rest/server.php" \
      -d "wstoken=${CREATION_TOKEN}" \
      -d "wsfunction=local_wsmanageactivities_add_quiz_questions" \
      -d "quizid=${QUIZ_ID}" \
      -d "idtype=cmid" \
      -d "questions[0][type]=multichoice" \
      -d "questions[0][name]=Questão Multiple Choice" \
      -d "questions[0][questiontext]=Qual é a principal vantagem da automação via API?" \
      -d "questions[0][mark]=2" \
      -d 'questions[0][questiondata]={"answers":[{"text":"Velocidade e consistência","fraction":1},{"text":"Complexidade técnica","fraction":0},{"text":"Maior trabalho manual","fraction":0},{"text":"Menos controlo","fraction":0}]}' \
      -d "questions[1][type]=truefalse" \
      -d "questions[1][name]=Questão Verdadeiro/Falso" \
      -d "questions[1][questiontext]=O plugin local_wsmanageactivities permite criar páginas e quizzes via API?" \
      -d "questions[1][mark]=1" \
      -d 'questions[1][questiondata]={"correctanswer":true}' \
      -d "questions[2][type]=shortanswer" \
      -d "questions[2][name]=Questão Resposta Curta" \
      -d "questions[2][questiontext]=Qual é o nome do sistema de gestão de aprendizagem que estamos a usar?" \
      -d "questions[2][mark]=1" \
      -d 'questions[2][questiondata]={"answers":[{"text":"Moodle","fraction":1},{"text":"moodle","fraction":1}]}' \
      -d "questions[3][type]=essay" \
      -d "questions[3][name]=Questão Ensaio" \
      -d "questions[3][questiontext]=Descreva três vantagens da automação de criação de cursos via API." \
      -d "questions[3][mark]=6" \
      -d "moodlewsrestformat=json")
    
    if echo "$QUESTIONS_RESULT" | jq -e '.success' > /dev/null 2>&1 && [ "$(echo "$QUESTIONS_RESULT" | jq -r '.success')" = "true" ]; then
        QUESTIONS_ADDED=$(echo "$QUESTIONS_RESULT" | jq -r '.questions_added')
        QUESTIONS_REQUESTED=$(echo "$QUESTIONS_RESULT" | jq -r '.questions_requested')
        echo_success "Questões adicionadas: $QUESTIONS_ADDED de $QUESTIONS_REQUESTED"
    else
        echo_error "Falha ao adicionar questões: $QUESTIONS_RESULT"
    fi
else
    echo_warning "Quiz não foi criado, saltando adição de questões"
fi

# Passo 7: Criar quiz com questões em uma chamada
echo_info "Passo 7: Criando quiz com questões numa só chamada..."
QUIZ_WITH_QUESTIONS=$(safe_curl "${MOODLE_URL}/webservice/rest/server.php" \
  -d "wstoken=${CREATION_TOKEN}" \
  -d "wsfunction=local_wsmanageactivities_create_quiz" \
  -d "courseid=${COURSE_ID}" \
  -d "sectionnum=1" \
  -d "name=Quiz Completo" \
  -d "config[intro]=Quiz criado com questões numa só operação API" \
  -d "config[grade]=20" \
  -d "config[attempts]=2" \
  -d "config[timelimit]=3600" \
  -d "questions[0][type]=multichoice" \
  -d "questions[0][name]=Questão Automática MC" \
  -d "questions[0][questiontext]=Que tipo de plugin é o local_wsmanageactivities?" \
  -d "questions[0][mark]=5" \
  -d 'questions[0][questiondata]={"answers":[{"text":"Plugin local para web services","fraction":1},{"text":"Plugin de atividade","fraction":0},{"text":"Plugin de tema","fraction":0}]}' \
  -d "questions[1][type]=truefalse" \
  -d "questions[1][name]=Questão Automática TF" \
  -d "questions[1][questiontext]=Este sistema consegue criar cursos completos automaticamente?" \
  -d "questions[1][mark]=5" \
  -d 'questions[1][questiondata]={"correctanswer":true}' \
  -d "moodlewsrestformat=json")

if echo "$QUIZ_WITH_QUESTIONS" | jq -e '.success' > /dev/null 2>&1 && [ "$(echo "$QUIZ_WITH_QUESTIONS" | jq -r '.success')" = "true" ]; then
    QUIZ2_ID=$(echo "$QUIZ_WITH_QUESTIONS" | jq -r '.id')
    QUIZ2_QUESTIONS=$(echo "$QUIZ_WITH_QUESTIONS" | jq -r '.questions_added')
    echo_success "Quiz completo criado com ID: $QUIZ2_ID ($QUIZ2_QUESTIONS questões)"
else
    echo_error "Falha ao criar quiz completo: $QUIZ_WITH_QUESTIONS"
fi

# Passo 8: Criar página avançada com opções
echo_info "Passo 8: Criando página avançada com opções de completion..."
ADVANCED_PAGE=$(safe_curl "${MOODLE_URL}/webservice/rest/server.php" \
  -d "wstoken=${CREATION_TOKEN}" \
  -d "wsfunction=local_wsmanageactivities_create_page" \
  -d "courseid=${COURSE_ID}" \
  -d "sectionnum=1" \
  -d "name=Página Avançada" \
  -d "content=<h2>Página com Funcionalidades Avançadas</h2><p>Esta página demonstra:</p><ol><li><strong>Completion tracking</strong> - requer visualização</li><li><strong>Conteúdo HTML</strong> rico</li><li><strong>Configuração via API</strong></li></ol><div class=\"alert alert-info\"><p>💡 <strong>Dica:</strong> Todas estas funcionalidades foram configuradas automaticamente via API!</p></div>" \
  -d "options[intro]=Página com tracking de completion ativo" \
  -d "options[visible]=1" \
  -d "options[completion][completionview]=1" \
  -d "options[completion][completionexpected]=$(($(date +%s) + 604800))" \
  -d "moodlewsrestformat=json")

if echo "$ADVANCED_PAGE" | jq -e '.success' > /dev/null 2>&1 && [ "$(echo "$ADVANCED_PAGE" | jq -r '.success')" = "true" ]; then
    ADVANCED_PAGE_ID=$(echo "$ADVANCED_PAGE" | jq -r '.id')
    echo_success "Página avançada criada com ID: $ADVANCED_PAGE_ID"
else
    echo_error "Falha ao criar página avançada: $ADVANCED_PAGE"
fi

# Passo 9: Resumo final
echo ""
echo "📊 RESUMO DO TESTE"
echo "=================="
echo_info "Curso criado: $COURSE_ID"
echo_info "URL do curso: ${MOODLE_URL}/course/view.php?id=${COURSE_ID}"

if [ ! -z "$PAGE_ID" ]; then
    echo_success "Página básica: Criada (ID: $PAGE_ID)"
else
    echo_error "Página básica: Falhou"
fi

if [ ! -z "$QUIZ_ID" ]; then
    echo_success "Quiz básico: Criado (ID: $QUIZ_ID)"
else
    echo_error "Quiz básico: Falhou"
fi

if [ ! -z "$QUIZ2_ID" ]; then
    echo_success "Quiz completo: Criado (ID: $QUIZ2_ID)"
else
    echo_error "Quiz completo: Falhou"
fi

if [ ! -z "$ADVANCED_PAGE_ID" ]; then
    echo_success "Página avançada: Criada (ID: $ADVANCED_PAGE_ID)"
else
    echo_error "Página avançada: Falhou"
fi

# Verificação final
echo ""
echo_info "Verificando curso final..."
FINAL_CONTENTS=$(safe_curl "${MOODLE_URL}/webservice/rest/server.php" \
  -d "wstoken=${MOBILE_TOKEN}" \
  -d "wsfunction=core_course_get_contents" \
  -d "courseid=${COURSE_ID}" \
  -d "moodlewsrestformat=json")

if echo "$FINAL_CONTENTS" | jq -e '.[0]' > /dev/null 2>&1; then
    TOTAL_MODULES=$(echo "$FINAL_CONTENTS" | jq '[.[] | .modules[]] | length')
    echo_success "Curso final contém $TOTAL_MODULES atividades"
    
    echo ""
    echo "📋 ATIVIDADES CRIADAS:"
    echo "$FINAL_CONTENTS" | jq -r '.[] | .modules[]? | "  - \(.name) (\(.modname))"'
else
    echo_warning "Não foi possível verificar conteúdo final do curso"
fi

echo ""
echo_success "✅ TESTE COMPLETO DO PLUGIN CONCLUÍDO!"
echo_info "🔗 Aceda ao curso para ver todas as atividades criadas:"
echo_info "   ${MOODLE_URL}/course/view.php?id=${COURSE_ID}"
echo ""