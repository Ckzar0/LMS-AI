# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste ficheiro.

O formato baseia-se em [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-06-25

### Adicionado
- **Web Service: `local_wsmanageactivities_create_page`**
  - Criação de atividades Page via API
  - Suporte para conteúdo HTML completo
  - Configurações de visibility, groupmode, availability
  - Completion tracking (completionview)
  - Validação e sanitização de conteúdo

- **Web Service: `local_wsmanageactivities_create_quiz`**
  - Criação de atividades Quiz via API
  - Configuração completa (tempo, tentativas, nota)
  - Suporte para criação de questões em batch
  - 4 tipos de questões suportados (multichoice, shortanswer, essay, truefalse)
  - Review options configuráveis

- **Web Service: `local_wsmanageactivities_add_quiz_questions`**
  - Adição de questões a quizzes existentes
  - Suporte para quiz ID ou course module ID
  - Validação de tipos de questões
  - Integração com question bank do Moodle

- **Web Service: `local_wsmanageactivities_get_module_types`**
  - Listagem de módulos disponíveis
  - Filtro por módulos suportados pelo plugin
  - Verificação de permissões por módulo
  - Informações sobre capacidades do plugin

- **Sistema de Permissões:**
  - Capabilities específicas para cada operação
  - Integração com sistema de roles do Moodle
  - Validação de contexto e permissões

- **Validação e Segurança:**
  - Sanitização de conteúdo HTML
  - Validação de parâmetros obrigatórios
  - Error handling robusto
  - Logging para debugging

- **Compatibilidade:**
  - Suporte completo Moodle 5.0+
  - Integração com plugin `local_wsmanagesections`
  - Compatibilidade com tokens existentes
  - Legacy support via externallib.php

- **Documentação:**
  - README.md completo com exemplos
  - Testes PHPUnit abrangentes
  - Scripts de demonstração
  - Documentação de API

### Funcionalidades Técnicas
- **Classes Helper:** `page_helper`, `quiz_helper`, `validation`
- **Privacy Provider:** Compliance GDPR completo
- **Instalação Automática:** Scripts de setup e configuração
- **Multilingue:** Strings em inglês e português
- **Logging:** Sistema de debug detalhado

### Tipos de Questões
- **Multiple Choice:** Com opções configuráveis e feedback
- **Short Answer:** Com case sensitivity configurável
- **Essay:** Com configurações de resposta avançadas
- **True/False:** Com respostas automáticas

### Integração Validada
- ✅ Funciona com sistema de automação existente
- ✅ Compatible com scripts bash macOS/Linux
- ✅ Integração perfeita com `local_wsmanagesections`
- ✅ Tokens de criação e mobile suportados
- ✅ Zero conflitos com funcionalidades existentes

## [Unreleased]

### Planeado para v1.1.0
- Suporte para mais tipos de questões (calculated, matching)
- Web service para criação de outras atividades (forum, assignment)
- Bulk operations para múltiplas atividades
- Templates pré-configurados
- API para gestão de grupos e groupings

### Planeado para v1.2.0
- Interface web para configuração
- Integração com sistema de analytics
- Export/import de configurações
- API para backup/restore de atividades

## Notas de Compatibilidade

### Moodle 5.0+
- Todas as funcionalidades totalmente suportadas
- Context IDs obrigatórios implementados
- APIs modernas utilizadas

### Integrações
- **Plugin `local_wsmanagesections`:** Recomendado para automação completa
- **Tokens existentes:** Funciona com configuração atual
- **Scripts de automação:** Compatibilidade total mantida

## Contribuições

### Desenvolvido por
- Sistema de automação Moodle 5.0
- Base de conhecimento consolidada Junho 2025
- Validado em ambiente localhost XAMPP/WAMP/MAMP

### Testado em
- Moodle 5.0.1 (Build: 20250609)
- macOS (bash 3.x) e Linux (bash 4.x+)
- PHP 8.1+
- MySQL 8.0+

## Licença

GNU GPL v3 ou posterior - compatível com licenciamento Moodle.

---

**Para mais informações, consulte README.md e documentação de API.**