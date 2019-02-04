-- Predisposizione tabella su database MySQL locale

CREATE TABLE `fatture_elettroniche` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_fattura` int(11) DEFAULT NULL, -- RIFERIMENTO ALLA FATTURA SUL PROPRIO DATABASE
  `sdi_identificativo` varchar(36) CHARACTER SET utf8 NOT NULL,
  `sdi_stato` varchar(14) CHARACTER SET utf8 NOT NULL,
  `sdi_fattura` mediumtext CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `sdi_fattura_xml` mediumtext CHARACTER SET utf8 NOT NULL,
  `sdi_data_aggiornamento` datetime NOT NULL,
  `sdi_messaggio` text CHARACTER SET utf8 NOT NULL,
  `sdi_nome_file` varchar(50) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_fattura` (`id_fattura`)
);
