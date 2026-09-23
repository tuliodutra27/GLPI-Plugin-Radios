<?php

/**
 * Instalação/atualização do plugin Radios.
 *
 * Cria as tabelas do zero numa instalação nova. Numa instalação já existente, o CREATE TABLE IF
 * NOT EXISTS nunca é reaplicado sobre uma tabela que já existe - então qualquer coluna nova
 * adicionada ao layout deste arquivo desde a instalação original nunca chegava na tabela real de
 * quem já tinha o plugin instalado (foi exatamente o que aconteceu em produção: a coluna `name`
 * de glpi_plugin_radios_radios existe aqui no CREATE TABLE, mas a tabela real, criada por uma
 * versão bem mais antiga deste arquivo, nunca a ganhou - só percebido quando o plugin Laudo
 * passou a depender dela pra ordenar/filtrar o seletor de "Ativo vinculado"). Por isso, quando a
 * tabela já existe, roda uma migração (mesmo padrão usado no plugin laudo) que adiciona, via
 * fieldExists()/addField(), qualquer coluna do layout atual que ainda esteja faltando - segura
 * pra rodar em toda reinstalação/atualização, não mexe em coluna já existente.
 */
function plugin_radios_install() {
    global $DB;

    $migration = new Migration(PLUGIN_RADIOS_VERSION);

    // Criação da tabela principal glpi_plugin_radios_radios
    $table_radios = 'glpi_plugin_radios_radios';
    if (!$DB->tableExists($table_radios)) {
        $query_radios = "CREATE TABLE `$table_radios` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) DEFAULT NULL,
            `manufacturers_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `model` VARCHAR(255) DEFAULT NULL,
            `serial` VARCHAR(255) DEFAULT NULL,
            `otherserial` VARCHAR(255) DEFAULT NULL,
            `chave_nf` VARCHAR(44) DEFAULT NULL,
            `comment` TEXT,
            `states_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `users_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `groups_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `locations_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `entities_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `date_creation` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `date_mod` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
            `is_template` TINYINT(1) NOT NULL DEFAULT 0,
            `template_name` VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_manufacturer` (`manufacturers_id`),
            KEY `idx_state` (`states_id`),
            KEY `idx_user` (`users_id`),
            KEY `idx_group` (`groups_id`),
            KEY `idx_location` (`locations_id`),
            KEY `idx_entity` (`entities_id`),
            KEY `idx_chave_nf` (`chave_nf`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $DB->doQuery($query_radios);
    } else {
        // Layout completo da tabela acima, coluna => definição SQL - usado só pra descobrir o
        // que falta numa instalação existente (não mexe em coluna já presente).
        $radiosFields = [
            'name'             => "VARCHAR(255) DEFAULT NULL",
            'manufacturers_id' => "INT(11) UNSIGNED NOT NULL DEFAULT 0",
            'model'            => "VARCHAR(255) DEFAULT NULL",
            'serial'           => "VARCHAR(255) DEFAULT NULL",
            'otherserial'      => "VARCHAR(255) DEFAULT NULL",
            'chave_nf'         => "VARCHAR(44) DEFAULT NULL",
            'comment'          => "TEXT",
            'states_id'        => "INT(11) UNSIGNED NOT NULL DEFAULT 0",
            'users_id'         => "INT(11) UNSIGNED NOT NULL DEFAULT 0",
            'groups_id'        => "INT(11) UNSIGNED NOT NULL DEFAULT 0",
            'locations_id'     => "INT(11) UNSIGNED NOT NULL DEFAULT 0",
            'entities_id'      => "INT(11) UNSIGNED NOT NULL DEFAULT 0",
            'is_deleted'       => "TINYINT(1) NOT NULL DEFAULT 0",
            'is_template'      => "TINYINT(1) NOT NULL DEFAULT 0",
            'template_name'    => "VARCHAR(255) DEFAULT NULL",
        ];
        // Só os campos que tinham KEY própria no CREATE TABLE acima.
        $radiosKeyed = ['manufacturers_id', 'states_id', 'users_id', 'groups_id', 'locations_id', 'entities_id', 'chave_nf'];

        foreach ($radiosFields as $field => $definition) {
            if (!$DB->fieldExists($table_radios, $field)) {
                $migration->addField($table_radios, $field, $definition);
                if (in_array($field, $radiosKeyed, true)) {
                    $migration->addKey($table_radios, $field);
                }
            }
        }
    }

    // Criação da tabela de histórico glpi_radios_historico - VERSÃO ATUALIZADA
    $table_historico = 'glpi_radios_historico';
    if (!$DB->tableExists($table_historico)) {
        $query_historico = "CREATE TABLE `$table_historico` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `radios_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `serial` VARCHAR(255) DEFAULT NULL,
            `model` VARCHAR(255) DEFAULT NULL,
            `manufacturers_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `patrimonio` VARCHAR(255) DEFAULT NULL,
            `states_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `groups_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `users_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `locations_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `tecnico_alterou_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `data_movimentacao` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `entities_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `idx_radios_id` (`radios_id`),
            KEY `idx_data_movimentacao` (`data_movimentacao`),
            KEY `idx_tecnico` (`tecnico_alterou_id`),
            KEY `idx_serial` (`serial`),
            KEY `idx_patrimonio` (`patrimonio`),
            CONSTRAINT `fk_radios_historico_radios` FOREIGN KEY (`radios_id`) REFERENCES `glpi_plugin_radios_radios` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $DB->doQuery($query_historico);
    } else {
        $historicoFields = [
            'serial'             => "VARCHAR(255) DEFAULT NULL",
            'model'              => "VARCHAR(255) DEFAULT NULL",
            'manufacturers_id'   => "INT(11) UNSIGNED NOT NULL DEFAULT 0",
            'patrimonio'         => "VARCHAR(255) DEFAULT NULL",
            'states_id'          => "INT(11) UNSIGNED NOT NULL DEFAULT 0",
            'groups_id'          => "INT(11) UNSIGNED NOT NULL DEFAULT 0",
            'users_id'           => "INT(11) UNSIGNED NOT NULL DEFAULT 0",
            'locations_id'       => "INT(11) UNSIGNED NOT NULL DEFAULT 0",
            'tecnico_alterou_id' => "INT(11) UNSIGNED NOT NULL DEFAULT 0",
            'entities_id'        => "INT(11) UNSIGNED NOT NULL DEFAULT 0",
        ];
        $historicoKeyed = ['serial', 'patrimonio', 'tecnico_alterou_id'];

        foreach ($historicoFields as $field => $definition) {
            if (!$DB->fieldExists($table_historico, $field)) {
                $migration->addField($table_historico, $field, $definition);
                if (in_array($field, $historicoKeyed, true)) {
                    $migration->addKey($table_historico, $field);
                }
            }
        }
    }

    // Criação da tabela glpi_pre_update_radios - auxiliar do instalador, não usada pelo fluxo
    // atual do plugin (ver README.md), então sem migração de colunas: nada depende do layout
    // dela estar completo.
    $table_pre_update = 'glpi_pre_update_radios';
    $query_pre_update = "CREATE TABLE IF NOT EXISTS `$table_pre_update` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) DEFAULT NULL,
        `manufacturers_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `model` VARCHAR(255) DEFAULT NULL,
        `serial` VARCHAR(255) DEFAULT NULL,
        `otherserial` VARCHAR(255) DEFAULT NULL,
        `chave_nf` VARCHAR(44) DEFAULT NULL,
        `comment` TEXT,
        `states_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `users_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `groups_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `locations_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `entities_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `date_creation` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        `date_mod` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
        `is_template` TINYINT(1) NOT NULL DEFAULT 0,
        `template_name` VARCHAR(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_pre_manufacturer` (`manufacturers_id`),
        KEY `idx_pre_state` (`states_id`),
        KEY `idx_pre_user` (`users_id`),
        KEY `idx_pre_group` (`groups_id`),
        KEY `idx_pre_location` (`locations_id`),
        KEY `idx_pre_entity` (`entities_id`),
        KEY `idx_pre_serial` (`serial`),
        KEY `idx_pre_otherserial` (`otherserial`),
        KEY `idx_pre_chave_nf` (`chave_nf`),
        KEY `idx_pre_name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $DB->doQuery($query_pre_update);

    $migration->executeMigration();

    return true;
}

function plugin_radios_uninstall() {
    global $DB;

    // Remove primeiro a tabela com foreign key (histórico)
    $DB->doQuery("DROP TABLE IF EXISTS `glpi_radios_historico`");

    // Remove a tabela de pré-atualização
    $DB->doQuery("DROP TABLE IF EXISTS `glpi_pre_update_radios`");

    // Por último remove a tabela principal
    $DB->doQuery("DROP TABLE IF EXISTS `glpi_plugin_radios_radios`");

    return true;
}
