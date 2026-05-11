> [!IMPORTANT]
> **REGRAS DE CONDUTA:** 
> 1. Nunca avances com qualquer implementação, alteração de código ou execução de tarefas complexas sem a minha autorização explícita.
> 2. **NUNCA** realizes commits de Git sem que eu peça ou autorize especificamente para cada caso.
> 3. Deves sempre apresentar a estratégia primeiro e aguardar validação.

# 🧠 Memória de Sessão - AI LMS Moodle

## 🚀 Estado Atual (Branch: feat/containerization-setup):
- **Infraestrutura & Automação:** 
    - Migração completa para ambiente 100% Docker com orquestração via `docker-compose.yml`.
    - **Custom Dockerfile:** Moodle agora usa `Dockerfile.moodle` para garantir dependências de extração de imagens (`pdfimages`, `python3`, `Pillow`).
    - **Cursos Portáteis:** Pasta `/Cursos` na raiz mapeada para o Docker para processar PDFs grandes sem upload via browser.
- **Segurança:** 
    - Hardening de segredos: API Keys removidas do código e geridas via ficheiro `.env` local.
- **Conectividade & LLM:**
    - **Portkey Ativo:** Integração com Portkey AI Gateway corrigida e funcional (suporta Config IDs/Slugs `@`).
    - **Toggle Inteligente:** `USE_PORTKEY` no FrontEnd permite alternar entre Portkey e Gemini Direto.
- **Certificação (PAI-1215):** 
    - **100% Funcional:** Implementado sistema de **Clonagem Automática de Templates** para garantir isolamento por curso.
    - **Download Nativo:** Novo ecrã de conclusão no FrontEnd com download direto de PDF via API (Base64), sem necessidade de o aluno visitar o Moodle.
    - **UX:** Transição fluida entre Avaliação e Certificado com design Premium e animações.
- **Pipeline de Media:**
    - **Images Fix:** Suporte para `.png` e `.jpg`. URLs agora são relativos (`/course_assets/`), resolvendo o problema de bloqueio de rede interna do Docker.
    - **Auto-Cleanup:** Remoção automática do fórum de "Announcements" para garantir progresso de 100%.
- **Quiz & Avaliação:** 
    - Sistema de feedback (1-5 estrelas) e submissão de notas funcionais.

## 📂 Estrutura de Pastas:
- **Orquestração:** `docker-compose.yml`, `Dockerfile.moodle`, `bootstrap.sh`.
- **Manuais PDFs:** `/Cursos/` (Raiz do projeto).
- **Imagens Extraídas:** `moodle-stable/moodle/public/local/wsmanageactivities/extracted_images/`
- **Assets Públicos:** `moodle-stable/moodle/course_assets/` (URLs: `/course_assets/ID/img.jpg`)

## 📌 Próximos Passos (A Iniciar):
1. **Quick Wins - UX (PAI-QW):**
    - Substituir vídeo por imagem de capa nos Cards da aba "Cursos".
    - Exibir média de estrelas (Rating) nos Cards de listagem de cursos.
2. **Portkey Agnostic:** Migrar todas as chamadas para usar `PORTKEY_CONFIG_ID` (Hot-Swap).
3. **Certificação (PAI-1215):** Botão para gerar PDF de certificado via Moodle (CustomCert).
4. **Áudio (TTS):** Botão "Play" para leitura do conteúdo via IA.
5. **Extração de Tabelas:** Evoluir os placeholders `[[TABLE_PXX]]` para imagens reais das tabelas.

## ⚠️ Notas Técnicas:
- **Upload Grande:** PDFs >15MB devem ser colocados na pasta `/Cursos` para evitar erros de memória no PHP.
- **URLs de Media:** Nunca usar URLs absolutos com `http://webserver` na base de dados; preferir caminhos relativos ou tradução via API Next.js.
- **Token de Web Service:** `14c68ff68a1a57cdc4cf4d72f443b87d` (Fixo via Bootstrap).
---