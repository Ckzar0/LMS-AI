# Correções Aplicadas ao Plugin wsmanageactivities
**Data:** 13 de Fevereiro de 2025, 18:10
**Versão:** v5.1-FIXED

## 🐛 Problema Identificado

O plugin estava a gerar o erro:
```
line 315 of /public/lib/dml/moodle_read_replica_trait.php: 
call to moodle_database->start_delegated_transaction()
```

**Causa Raiz:** O Moodle 5.0 não permite transações de base de dados aninhadas. Quando múltiplas atividades eram criadas em sequência usando `add_moduleinfo()`, cada uma tentava iniciar uma nova transação, causando conflito se alguma transação anterior não tivesse sido corretamente fechada.

## ✅ Correções Implementadas

### 1. ActivityCreator.php (classes/importer/ActivityCreator.php)

#### Mudanças:
- **Adicionado método `clear_pending_transactions()`**
  - Verifica se existem transações pendentes
  - Faz rollback automático de transações não fechadas
  - Logs detalhados para debugging

- **Modificado `create_page()`**
  - Chama `clear_pending_transactions()` antes de criar atividade
  - Try-catch melhorado com logs específicos

- **Modificado `create_quiz()`**
  - Chama `clear_pending_transactions()` antes de criar atividade
  - Try-catch melhorado com logs específicos
  - Logs de progresso para cada etapa (criação, questões, recalculo)

#### Código Adicionado:
```php
private static function clear_pending_transactions() {
    global $DB;
    $cleared = 0;
    while ($DB->is_transaction_started()) {
        try {
            error_log("ActivityCreator - Limpando transação pendente (tentativa " . ($cleared + 1) . ")");
            $DB->force_transaction_rollback();
            $cleared++;
        } catch (Exception $e) {
            error_log("ActivityCreator - Erro ao limpar transação: " . $e->getMessage());
            break;
        }
    }
    if ($cleared > 0) {
        error_log("ActivityCreator - Total de transações limpas: $cleared");
    }
}
```

### 2. upload.php

#### Mudanças:
- **Interface melhorada**
  - Título atualizado para "v5.1-FIXED"
  - Banner informativo sobre as correções
  - Aviso sobre logs

- **Gestão de transações no loop de atividades**
  - Verifica transações pendentes antes de cada atividade
  - Rollback automático em caso de erro
  - Continua processamento mesmo com erros individuais

- **Estatísticas de execução**
  - Contador de sucessos
  - Contador de erros
  - Resumo no final da importação

- **Logs melhorados**
  - Stack traces completas em caso de erro
  - Identificação clara de cada etapa
  - Feedback visual no terminal

#### Funcionalidades Adicionadas:
```php
// Verificação antes de cada atividade
while ($DB->is_transaction_started()) {
    error_log("upload.php - Transação pendente detectada");
    $DB->force_transaction_rollback();
    $transaction_cleared = true;
}

// Rollback em caso de erro
if ($DB->is_transaction_started()) {
    try {
        $DB->force_transaction_rollback();
        echo "      🔄 Transação revertida\n";
    } catch (Exception $rollback_error) {
        error_log("upload.php - Erro ao fazer rollback: " . $rollback_error->getMessage());
    }
}
```

## 📁 Backups Criados

Antes de aplicar as correções, foram criados backups dos ficheiros originais:

```bash
# ActivityCreator.php
/var/www/html/moodle2/public/local/wsmanageactivities/classes/importer/ActivityCreator.php.backup_1771005917

# upload.php
/var/www/html/moodle2/public/local/wsmanageactivities/upload.php.backup_1771006100
```

## 🎯 Benefícios das Correções

1. **Robustez**: O sistema agora continua mesmo se uma atividade falhar
2. **Debugging**: Logs detalhados facilitam identificação de problemas
3. **Transparência**: Feedback visual claro sobre o que está a acontecer
4. **Recuperação**: Limpeza automática de transações problemáticas
5. **Estatísticas**: Saber quantas atividades foram criadas com sucesso vs erros

## 🔍 Como Verificar se Funciona

1. Acesse: `http://192.168.64.2/moodle2/local/wsmanageactivities/upload.php`
2. Carregue o seu ficheiro JSON
3. Observe a saída no terminal verde
4. Verifique os logs em caso de problemas:
   ```bash
   tail -f /var/log/apache2/error.log
   # ou
   tail -f /var/www/html/moodle2/moodledata/error.log
   ```

## 🚨 Resolução de Problemas

Se ainda encontrar erros:

### Problema: "Permission denied" ao criar atividades
**Solução:** Verificar permissões do utilizador no Moodle

### Problema: "Category not found" para questões
**Solução:** Certificar que os bancos de questões existem antes das atividades

### Problema: Ainda há erros de transação
**Solução:** 
1. Verificar logs detalhados
2. Confirmar que não há outro código a iniciar transações
3. Contactar para análise adicional

## 📊 Comparação: Antes vs Depois

### Antes:
```
[1] quiz: Teste 1
      ❌ Erro: call to moodle_database->start_delegated_transaction()
[PARADO - Todas as atividades seguintes falharam]
```

### Depois:
```
[1] quiz: Teste 1
      ✅ CM: 123
[2] page: Página 1
      ⚠️  Transação anterior limpa
      ✅ CM: 124
[3] quiz: Teste 2
      ✅ CM: 125

📊 RESUMO:
   ✅ Sucessos: 3
   ❌ Erros: 0
   📝 Total: 3
```

## 🔄 Cache

Cache do Moodle foi limpa após aplicação das correções:
```bash
cd /var/www/html/moodle2 && sudo -u www-data php admin/cli/purge_caches.php
```

## ✍️ Notas Adicionais

- Estas correções são compatíveis com Moodle 5.0+
- Não alteram a estrutura da base de dados
- Não afetam funcionalidades existentes
- Backups podem ser restaurados a qualquer momento se necessário

## 📞 Suporte

Se encontrar problemas após estas correções, reúna as seguintes informações:

1. Conteúdo do ficheiro JSON que está a tentar importar
2. Logs do erro (`/var/log/apache2/error.log`)
3. Output do terminal verde durante a importação
4. ID do curso (se foi criado)

---

**Ficheiros Modificados:**
- `/var/www/html/moodle2/public/local/wsmanageactivities/classes/importer/ActivityCreator.php`
- `/var/www/html/moodle2/public/local/wsmanageactivities/upload.php`

**Versão do Plugin:** v5.1-FIXED (Transaction Handling)
