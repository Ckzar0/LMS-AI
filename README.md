# 🎓 AI LMS MOODLE - Gerador de Cursos Inteligente

Este projeto é um ecossistema completo que utiliza Inteligência Artificial para gerar cursos estruturados (conteúdo, imagens e quizzes) e integrá-los automaticamente numa plataforma Moodle via uma interface Next.js moderna.

## 🚀 Início Rápido (Quick Start)

Se acabou de clonar o repositório, basta executar o script de automação total:

```bash
chmod +x bootstrap.sh
./bootstrap.sh
```

Este script irá:
1. Validar e preparar o ambiente Docker.
2. Descarregar o Moodle Core (se necessário).
3. Iniciar a Base de Dados e o Webserver.
4. Restaurar a base de dados limpa e pré-configurada.
5. Configurar automaticamente as chaves de API e Tokens de acesso.

---

## 🌐 Acessos Padrão

Após o bootstrap, o sistema estará disponível em:

- **Dashboard (Next.js):** [http://localhost:3000](http://localhost:3000)
- **Moodle Admin:** [http://localhost:8080](http://localhost:8080)
  - **Utilizador:** `admin`
  - **Password:** `admin`

---

## 🏗️ Arquitetura do Projeto

- **FrontEnd:** Next.js 14+, Tailwind CSS, Shadcn/UI.
- **BackEnd Moodle:** Moodle 4.5+ em Docker com plugin customizado `local_wsmanageactivities`.
- **IA:** Integração com Google Gemini (via Portkey para resiliência).
- **Base de Dados:** MariaDB 10.11.

---

## 🛠️ Desenvolvimento

Para instruções detalhadas de configuração manual, resolução de problemas e requisitos, consulte o ficheiro:
👉 **[SETUP.md](./SETUP.md)**

## 📜 Licença e Termos
Este projeto é de uso interno. Consulte os proprietários para detalhes sobre distribuição.
