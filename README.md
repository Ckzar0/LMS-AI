# 🎓 AI LMS MOODLE - Gerador de Cursos Inteligente

Este projeto é um ecossistema completo que utiliza Inteligência Artificial para transformar manuais técnicos (PDF) em cursos estruturados dentro do Moodle, com uma interface Next.js moderna e "Headless".

---

## 🌟 Funcionalidades Principais

- **Geração Inteligente via IA:** Extração de texto e imagens de PDFs para criação de módulos, páginas e quizzes.
- **Portkey Gateway:** Integração resiliente com IA (Gemini/Claude) via Portkey.
- **Modo Fábrica (JSON):** Importação direta de cursos via ficheiros JSON estruturados.
- **Ajuste Fino de Imagens:** Ferramenta dedicada (`fix_images.php`) para substituir placeholders e renumerar figuras automaticamente.
- **Dashboard em Tempo Real:** Estatísticas reais de cursos, utilizadores e atividade da plataforma.
- **Study Mode Moderno:** Interface de estudo focado, rápida e responsiva construída em Next.js.
- **Certificação Automática:** Fluxo completo desde o estudo até à emissão do certificado em PDF.

---

## 🏗️ Arquitetura do Projeto

- **FrontEnd:** Next.js 16.1.6 (Tailwind CSS, Shadcn/UI).
- **BackEnd Moodle:** Moodle 5.1.3+ (Dockerizado) com plugin customizado `wsmanageactivities`.
- **IA/LLM:** Google Gemini via **Portkey.ai**.
- **Infraestrutura:** Docker & Docker Compose com automação via `bootstrap.sh`.

---

## 🚀 Início Rápido (Quick Start)

Se acabou de clonar o repositório, basta executar o script de automação total:

```bash
chmod +x bootstrap.sh
./bootstrap.sh
```

Este script trata de:
1. Validar e preparar o ambiente Docker (Rede, Volumes e Permissões).
2. Descarregar o Moodle Core 5.1.3.
3. Configurar a Base de Dados (MariaDB) com o baseline `moodle_base_setup.sql`.
4. Ativar WebServices, REST e configurar o Token de acesso global.

---

## 🌐 Acessos Padrão

- **Dashboard (Next.js):** [http://localhost:3000](http://localhost:3000)
- **Moodle Admin:** [http://localhost:8080](http://localhost:8080)
  - **Utilizador:** `admin`
  - **Password:** `admin`
  - **Token API:** `14c68ff68a1a57cdc4cf4d72f443b87d`

---

## 📚 Documentação Adicional

Para garantir um teste ou deployment sem falhas, consulte os novos manuais:

- 🛠️ **[Guia de Instalação (Produção)](./INSTALL_PROD.md):** Essencial para configurar em servidores remotos ou máquinas de contentores.
- 📖 **[Manual do Utilizador](./USER_MANUAL.md):** Guia detalhado sobre como gerar cursos, configurar a profundidade da IA e usar as ferramentas de reparação de imagens.

---

## 📜 Licença e Termos
Este projeto é de uso interno. Consulte os proprietários para detalhes sobre distribuição e termos de utilização.
