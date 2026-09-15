# Plugin Radios — GLPI

Plugin para **cadastro, gestão e histórico de rádios comunicadores** no GLPI, integrado ao menu Ativos.

> Este plugin era mantido junto com o [plugin Termos](https://github.com/tuliodutra27/plugin-termos)
> no mesmo repositório; a partir desta versão vive em repositório próprio.

## Requisitos

- GLPI 10.0.0 ou superior (compatível com GLPI 11)
- PHP 8.0+

## Instalação

### 1. Copiar o plugin

Clone este repositório (ou copie seu conteúdo) para o diretório de plugins do GLPI, na pasta `radios/`:

```bash
git clone https://github.com/tuliodutra27/GLPI-Plugin-Radios.git /var/www/glpi/plugins/radios
```

### 2. Ativar no GLPI

Acesse **Configuração → Plugins**, localize **Radios** e clique em **Instalar**, depois em **Ativar**.

## Tabelas criadas

| Tabela | Descrição |
|---|---|
| `glpi_plugin_radios_radios` | Cadastro principal dos rádios |
| `glpi_radios_historico` | Histórico de todas as alterações realizadas |
| `glpi_pre_update_radios` | Tabela auxiliar criada/removida pelo instalador; não é usada pelo fluxo atual (mantida por compatibilidade) |

## Campos do cadastro

| Campo | Descrição |
|---|---|
| Fabricante | Fabricante do equipamento (tabela `glpi_manufacturers`) |
| Modelo | Modelo do rádio |
| Número de Série | Identificador único — validado contra duplicidade |
| Patrimônio | Número de patrimônio interno (`otherserial`) |
| Chave da Nota Fiscal | Chave NF-e (até 44 caracteres) |
| Status | Status do equipamento (tabela `glpi_states`) |
| Localização | Localização física (tabela `glpi_locations`) |
| Grupo | Grupo responsável (tabela `glpi_groups`) |
| Usuário | Usuário responsável (tabela `glpi_users`) |
| Observações | Campo livre de observações |

## Funcionalidades

### Lista de Rádios

Exibe todos os rádios ativos com filtros por:
- Número de série
- Fabricante
- Modelo
- Status
- Grupo
- Usuário
- Localização
- Ordenação configurável

Paginação de 100 itens por página.

### Cadastrar Novo Rádio

Formulário de cadastro com validação de série duplicada. O cadastro já registra automaticamente a primeira entrada no histórico.

### Editar Rádio

Edição completa dos dados. O sistema captura o estado anterior ao salvar e registra no histórico **apenas os campos que foram alterados**, identificando o técnico responsável pela alteração e a data/hora.

**Campos monitorados no histórico:** série, modelo, fabricante, patrimônio, status, grupo, usuário, localização.

### Histórico de Movimentações

Visualização de todas as alterações do rádio com filtros por número de série e período. Exportação disponível em **CSV** (UTF-8 com BOM para compatibilidade com Excel, separador `;`).

### Exportar Lista (CSV)

Exporta a lista filtrada atual para CSV com os mesmos campos da listagem.

### Excluir Rádio

Exclusão lógica (soft delete — campo `is_deleted = 1`). O rádio não aparece mais na listagem mas os dados são preservados no banco.

## Desinstalação

Acesse **Configuração → Plugins**, localize **Radios**, clique em **Desativar** e depois em **Desinstalar**.

> A desinstalação remove permanentemente as tabelas `glpi_radios_historico`, `glpi_pre_update_radios` e `glpi_plugin_radios_radios`.

## Autores

Diego, Luciano, Rafael e Tulio — ALISEO SA
