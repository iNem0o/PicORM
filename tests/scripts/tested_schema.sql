CREATE TABLE IF NOT EXISTS `brands` (
  `idBrand` int(11) NOT NULL AUTO_INCREMENT,
  `nameBrand` varchar(100) NOT NULL,
  `noteBrand` float DEFAULT '0',
  PRIMARY KEY (`idBrand`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `cars` (
  `idCar` int(11) NOT NULL AUTO_INCREMENT,
  `idBrand` int(11) NOT NULL,
  `nameCar` varchar(100) NOT NULL,
  `noteCar` float NOT NULL,
  PRIMARY KEY (`idCar`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `car_have_tag` (
  `idCar` int(11) NOT NULL,
  `idTag` int(11) NOT NULL,
  PRIMARY KEY (`idCar`,`idTag`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tags` (
  `idTag` int(11) NOT NULL AUTO_INCREMENT,
  `libTag` varchar(255) NOT NULL,
  PRIMARY KEY (`idTag`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

TRUNCATE brands;;
TRUNCATE cars;
TRUNCATE car_have_tag;
TRUNCATE tags;