# 🚩 LMS AI - Guia de Configuração Detalhado (Setup)

Este guia descreve como o ambiente está estruturado e como mantê-lo.

---

## 🛠️ Requisitos do Sistema

1. **Docker & Docker Compose** (Obrigatório)
2. **Node.js v20+** (Apenas se quiser correr o FrontEnd fora do Docker)
3. **Chave API Gemini** (Necessária para a geração de cursos)

---

## 🚀 Método Recomendado: Bootstrap Automático

O script `bootstrap.sh` é a forma mais rápida de colocar tudo a funcionar. Ele lida com a criação de volumes, permissões, restauração de BD e configuração de tokens.

```bash
./bootstrap.sh
```

**Credenciais Iniciais:**
- **Moodle:** `admin` / `admin`
- **WS Token:** `14c68ff68a1a57cdc4cf4d72f443b87d` (Já configurado no `.env.local`)

---

## ⚙️ Configuração Manual (Se necessário)

### 1. Variáveis de Ambiente (FrontEnd)
O FrontEnd necessita de um ficheiro `FrontEnd/.env.local`. O bootstrap cria um básico, mas deve editá-lo para adicionar a sua chave da Gemini:

```env
NEXT_PUBLIC_MOODLE_URL=http://localhost:8080
MOODLE_URL=http://webserver  # Se correr dentro do Docker
# MOODLE_URL=http://localhost:8080 # Se correr fora do Docker
MOODLE_TOKEN=14c68ff68a1a57cdc4cf4d72f443b87d
GEMINI_API_KEY=SUA_CHAVE_AQUI
```

### 2. Comandos Úteis do Docker
- **Ver logs:** `docker compose logs -f`
- **Parar tudo:** `docker compose down`
- **Limpar caches do Moodle:** `docker exec lms-ai-webserver-1 php admin/cli/purge_caches.php`

---

## 🔍 Resolução de Problemas (Troubleshooting)

### "Database connection failed"
- Verifique se o contentor da DB (`lms-ai-db-1`) está ativo: `docker ps`.
- O Moodle demora cerca de 30-60 segundos a aceitar ligações após o primeiro arranque.

### Imagens não aparecem no FrontEnd
- As imagens extraídas são mapeadas via volume:
  `moodle-stable/moodle/public/local/wsmanageactivities/extracted_images` -> `FrontEnd/public/extracted_images`
- Certifique-se de que a pasta tem permissões `777`.

### Alterar a Password do Admin
Se precisar de resetar a password via terminal:
```bash
docker exec lms-ai-webserver-1 php admin/cli/reset_password.php --username=admin --password=SUA_NOVA_PASS --ignore-password-policy
```

---

## 📂 Estrutura de Pastas Críticas
- `bootstrap.sh`: Orquestrador de setup inicial.
- `moodle_base_setup.sql`: Baseline da BD (limpa e configurada).
- `FrontEnd/`: Código fonte Next.js.
- `moodle-stable/moodle/public/local/wsmanageactivities/`: Plugin customizado da API.
