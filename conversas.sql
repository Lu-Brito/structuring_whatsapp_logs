CREATE TABLE `conversas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversa` varchar(500) DEFAULT NULL,
  `data` datetime DEFAULT NULL,
  `contato` varchar(500) DEFAULT NULL,
  `mensagem` text DEFAULT NULL,
  `is_contato_desconhecido` int(11) DEFAULT 0,
  `is_anexo` int(11) DEFAULT 0,
  `extensao_anexo` varchar(500) DEFAULT NULL,
  `is_enviada_por_mim` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;