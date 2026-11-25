# Versionamento e releases — plugin Radios

Este documento define **como numerar, registrar e publicar versões** do plugin. Segue o mesmo
desenho do plugin Checklist uso de equipamentos (repositório GLPI-Plugin-Checklist-Itens), em
versão mais curta: só o número, o changelog e a rotina de release.

## 1. Resumo

| O quê | Padrão | Onde vive |
|---|---|---|
| Número da versão | [SemVer 2.0.0](https://semver.org/) (`MAJOR.MINOR.PATCH`) | constante `PLUGIN_RADIOS_VERSION` em `setup.php` |
| Marca da versão no Git | tag anotada `vX.Y.Z` | repositório; o workflow `release.yml` gera o zip e a release |
| Histórico para as pessoas | [Keep a Changelog 1.1.0](https://keepachangelog.com/pt-BR/1.1.0/) | `CHANGELOG.md` na raiz |
| Ramos | `main` sempre publicável; mudança maior entra por ramo curto `feat/<assunto>` ou `fix/<assunto>` | testada no GLPI de teste antes de voltar para a `main` |

Regras que não mudam:

1. A versão só existe em **um** lugar no código (`setup.php`). A tag, o zip e o título da release
   derivam dela.
2. **Versão publicada não muda.** Errou depois de publicar? Sai uma versão nova. Nunca se move nem se
   apaga uma tag de release já criada.
3. Toda mudança que a pessoa percebe entra no `CHANGELOG.md`.
4. O número no `setup.php` só sobe no commit de release.

## 2. Número da versão

| Sobe | Quando | Exemplo |
|---|---|---|
| **PATCH** (1.1.1 → 1.1.2) | só correção de defeito, **sem mexer no banco** e sem tela ou fluxo novo | corrigir texto, consulta, permissão |
| **MINOR** (1.1.1 → 1.2.0) | funcionalidade nova **ou qualquer mudança no banco** (coluna ou tabela nova) | nova aba, nova coluna |
| **MAJOR** (1.1.1 → 2.0.0) | mudança incompatível: tabela ou coluna removida, mudança de direitos, faixa de GLPI suportada reduzida | — |

O GLPI compara o número do `setup.php` com o gravado no banco **como texto**: qualquer mudança de
número, inclusive de PATCH, faz o plugin pedir **Atualizar** em Configurar > Plugins. Por isso a
instalação (`hook.php`) precisa poder rodar várias vezes seguidas sem erro, criando só o que falta.

### Pré-lançamento

Formato `X.Y.Z-rc.N` (`1.2.0-rc.1`). A tag com hífen sai no GitHub como **pré-lançamento**.
Recomendado em versão que muda o banco: instale o zip do rc no GLPI de teste antes da versão final.
Cada rc e a versão final pedem Atualizar.

## 3. Changelog

Arquivo `CHANGELOG.md` na raiz, escrito **para pessoas** (quem instala e usa), não é cópia do
`git log`.

- Versões da mais nova para a mais antiga, cada uma com a data em formato `AAAA-MM-DD`.
- Seção **`[Não lançado]`** sempre no topo. Cada mudança visível entra ali no mesmo momento do
  commit.
- Categorias: **Adicionado, Alterado, Descontinuado, Removido, Corrigido, Segurança.** Use só as que
  se aplicam.
- Versão com Atualizar leva a linha **"Requer Atualizar em Configurar > Plugins."** logo abaixo do
  título, dizendo se o banco muda.
- Mudança incompatível vai em **negrito** no começo da linha.

## 4. Rotina de release

Para cada versão `X.Y.Z`:

1. A `main` tem tudo o que vai na versão e a árvore de trabalho está limpa (`git status`).
2. Defina o número pela tabela da seção 2.
3. No `CHANGELOG.md`: mova o `[Não lançado]` para a seção `[X.Y.Z] - AAAA-MM-DD` e atualize os
   links no rodapé.
4. Em `setup.php`: ajuste `PLUGIN_RADIOS_VERSION` para `X.Y.Z`.
5. Em `radio.xml`: acrescente a versão em `<versions>`, com a faixa de GLPI em que foi testada.
6. Commit: `chore(release): X.Y.Z`.
7. Escreva o texto da tag em um arquivo **fora** do repositório e crie a **tag anotada**:

   ```
   git tag -a vX.Y.Z -F notas-da-tag.txt
   ```

   Modelo do texto:

   ```
   Radios X.Y.Z

   Para GLPI 10.0.x.

   Novidades
   - …

   Instalação
   1. Baixe o radios-X.Y.Z.zip abaixo e extraia dentro da pasta plugins do GLPI
      (a pasta precisa se chamar radios).
   2. Em Configurar > Plugins, instale e ative.

   Atualização: substitua os arquivos da pasta e, em Configurar > Plugins, clique em Atualizar e
   depois em Ativar (a X.Y.Z muda o banco).

   Mudanças completas: CHANGELOG.md, no repositório do GitHub.
   ```

   A linha entre parênteses da Atualização só diz que muda o banco quando muda; senão, diga que a
   estrutura do banco não muda.
8. Envie: `git push origin main` e, separado, `git push origin vX.Y.Z`.
9. Confira na aba **Actions** do GitHub que o workflow **Release** terminou verde, que a release tem
   o zip `radios-X.Y.Z.zip`, que o zip só tem o código do plugin, e instale esse zip uma vez para
   ver que sobe.

## 5. O que o workflow faz

`.github/workflows/release.yml` roda quando uma tag `v*` é enviada:

1. recusa a tag se ela for diferente da versão do `setup.php`;
2. gera `radios-X.Y.Z.zip` com `git archive`, dentro da pasta `radios/` (o GLPI exige esse nome);
3. cria a release com o zip e o texto da tag como descrição (tag com hífen vira pré-lançamento).

O `.gitattributes` fixa os finais de linha em LF (o plugin roda em Linux) e define o que fica **fora**
do zip, para ele levar só o código do plugin: `.github`, `.gitattributes`, `.gitignore`, `docs/`,
`README.md`, `CHANGELOG.md` e `radio.xml` (os dados do plugin para o diretório do GLPI, que ficam no
repositório). **Arquivo novo que não é código do plugin precisa entrar no `.gitattributes` com
`export-ignore`.**

Se o workflow falhar **antes** de criar a release, a tag ainda não foi publicada e pode ser
refeita; **depois** que a release existe, só sai versão nova.
