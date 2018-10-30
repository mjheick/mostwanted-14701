-- phpMyAdmin SQL Dump
-- version 4.0.10.20
-- https://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 30, 2018 at 01:20 AM
-- Server version: 5.5.60-MariaDB
-- PHP Version: 5.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `chautauqua_sheriff.us`
--
CREATE DATABASE IF NOT EXISTS `chautauqua_sheriff.us` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `chautauqua_sheriff.us`;

-- --------------------------------------------------------

--
-- Table structure for table `most-wanted`
--

CREATE TABLE IF NOT EXISTS `most-wanted` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `guid` char(64) DEFAULT NULL,
  `uid` char(64) DEFAULT NULL,
  `image` varchar(64) NOT NULL,
  `source-url` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL,
  `address` varchar(64) NOT NULL,
  `vitals1` varchar(64) NOT NULL,
  `vitals2` varchar(64) NOT NULL,
  `wanted-by` varchar(64) NOT NULL,
  `charge` varchar(64) NOT NULL,
  `judge` varchar(64) NOT NULL,
  `added` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastseen` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `posted` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`pk`),
  KEY `guid` (`guid`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1856 ;

-- --------------------------------------------------------

--
-- Table structure for table `prisoner`
--

CREATE TABLE IF NOT EXISTS `prisoner` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `guid` char(64) DEFAULT NULL,
  `image` varchar(64) NOT NULL DEFAULT 'http://findour.info/mugs/',
  `name` varchar(64) NOT NULL DEFAULT 'unknown',
  `age` int(10) unsigned NOT NULL,
  `when_booked` char(32) NOT NULL,
  `category` enum('unknown','Unspecified','Felony','Violation','Misdemeanor') NOT NULL DEFAULT 'unknown',
  `bail` varchar(16) NOT NULL,
  `mfn` varchar(32) NOT NULL,
  `added` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastseen` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `posted` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`pk`),
  UNIQUE KEY `guid` (`guid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=219 ;

