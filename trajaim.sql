-- phpMyAdmin SQL Dump
-- version 4.1.12
-- http://www.phpmyadmin.net
--
-- Φιλοξενητής: 127.0.0.1
-- Χρόνος δημιουργίας: 16 Δεκ 2017 στις 11:51:24
-- Έκδοση διακομιστή: 5.6.16
-- Έκδοση PHP: 5.5.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Βάση δεδομένων: `trajaim`
--

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `veh_traj`
--

CREATE TABLE IF NOT EXISTS `veh_traj` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `EEIS_Enabled` int(2) NOT NULL DEFAULT '0' COMMENT 'Speed advice enabled or disabled',
  `VehNr` int(11) NOT NULL COMMENT 'Vehicle ID',
  `LVeh` int(11) NOT NULL COMMENT 'ID of the next vehicle downstream',
  `Type` int(6) NOT NULL COMMENT 'Vehicle Type ID',
  `VehTypeName` varchar(20) NOT NULL COMMENT 'Vehicle Type Name',
  `Length` float DEFAULT '0' COMMENT 'Vehicle Length [m]',
  `t` float NOT NULL DEFAULT '-1' COMMENT 'Simulation Time [s]',
  `a` float NOT NULL COMMENT 'Acceleration [m/s^2] during the simulation step',
  `v` float NOT NULL COMMENT 'Speed [m/s] at the end of the simulation step',
  `DesLn` int(4) NOT NULL COMMENT 'Desired Lane (by Direction decision)',
  `Grad` float NOT NULL DEFAULT '0' COMMENT 'Gradient [%] of the current link',
  `WorldX` float NOT NULL COMMENT 'World coordinate x (vehicle front end at the end of the simulation step)',
  `WorldY` float NOT NULL COMMENT 'World coordinate y (vehicle front end at the end of the simulation step)',
  `WorldZ` float NOT NULL COMMENT 'World coordinate z (vehicle front end at the end of the simulation step)',
  `RWorldX` float NOT NULL COMMENT 'World coordinate x (vehicle rear end at the end of the time step)',
  `RWorldY` float NOT NULL COMMENT 'World coordinate y (vehicle rear end at the end of the time step)',
  `RWorldZ` float NOT NULL COMMENT 'World coordinate z (vehicle rear end at the end of the time step)',
  `x` float NOT NULL COMMENT 'Distance from the start position of the current section or turn the vehicle is in to the front part of the vehicle [m] at the end of the simultion step',
  `y` float NOT NULL COMMENT 'Lateral position relative to middle of lane (0.5) at the end of the simulation step',
  `comment` varchar(200) NOT NULL COMMENT 'Comment',
  PRIMARY KEY (`id`),
  KEY `VehNrIDX` (`VehNr`),
  KEY `VehTypenameIDX` (`VehTypeName`),
  KEY `EEIS_idx` (`EEIS_Enabled`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=103478 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
