# 📦 Plugin wsmanageactivities (LMS-AI Core)

**Versão**: v1.2.0 (Maio 2026)  
**Moodle**: 5.1.3+  
**Status**: Produção Estável (Docker)

Este plugin é o motor de integração do ecossistema **LMS-AI**, permitindo a criação automática de cursos, atividades e bancos de questões via Inteligência Artificial.

---

## 🚀 Funcionalidades Principais

- 🤖 **Integração IA Unificada (v10.5)**: Serviço REST para criação de cursos completos em segundos com suporte a SSE.
- 🖼️ **Ajuste de Media**: Ferramenta `fix_images.php` para polimento visual e legendas automáticas.
- 🔄 **Auto-Refresh**: Atualização automática do Banco de Questões e limpeza de cache após geração.
- 🧩 **Moodle 5.1 Ready**: Suporte nativo ao novo sistema de versionamento de questões.
- 📑 **Navegação Dinâmica**: Injeção automática de botões "Anterior/Próximo" em todas as atividades.

---

## 🛠️ Instalação e Configuração

O plugin é instalado automaticamente através do script de **Bootstrap** na raiz do projeto:

```bash
./bootstrap.sh
```

### URLs de Utilidade Técnica
*   **API Root:** `http://localhost:8080/local/wsmanageactivities/`
*   **Reparador de Imagens:** `http://localhost:8080/local/wsmanageactivities/fix_images.php`
*   **Upload Manual (Legacy):** `http://localhost:8080/local/wsmanageactivities/upload.php`

---

## 📚 Documentação Técnica

Para detalhes sobre a arquitetura e base de dados, consulte:
- 📖 **[KNOWLEDGE_BASE.md](./KNOWLEDGE_BASE.md)**: Manual técnico de referência.
- 📓 **[INTERNAL_DEV_NOTES.md](./INTERNAL_DEV_NOTES.md)**: Histórico, métricas e ROI para relatórios.

---
© 2026 LMS-AI Project
