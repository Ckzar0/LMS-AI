# 📦 Plugin wsmanageactivities

**Versão**: v8.1  
**Moodle**: 5.0+  
**Autor**: Sistema de importação automática de cursos

## 🎯 Funcionalidades

- ✅ Criação automática de cursos via JSON
- ✅ Bancos de questões
- ✅ Páginas de conteúdo
- ✅ Quizzes com questões aleatórias
- ✅ **Navegação automática entre conteúdos** (v8.1)

## 🚀 Como Usar

1. Aceder: `http://192.168.64.2/moodle2/local/wsmanageactivities/upload.php`
2. Fazer upload de ficheiro JSON
3. Aguardar processamento
4. Aceder ao curso criado

## 📝 Exemplo de JSON

```json
{
  "course_name": "Meu Curso",
  "course_shortname": "CURSO_001",
  "question_banks": [...],
  "activities": [
    {"type": "page", "name": "Intro", "content": "..."},
    {"type": "quiz", "name": "Teste", "questions_from_bank": {...}}
  ]
}
```

## 🧭 Navegação Automática

Todas as **páginas** têm botões:
- **← Anterior**: Vai para atividade anterior (página ou quiz)
- **Próximo →**: Vai para próxima atividade (página ou quiz)

## 📚 Documentação

- **KNOWLEDGE_BASE.md**: Informação completa do sistema
- **CHANGELOG.md**: Histórico de versões
- **QUIZ_QUESTIONS_EXAMPLES.md**: Exemplos de questões

## 🔧 Acesso SSH

```bash
ssh ubuntu@192.168.64.2
cd /var/www/html/moodle2/public/local/wsmanageactivities
```

## 📊 Estrutura

```
wsmanageactivities/
├── upload.php              # Interface web
├── classes/
│   ├── CourseManager.php
│   ├── QuestionBankManager.php
│   └── importer/
│       ├── ActivityCreator.php
│       └── QuestionCreator.php
├── KNOWLEDGE_BASE.md       # 📖 Documentação completa
├── CHANGELOG.md            # 📝 Histórico
└── README.md               # 👈 Este ficheiro
```

## 🐛 Troubleshooting

Ver logs:
```bash
sudo tail -f /var/log/php/error.log
```

Ver documentação completa:
```bash
cat KNOWLEDGE_BASE.md
```

---

**Última atualização**: 14-Feb-2026  
**Versão atual**: v8.1
