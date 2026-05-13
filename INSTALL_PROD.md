# 🚀 Guia de Instalação em Produção (Máquina de Contentores) - AI LMS MOODLE

Este guia detalha os passos necessários para instalar e colocar em funcionamento o ecossistema **AI LMS MOODLE** num servidor ou máquina de contentores remota.

---

## 📋 Pré-requisitos

Antes de iniciar, garante que a máquina tem:
1.  **Docker & Docker Compose V2** instalados.
2.  **Git** para clonagem do repositório (ou transferência dos ficheiros).
3.  **Portas abertas:**
    *   `3000`: FrontEnd (Next.js)
    *   `8080`: BackEnd (Moodle)
4.  **Acesso à Internet:** Necessário para descarregar imagens do Docker e comunicar com a API da Portkey.

---

## 🛠️ Passo 1: Configuração de Ambiente (`.env`)

Na raiz do projeto, edita o ficheiro `.env`. Este ficheiro é **crítico** para a comunicação entre o browser do utilizador e os serviços.

```bash
nano .env
```

**Configurações obrigatórias:**
*   `NEXT_PUBLIC_MOODLE_URL`: Substitui `http://localhost:8080` pelo IP público ou DNS do servidor (ex: `http://10.0.0.15:8080`).
*   `PORTKEY_API_KEY`: Garante que a tua chave da Portkey está presente.
*   `MOODLE_URL=http://webserver`: **NÃO ALTERAR**. Esta é a comunicação interna entre contentores.

---

## 🚀 Passo 2: Execução do Bootstrap Automático

O sistema possui um script que automatiza a configuração de volumes, permissões e base de dados.

```bash
# Dar permissão de execução
chmod +x bootstrap.sh

# Executar o setup
sudo ./bootstrap.sh
```

### O que o script faz:
1.  **Estrutura de Pastas:** Cria e dá permissões `777` às pastas de imagens e PDFs (essencial para Linux).
2.  **Moodle Core:** Faz o download do Moodle 5.1.3 se não estiver presente.
3.  **Docker Up:** Sobe os contentores em modo `detached`.
4.  **Base de Dados:** Restaura a base de dados pré-configurada (`moodle_base_setup.sql`).
5.  **Configuração de Sistema:** Ativa WebServices, REST e configura o Token de acesso (`14c68ff...`).

---

## 🌐 Passo 3: Verificação de Acessos

Após o script terminar (pode demorar 2-5 minutos dependendo da rede), verifica:

1.  **Moodle Admin:** `http://[IP_DO_SERVIDOR]:8080`
    *   Utilizador: `admin`
    *   Password: `admin`
2.  **FrontEnd:** `http://[IP_DO_SERVIDOR]:3000`

---

## 🔍 Resolução de Problemas Comuns

### 1. "O FrontEnd não consegue comunicar com o Moodle"
*   Verifica se o `NEXT_PUBLIC_MOODLE_URL` no `.env` aponta para o IP que vês no teu browser.
*   Corre `docker compose restart frontend` após alterar o `.env`.

### 2. "Imagens não aparecem no FrontEnd"
*   Verifica se o volume está montado corretamente:
    `docker exec frontend ls /app/public/extracted_images`
*   Garante que no Host as permissões estão abertas: `chmod -R 777 moodle-stable/moodle/public/local/wsmanageactivities/extracted_images`.

### 3. Ver Logs em Tempo Real
Se algo falhar, usa estes comandos:
```bash
# Ver logs de todos os serviços
docker compose logs -f

# Ver logs apenas do gerador de cursos (BackEnd)
docker compose logs -f webserver

# Ver logs da IA e UI (FrontEnd)
docker compose logs -f frontend
```

### 4. Limpeza Total (Reset)
Se precisares de começar do zero:
```bash
docker compose down -v
# CUIDADO: Isto apaga a Base de Dados e Volumes.
```

---

## 📌 Notas de Segurança
*   Este ambiente usa credenciais padrão (`admin/admin`). **Não expor diretamente na internet sem proteção de firewall ou alteração de passwords.**
*   O token do WebService é estático para facilitar o teste, mas deve ser revogado em produção real.
