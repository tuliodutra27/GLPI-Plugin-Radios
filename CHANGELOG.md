# Changelog

Todas as mudanças relevantes deste plugin ficam registradas aqui.
O formato segue o [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e as versões
seguem o [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [Não lançado]

## [1.1.1] - 2026-11-24

Requer Atualizar em Configurar > Plugins.

A atualização acrescenta uma coluna à tabela do histórico (`glpi_radios_historico`). Sem o
Atualizar, o histórico não consegue gravar as alterações do rádio.

### Corrigido
- O histórico do rádio não registrava as alterações no campo Comentários. Agora cada linha do
  histórico guarda o comentário do rádio naquele momento, e ele aparece na coluna "Comentários" da
  aba Histórico do rádio, na tela Histórico de Movimentações e no CSV exportado. Uma alteração só
  no comentário também gera uma linha nova.
- As linhas do histórico anteriores a esta versão mostram "-" em "Comentários", porque o comentário
  daquela época não era guardado.
- As telas antigas de cadastro e edição (`novo_radio.php` e `editar_radio.php`) também gravam o
  comentário no histórico.

## [1.1.0] - 2026-11-24

Requer Atualizar em Configurar > Plugins.

Esta versão não teve release própria: vai junto na 1.1.1.

### Adicionado
- Aba **Laudos** no formulário do rádio, depois do Histórico: os laudos do plugin Laudo que têm o
  rádio como "Ativo vinculado", com situação, setor, data e o chamado de cada um. Cada laudo abre
  em uma nova guia. A aba só aparece com o plugin Laudo ativo e para quem tem direito de ver
  laudos.
- Aba **Chamados** no formulário do rádio: os chamados ligados ao rádio pelo campo "Itens" do
  chamado do GLPI e os chamados informados nos laudos dele. Cada chamado aparece uma vez, com a
  origem do vínculo, e abre em uma nova guia. Quem não pode abrir o chamado vê só o número.
- As duas abas só leem os dados; nada é gravado.

### Alterado
- Quem atualiza da 1.0.0 recebe junto as correções das versões 1.0.1 a 1.0.3 (veja abaixo), que
  nunca tiveram release.

## Versões anteriores

As versões 1.0.0 a 1.0.3 (maio a setembro de 2026) nunca foram publicadas como release. Em resumo:
lista e formulário nativos do GLPI no menu Ativos, aba Histórico por rádio com paginação e
exportação em CSV, compatibilidade com o GLPI 11 e uma migração que acrescenta as colunas que
faltam em instalações antigas (ao clicar em Atualizar).

[Não lançado]: https://github.com/tuliodutra27/GLPI-Plugin-Radios/compare/v1.1.1...HEAD
[1.1.1]: https://github.com/tuliodutra27/GLPI-Plugin-Radios/releases/tag/v1.1.1
