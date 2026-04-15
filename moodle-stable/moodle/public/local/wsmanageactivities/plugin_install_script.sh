#!/bin/bash
# Script de instalação automática do plugin local_wsmanageactivities
# Compatível com macOS e Linux

# Configurações padrão
DEFAULT_MOODLE_PATH="/Applications/XAMPP/xamppfiles/htdocs/moodle500"
DEFAULT_MOODLE_URL="http://localhost:8888/moodle500"

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo_success() { echo -e "${GREEN}✅ $1${NC}"; }
echo_error() { echo -e "${RED}❌ $1${NC}"; }
echo_warning() { echo -e "${YELLOW}⚠️ $1${NC}"; }
echo_info() { echo -e "${BLUE}ℹ️ $1${NC}"; }

# Detecção de SO
detect_os() {
    if [[ "$OSTYPE" == "darwin"* ]]; then
        echo "macos"
    else
        echo "linux"
    fi
}

# Verificar se está a correr como root (Linux)
check_permissions() {
    if [[ "$(detect_os)" == "linux" && $EUID -eq 0 ]]; then
        echo_warning "A correr como root. Considere usar um utilizador normal."
    fi
}

# Função para encontrar diretório do Moodle
find_moodle_directory() {
    local common_paths=(
        "$DEFAULT_MOODLE_PATH"
        "/var/www/html/moodle"
        "/var/www/moodle"
        "/opt/lampp/htdocs/moodle"
        "/Applications/MAMP/htdocs/moodle"
        "$HOME/moodle"
        "$(pwd)/moodle"
    )
    
    for path in "${common_paths[@]}"; do
        if [[ -d "$path" && -f "$path/config.php" ]]; then
            echo "$path"
            return 0
        fi
    done
    
    return 1
}

# Verificar estrutura do Moodle
verify_moodle_structure() {
    local moodle_path="$1"
    
    if [[ ! -f "$moodle_path/config.php" ]]; then
        echo_error "config.php não encontrado em $moodle_path"
        return 1
    fi
    
    if [[ ! -d "$moodle_path/local" ]]; then
        echo_error "Diretório local/ não encontrado em $moodle_path"
        return 1
    fi
    
    # Verificar se web services estão configurados
    if grep -q "wstoken" "$moodle_path/config.php" 2>/dev/null; then
        echo_info "Configuração de web services detectada"
    else
        echo_warning "Web services podem não estar configurados"
    fi
    
    return 0
}

# Criar estrutura de diretórios do plugin
create_plugin_structure() {
    local plugin_path="$1"
    
    echo_info "Criando estrutura de diretórios..."
    
    local directories=(
        "$plugin_path"
        "$plugin_path/classes"
        "$plugin_path/classes/external"
        "$plugin_path/classes/local"
        "$plugin_path/classes/privacy"
        "$plugin_path/db"
        "$plugin_path/lang"
        "$plugin_path/lang/en"
        "$plugin_path/lang/pt"
        "$plugin_path/tests"
        "$plugin_path/documentation"
    )
    
    for dir in "${directories[@]}"; do
        if mkdir -p "$dir"; then
            echo_success "Diretório criado: $dir"
        else
            echo_error "Falha ao criar diretório: $dir"
            return 1
        fi
    done
    
    return 0
}

# Verificar dependências
check_dependencies() {
    echo_info "Verificando dependências..."
    
    # Verificar PHP
    if command -v php &> /dev/null; then
        local php_version=$(php -v | head -n1 | cut -d " " -f2 | cut -d "." -f1,2)
        echo_success "PHP encontrado: versão $php_version"
        
        if [[ $(echo "$php_version >= 8.1" | bc 2>/dev/null) -eq 1 ]] 2>/dev/null; then
            echo_success "Versão PHP adequada para Moodle 5.0"
        else
            echo_warning "PHP $php_version pode não ser ideal para Moodle 5.0 (recomendado 8.1+)"
        fi
    else
        echo_error "PHP não encontrado no PATH"
        return 1
    fi
    
    # Verificar curl
    if command -v curl &> /dev/null; then
        echo_success "curl encontrado"
    else
        echo_error "curl não encontrado (necessário para testes)"
        return 1
    fi
    
    # Verificar jq (opcional mas recomendado)
    if command -v jq &> /dev/null; then
        echo_success "jq encontrado (para testes JSON)"
    else
        echo_warning "jq não encontrado - instalar para melhor experiência de testes"
        echo_info "Instalação: brew install jq (macOS) ou apt-get install jq (Linux)"
    fi
    
    return 0
}

# Função principal de instalação
install_plugin() {
    local moodle_path="$1"
    local plugin_path="$moodle_path/local/wsmanageactivities"
    
    echo "🚀 INSTALAÇÃO DO PLUGIN LOCAL_WSMANAGEACTIVITIES"
    echo "================================================"
    echo "Diretório Moodle: $moodle_path"
    echo "Diretório Plugin: $plugin_path"
    echo "SO: $(detect_os)"
    echo ""
    
    # Verificar se plugin já existe
    if [[ -d "$plugin_path" ]]; then
        echo_warning "Plugin já existe em $plugin_path"
        read -p "Deseja sobrescrever? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            echo_info "Instalação cancelada"
            exit 0
        fi
        rm -rf "$plugin_path"
    fi
    
    # Criar estrutura
    if ! create_plugin_structure "$plugin_path"; then
        echo_error "Falha ao criar estrutura do plugin"
        exit 1
    fi
    
    echo_info "Estrutura do plugin criada com sucesso!"
    echo_warning "IMPORTANTE: Agora precisa de:"
    echo ""
    echo "1. 📁 Copiar ficheiros do plugin para: $plugin_path"
    echo "   - version.php"
    echo "   - externallib.php"
    echo "   - README.md"
    echo "   - classes/ (todos os ficheiros)"
    echo "   - db/ (todos os ficheiros)"
    echo "   - lang/ (todos os ficheiros)"
    echo "   - tests/ (todos os ficheiros)"
    echo ""
    echo "2. 🌐 Aceder à interface de administração Moodle:"
    echo "   $DEFAULT_MOODLE_URL/admin"
    echo ""
    echo "3. 🔧 Seguir processo de instalação automática"
    echo ""
    echo "4. ⚙️ Configurar web services:"
    echo "   - Adicionar funções ao token existente"
    echo "   - Verificar permissões de utilizador"
    echo ""
    echo "5. 🧪 Executar testes:"
    echo "   bash test_plugin_complete.sh"
    
    return 0
}

# Configuração interativa
interactive_setup() {
    echo "🛠️ CONFIGURAÇÃO INTERATIVA DO PLUGIN"
    echo "====================================="
    echo ""
    
    # Encontrar diretório Moodle
    echo_info "Procurando diretório do Moodle..."
    local moodle_path
    moodle_path=$(find_moodle_directory)
    
    if [[ $? -eq 0 ]]; then
        echo_success "Moodle encontrado em: $moodle_path"
        read -p "Usar este diretório? (Y/n): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Nn]$ ]]; then
            read -p "Digite o caminho para o Moodle: " moodle_path
        fi
    else
        echo_warning "Moodle não encontrado automaticamente"
        read -p "Digite o caminho para o Moodle: " moodle_path
    fi
    
    # Verificar diretório fornecido
    if ! verify_moodle_structure "$moodle_path"; then
        echo_error "Diretório Moodle inválido: $moodle_path"
        exit 1
    fi
    
    # Verificar dependências
    if ! check_dependencies; then
        echo_error "Dependências em falta"
        exit 1
    fi
    
    # Instalar plugin
    install_plugin "$moodle_path"
}

# Função de teste pós-instalação
post_install_test() {
    local moodle_url="${1:-$DEFAULT_MOODLE_URL}"
    
    echo ""
    echo_info "Teste rápido de conectividade..."
    
    # Testar conectividade básica
    if curl -s --head "$moodle_url" | head -n1 | grep -q "200 OK"; then
        echo_success "Moodle acessível em $moodle_url"
    else
        echo_warning "Moodle pode não estar acessível em $moodle_url"
        echo_info "Verifique se o servidor web está em execução"
    fi
}

# Função de ajuda
show_help() {
    echo "Script de Instalação - Plugin local_wsmanageactivities"
    echo ""
    echo "Uso: $0 [opções]"
    echo ""
    echo "Opções:"
    echo "  -h, --help              Mostrar esta ajuda"
    echo "  -p, --path CAMINHO      Especificar caminho do Moodle"
    echo "  -u, --url URL           Especificar URL do Moodle"
    echo "  -t, --test              Executar apenas testes"
    echo "  -i, --interactive       Configuração interativa (padrão)"
    echo ""
    echo "Exemplos:"
    echo "  $0                                    # Configuração interativa"
    echo "  $0 -p /var/www/html/moodle           # Especificar diretório"
    echo "  $0 -t -u http://localhost/moodle     # Testar conectividade"
    echo ""
}

# Processar argumentos da linha de comando
while [[ $# -gt 0 ]]; do
    case $1 in
        -h|--help)
            show_help
            exit 0
            ;;
        -p|--path)
            MOODLE_PATH="$2"
            shift 2
            ;;
        -u|--url)
            MOODLE_URL="$2"
            shift 2
            ;;
        -t|--test)
            TEST_ONLY=true
            shift
            ;;
        -i|--interactive)
            INTERACTIVE=true
            shift
            ;;
        *)
            echo_error "Opção desconhecida: $1"
            show_help
            exit 1
            ;;
    esac
done

# Verificar permissões
check_permissions

# Executar com base nos argumentos
if [[ "$TEST_ONLY" == "true" ]]; then
    post_install_test "${MOODLE_URL:-$DEFAULT_MOODLE_URL}"
elif [[ -n "$MOODLE_PATH" ]]; then
    # Instalação não-interativa
    if verify_moodle_structure "$MOODLE_PATH"; then
        check_dependencies && install_plugin "$MOODLE_PATH"
    else
        echo_error "Caminho Moodle inválido: $MOODLE_PATH"
        exit 1
    fi
else
    # Configuração interativa (padrão)
    interactive_setup
fi

# Teste final se não foi apenas teste
if [[ "$TEST_ONLY" != "true" ]]; then
    post_install_test "${MOODLE_URL:-$DEFAULT_MOODLE_URL}"
fi

echo ""
echo_success "Instalação concluída!"
echo_info "Próximos passos: consulte README.md para configuração completa"