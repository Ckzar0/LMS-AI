# 🚩 LMS AI - Guia de Configuração (Setup)

Este repositório contém o ecossistema completo para a geração de cursos via IA com integração Moodle.
- **FrontEnd:** Next.js (Porto 3000)
- **Moodle Stable:** Moodle 5.1.3+ Dockerizado (Porto 8080)

---

## 🛠️ Requisitos do Sistema

1. **Docker & Docker Compose** (v2.0+)
2. **Node.js** (v20+) e **npm/pnpm**
3. **Chave API Gemini** (Necessária para geração de conteúdo)

---

## 🚀 Como Iniciar o Projeto (Passo a Passo)

### 1. Configurar as Variáveis de Ambiente
Crie um ficheiro chamado `.env.local` na pasta `FrontEnd/` com o seguinte conteúdo:

```env
# URL base do Moodle (onde os contentores Docker estão a ouvir)
MOODLE_URL=http://localhost:8080

# Token de acesso ao Web Service (gerado no Moodle)
MOODLE_TOKEN=14c68ff68a1a57cdc4cf4d72f443b87d

# Chave API do Google Gemini (para a IA gerar os cursos)
GEMINI_API_KEY=SUA_CHAVE_AQUI
```

### 2. Iniciar o Backend (Moodle)
Navegue até à pasta `moodle-stable/` e execute:

```bash
cd moodle-stable
# Definir o caminho absoluto para a pasta public do Moodle
export MOODLE_DOCKER_WWWROOT="$(pwd)/moodle"
export MOODLE_DOCKER_DB="mariadb"
export MOODLE_DOCKER_WEB_PORT="8080"

# Arrancar os contentores
./bin/moodle-docker-compose up -d
```

### 3. Restaurar a Base de Dados (Obrigatório no Setup inicial)
Este passo irá criar todas as tabelas e configurações de API necessárias:
```bash
# Executar este comando na raiz do projeto (onde está o ficheiro .sql)
docker exec -i moodle-stable-db-1 mariadb -u moodle -pm@0dl3ing moodle < moodle_base_setup.sql

# ATIVAÇÃO CRÍTICA (Caso a API dê erro 403 ou 404):
# 1. Ativar Web Services
docker exec moodle-stable-db-1 mariadb -u moodle -pm@0dl3ing moodle -e "update m_config set value = '1' where name = 'enablewebservices';"
# 2. Registar novas funções do plugin local_wsmanageactivities
docker exec moodle-stable-webserver-1 php admin/cli/upgrade.php --non-interactive
# 3. Garantir permissões das funções no Serviço ID 3
docker exec moodle-stable-db-1 mariadb -u moodle -pm@0dl3ing moodle -e "insert ignore into m_external_services_functions (externalserviceid, functionname) values (3, 'local_wsmanageactivities_create_course_with_content'), (3, 'core_webservice_get_site_info'), (3, 'core_course_get_courses'), (3, 'core_course_get_contents'), (3, 'core_user_get_users');"
```

### 4. Iniciar o Frontend (Next.js)
```bash
cd FrontEnd
npm install
npm run dev
```
O dashboard estará disponível em: `http://localhost:3000`

---

## 🔌 Configurações de Conectividade (API)

O sistema utiliza o plugin customizado `local_wsmanageactivities`. Se a ligação falhar:
1. Verifique se o Moodle está ativo no porto 8080.
2. Certifique-se de que o Token no `.env.local` coincide com o do Moodle.

---

## 📂 Estrutura do Repositório
- `/FrontEnd`: Aplicação Next.js.
- `/moodle-stable`: Ambiente Moodle Docker.
- `/moodle_base_setup.sql`: Base de dados inicial pronta a usar.
- `SETUP.md`: Este manual.
