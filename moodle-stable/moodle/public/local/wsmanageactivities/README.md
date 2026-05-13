# 📦 Plugin wsmanageactivities (LMS-AI Core)

**Versão**: v1.1.0 (Maio 2026)  
**Moodle**: 5.1+  
**Contexto**: Módulo de integração e criação de cursos via IA.

## 🎯 Funcionalidades

- ✅ **Criação Headless**: API para criação de cursos via JSON (Next.js).
- ✅ **Ajuste de Imagens**: Ferramenta `fix_images.php` para polimento visual.
- ✅ **Bancos de Questões**: Suporte total à nova estrutura do Moodle 5.1.
- ✅ **Navegação**: Botões de navegação automática entre atividades.

## 🚀 Como Usar (Interface Web)

1. Aceder: `http://localhost:8080/local/wsmanageactivities/upload.php`
2. Carregar o ficheiro JSON estruturado.
3. O curso será criado e o ID será devolvido.

## 🖼️ Reparação de Imagens

Se o curso gerado pela IA contiver placeholders ou imagens incorretas, utilize:
`http://localhost:8080/local/wsmanageactivities/fix_images.php?courseid=ID`

## 📚 Documentação Relacionada

- **KNOWLEDGE_BASE.md**: Detalhes técnicos e estrutura de BD.
- **INSTALL_PROD.md** (Raiz): Instruções de deployment Docker.
- **USER_MANUAL.md** (Raiz): Manual completo de utilização.

## 🛠️ Desenvolvimento e Testes

Para testar as funções de WebService via terminal:
```bash
./scripts/plugin_test_script.sh
```

---
**Última atualização**: Maio 2026  
**Status**: Produção Estável
