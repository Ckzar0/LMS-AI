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
Navegue até à pasta `moodle-stable/`. Para garantir que os contentores arrancam e comunicam corretamente, utilize a sequência abaixo.

**Comando de Arranque Seguro (Recomendado):**
```bash
cd moodle-stable
# 1. Limpar estados anteriores (importante para a rede Docker)
./bin/moodle-docker-compose down

# 2. Definir variáveis e arrancar em background
export MOODLE_DOCKER_WWWROOT="$(pwd)/moodle" && \
export MOODLE_DOCKER_DB="mariadb" && \
export MOODLE_DOCKER_WEB_PORT="8080" && \
./bin/moodle-docker-compose up -d

# 3. AGUARDAR o Banco de Dados estar pronto (CRÍTICO)
./bin/moodle-docker-wait-for-db
```

### 3. Restaurar a Base de Dados
Este passo cria as tabelas e configura a API. **Certifique-se de que está na raiz do projeto.**
```bash
# 1. Restaurar o Dump SQL
docker exec -i moodle-stable-db-1 mariadb -u moodle -pm@0dl3ing moodle < moodle_base_setup.sql

# 2. ATIVAÇÃO E ATUALIZAÇÃO:
docker exec moodle-stable-db-1 mariadb -u moodle -pm@0dl3ing moodle -e "update m_config set value = '1' where name = 'enablewebservices';"
docker exec moodle-stable-webserver-1 php admin/cli/upgrade.php --non-interactive
docker exec moodle-stable-db-1 mariadb -u moodle -pm@0dl3ing moodle -e "insert ignore into m_external_services_functions (externalserviceid, functionname) values (3, 'local_wsmanageactivities_create_course_with_content'), (3, 'core_webservice_get_site_info'), (3, 'core_course_get_courses'), (3, 'core_course_get_contents'), (3, 'core_user_get_users');"
```

---

## 🔍 Resolução de Problemas (Troubleshooting)

### "Error: Database connection failed"
Se este erro aparecer ao rodar comandos `docker exec` ou ao aceder ao browser:
1. **Causa:** O banco de dados ainda não terminou de inicializar ou a rede Docker está em cache.
2. **Solução:**
   - Corra `./bin/moodle-docker-wait-for-db` na pasta `moodle-stable/`.
   - Verifique se no `moodle-stable/moodle/config.php` o `$CFG->dbtype` está como `'mariadb'`.
   - Se persistir, faça `./bin/moodle-docker-compose down` e repita o "Comando de Arranque Seguro".


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
