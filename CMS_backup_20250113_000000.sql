/*!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.8-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: CMS
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `addresses` (
  `id` varchar(36) NOT NULL,
  `name` varchar(64) NOT NULL,
  `description` text NOT NULL,
  `country` varchar(64) NOT NULL,
  `street` varchar(64) NOT NULL,
  `city` varchar(64) NOT NULL,
  `zipcode` varchar(10) NOT NULL,
  `site` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
INSERT INTO `addresses` VALUES
('2e9de486-be37-11ef-aadb-0efd67b0fe77','Infinity Centre','Center inf','Congo','vingt-quatre','Kinshasa','2423235','Kinshasa'),
('4d5ad62e-be3a-11ef-aadb-0efd67b0fe77','AUB','American University of Beirut','Lebanon','Bliss Street','Beirut','000000','University'),
('4d91859a-be3d-11ef-aadb-0efd67b0fe77','New Address','This is an Address','Lebanon','Street 435','Sour','451246','Afrifood');
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_addresses` BEFORE INSERT ON `addresses` FOR EACH ROW BEGIN
    -- Set the `id` field to a new UUID
    SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `bol`
--

DROP TABLE IF EXISTS `bol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bol` (
  `id` varchar(36) NOT NULL,
  `number` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `company_id` varchar(36) NOT NULL,
  `site_id` varchar(36) NOT NULL,
  `reference` varchar(128) NOT NULL DEFAULT 'Default',
  `has_containers` int(11) NOT NULL DEFAULT 0,
  `shipping_line_id` varchar(36) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bol`
--

LOCK TABLES `bol` WRITE;
/*!40000 ALTER TABLE `bol` DISABLE KEYS */;
INSERT INTO `bol` VALUES
('00394cdd-cd13-11ef-96a0-2cf05d502fc1','HLCUIZ1241130043','FORMALDEHYDE %37','2025-01-07 16:18:15','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','57ad7ac7-cd91-11ef-96a0-2cf05d502fc1','DC2549-1',1,'c1c395bc-c116-11ef-9b34-0efd67b0fe77'),
('0e50bee7-cd9f-11ef-96a0-2cf05d502fc1','245738836','1 Container Said to Contain 960 BAGS\r\nSULFATE DE ZINC 35% POUDRE\r\nEMBALLAGE: SAC DE 25 KG NET SUR PALETTES D\'1.2 TONS','2025-01-08 09:00:49','ad6e19b7-ccf5-11ef-96a0-2cf05d502fc1','d490de75-ccf5-11ef-96a0-2cf05d502fc1','DC2385',1,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('12e150de-cdc2-11ef-96a0-2cf05d502fc1','245478283','3 containers said to contain 132 PACKAGES\r\nkitchen accessories','2025-01-08 13:11:29','ad6e195e-ccf5-11ef-96a0-2cf05d502fc1','d490e025-ccf5-11ef-96a0-2cf05d502fc1','DC2446-1',3,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('15213cae-cdbf-11ef-96a0-2cf05d502fc1','APU087872','1 Container Said to Contain 2136 CARTON\r\n2136 CARTONS OF CONFECTIONERY CONTAINING PIN POP XS STRAWBERRY 16X48 (13G)\r\nDT EXPORT','2025-01-08 12:50:04','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','MAT1720/2',1,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('19c65bfd-cda2-11ef-96a0-2cf05d502fc1','245985119','4 containers said to contain 3840 BAGS\r\nTHAI JASMINE RICE\r\nPACKING IN LLDPE BAG OF 5 KGS WITH PUNCH HANDLE,\r\n5 SUCH BAGS ARE PACKED IN MASTER PP BAG OF 25KGS','2025-01-08 09:22:36','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','PIP275',4,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('23850897-cd97-11ef-96a0-2cf05d502fc1','245663036','2 containers said to contain 5026 CARTONS\r\nCANDY','2025-01-08 08:04:08','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','MAT1739',2,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('27f44aaa-cd9e-11ef-96a0-2cf05d502fc1','285109806','1 Container Said to Contain 450 DRUMS\r\nCALCIUM CARBIDE','2025-01-08 08:54:22','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','d490e1d2-ccf5-11ef-96a0-2cf05d502fc1','DC2526/2',1,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('283dca50-cfff-11ef-96a0-2cf05d502fc1','246094567','9 containers said to contain 18 UNITS\r\n11 UNITS SINOTRUK HOWO 4X2 LIGHT CARGO TRUCK (LHD)','2025-01-11 09:33:46','ad6e19b7-ccf5-11ef-96a0-2cf05d502fc1','d4926b33-ccf5-11ef-96a0-2cf05d502fc1','DC2424',9,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('2d6e4860-cd10-11ef-96a0-2cf05d502fc1','TJN0499827','TOMATO PASTE','2025-01-07 15:58:03','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','MAT1702-2',20,'a0efe016-c116-11ef-9b34-0efd67b0fe77'),
('330b6888-cd0a-11ef-96a0-2cf05d502fc1','HLCUDUR241119210','MONO AMMONIUM PHOPSPHATE','2025-01-07 15:15:15','ad6e19b7-ccf5-11ef-96a0-2cf05d502fc1','d490de75-ccf5-11ef-96a0-2cf05d502fc1','DC2585',4,'c1c395bc-c116-11ef-9b34-0efd67b0fe77'),
('35731d22-cd9b-11ef-96a0-2cf05d502fc1','APU097476','5 CONTAINERS SAID TO CONTAIN 7800 CARTSONS OF :\r\nCOCOA CREAM BISCUITS 190531','2025-01-08 08:33:16','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1280-3',5,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('381ec88d-cda8-11ef-96a0-2cf05d502fc1','246093570','1 Container Said to Contain 1080 BAGS OF AMMONIUM BICARBONATE\r\n','2025-01-08 10:06:24','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','d4926cff-ccf5-11ef-96a0-2cf05d502fc1','DC2538',1,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('3c956aa4-cd0d-11ef-96a0-2cf05d502fc1','ISB1605639','EDGE BANDING MACHINE','2025-01-07 15:37:00','ad6e197a-ccf5-11ef-96a0-2cf05d502fc1','d4926b56-ccf5-11ef-96a0-2cf05d502fc1','DC2496',1,'a0efe016-c116-11ef-9b34-0efd67b0fe77'),
('3da00aee-cd9a-11ef-96a0-2cf05d502fc1','246311351','1 Container Said to Contain 828 PACKAGES\r\nBUILDING MATERIALS','2025-01-08 08:26:21','ad6e19ef-ccf5-11ef-96a0-2cf05d502fc1','d490e06d-ccf5-11ef-96a0-2cf05d502fc1','INF101',1,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('3f6484b4-cd11-11ef-96a0-2cf05d502fc1','TJN0499794','TOMATO PASTE','2025-01-07 16:05:42','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','MAT1701-2',5,'a0efe016-c116-11ef-9b34-0efd67b0fe77'),
('3fd2496d-cd9d-11ef-96a0-2cf05d502fc1','APU076411','2 containers said to contain 5200 CARTONS\r\n2X40\' HC CONTAINERS\r\nTOTAL 5200 CARTONS\r\nMAMA CHOCO CARAMEL LOLLIPOPS','2025-01-08 08:47:53','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','MAT1801',2,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('409274d9-cdc1-11ef-96a0-2cf05d502fc1','244269282','1 Container Said to Contain 1020 Bag\r\nH.S.CODE NO.1901.1090\r\n1 x 40HC - said Contain:\r\n1020 Wheat cereal 25kg','2025-01-08 13:05:36','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','d4926cff-ccf5-11ef-96a0-2cf05d502fc1','DC2378-1',1,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('4752f3e7-cda5-11ef-96a0-2cf05d502fc1','246456380','5 containers said to contain 13250 PACKAGE\r\nPASTA','2025-01-08 09:45:21','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1269-2',5,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('4bef52a0-cdbf-11ef-96a0-2cf05d502fc1','APU087875','1 Container Said to Contain 2136 CARTON\r\n\r\n2136 CARTONS OF CONFECTIONERY WITH PIN POP MEGA MIX XS 16X48 (13G) DT\r\nEXPORTACION\r\n','2025-01-08 12:51:36','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','MAT1718/2',1,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('4c56a566-cda6-11ef-96a0-2cf05d502fc1','APU086747','7 containers said to contain 7210 CARTONS\r\n129.780 MTS / 7,210 CARTONS\r\nREFINED VEGETABLE PALM OLEIN (5L)\r\nAVENA BRAND IV60','2025-01-08 09:52:39','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1296-1',7,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('51e90e08-cd0b-11ef-96a0-2cf05d502fc1','MEDUEV509751','1X40 * CNTR(S) S.T.C\r\n\r\n3020 CT\r\nPRAN CHOCO CHOCO\r\nTOTAL 3020 CARTONS','2025-01-07 15:23:17','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','MAT1783',1,'03101185-cd9f-11ef-96a0-2cf05d502fc1'),
('534d20d7-cd07-11ef-96a0-2cf05d502fc1','APU085011','1 Container Said to Contain 2650 CARTONS\r\n\r\n03 X 40 FT. HIGHT CUBE\r\n\r\n7950 BOXES MINI FORT POP MILK CARAMEL FLAVOR 6G\r\n\r\n\r\n','2025-01-07 14:54:41','ad6e193e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1257-3',3,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('5fb2a13c-cd9f-11ef-96a0-2cf05d502fc1','246093934','1 Container Said to Contain 1049 PACKAGES\r\nBAKING POWDER\r\nUMBRELLA\r\nAPRON','2025-01-08 09:03:05','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','d4926cff-ccf5-11ef-96a0-2cf05d502fc1','DC2441',1,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('5fba868d-cdc6-11ef-96a0-2cf05d502fc1','AMC2311701','PACKAGING MATERIAL ','2025-01-08 13:42:16','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','d4926cff-ccf5-11ef-96a0-2cf05d502fc1','DC2501-2',4,'a0efe016-c116-11ef-9b34-0efd67b0fe77'),
('62a5cda2-cd13-11ef-96a0-2cf05d502fc1','HLCUIZ1241130087','FORMALDEHYDE %37','2025-01-07 16:21:01','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','57ad7ac7-cd91-11ef-96a0-2cf05d502fc1','DC2549-3',1,'c1c395bc-c116-11ef-9b34-0efd67b0fe77'),
('651f428d-cd12-11ef-96a0-2cf05d502fc1','HLCUIZ1241098067','15 CASE\r\nCONCRETE BATCHING PLANT','2025-01-07 16:13:55','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','d490e06d-ccf5-11ef-96a0-2cf05d502fc1','DC2494',1,'c1c395bc-c116-11ef-9b34-0efd67b0fe77'),
('6900f175-cda2-11ef-96a0-2cf05d502fc1','245720649','1 Container Said to Contain 2500 CARTONS\r\n\"LEBEST BRAND\"\r\nPURE CEYLON BLACK TEA CONTAINING EACH 2500 CORRUGATED MASTER CARTONS OF\r\n100TBX12X2GRM TEA BAGS WITH ENVELOPE','2025-01-08 09:24:49','ad6e19b7-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','MAT1730/2',1,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('75a48e36-cd0d-11ef-96a0-2cf05d502fc1','DXB0908428B','1X40HC FCL CNTR STC\r\nTOTAL: 1900 CARTONS\r\nINSTANT FULL CREAM MILK POWDER\r\nBRAND: MILGRO PRIMA\r\n(400G X 24 TINS PER CARTON)','2025-01-07 15:38:35','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1298 ',1,'a0efe016-c116-11ef-9b34-0efd67b0fe77'),
('7c0c9b29-cd09-11ef-96a0-2cf05d502fc1','HLCUSS5241013687','1 CONT. 40\'X9\'6\" REEFER CONTAINER SLAC\r\n520 BAG, POLYBAG\r\nBRASILIAN BEANS\r\n(FEIJAO BOLINHA)','2025-01-07 15:10:08','ad6e19b7-ccf5-11ef-96a0-2cf05d502fc1','d490de75-ccf5-11ef-96a0-2cf05d502fc1','DC2537',1,'c1c395bc-c116-11ef-9b34-0efd67b0fe77'),
('7c71fbff-cdc1-11ef-96a0-2cf05d502fc1','242513084','3 containers said to contain 3 UNITS\r\n3 x 40FR - Forty foot flatrack said to contain 03 units.\r\n1Tractor NH model T7.260 Pivo - rodado DUALL,\r\nwith Auto steer with navigator and antena intellewil,\r\nRTX center pointer fo tractor chip.\r\nNCM - HS - 87019590\r\n2 Harvester NH model CR6.80 DUALL Pilot 4WD\r\nNCM - HS - 84335100','2025-01-08 13:07:16','ad6e19b7-ccf5-11ef-96a0-2cf05d502fc1','d490de75-ccf5-11ef-96a0-2cf05d502fc1','DC2256',3,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('7d47b035-cd0e-11ef-96a0-2cf05d502fc1','DXB0908428C','1X40\'HC FCL CNTR STC\r\nTOTAL: 1941 CARTONS\r\nINSTANT FULL CREAM MILK POWDER\r\nBRAND: MILGRO PRIMA\r\n(900G X 12 TINS PER CARTON)','2025-01-07 15:45:58','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1299',1,'a0efe016-c116-11ef-9b34-0efd67b0fe77'),
('85985879-cd0f-11ef-96a0-2cf05d502fc1','TJN0499811','TOMATO PASTE','2025-01-07 15:53:21','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','MAT1700-2',5,'a0efe016-c116-11ef-9b34-0efd67b0fe77'),
('898fe37b-cd00-11ef-96a0-2cf05d502fc1','246315834','10 containers said to contain 33750 CARTON\r\n\r\nSPAGHETTI','2025-01-07 14:06:05','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1293-1',10,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('8ab36ed8-cdbf-11ef-96a0-2cf05d502fc1','APU087876','1 Container Said to Contain 2136 CARTON\r\n2136 CARTONS OF CONFECTIONERY CONTAINING PIN POP TROPICAL MIX XS 16X48\r\n(13G) DT EXPORT','2025-01-08 12:53:21','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','MAT1719/2',1,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('8d97853f-cdbe-11ef-96a0-2cf05d502fc1','246416598','2 containers said to contain 466 PACKAGE\r\nSOFABED\r\nPANEL FURNITURE\r\nSITTING GORUP\r\nPANEL FURNITURE','2025-01-08 12:46:17','ad6e197a-ccf5-11ef-96a0-2cf05d502fc1','d4926b56-ccf5-11ef-96a0-2cf05d502fc1','DC2476',2,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('8e940cb0-cda4-11ef-96a0-2cf05d502fc1','246359354','10 containers said to contain 33750 CARTONS\r\n\r\nSpaghetti 1.7 mm 20x400 Gr Carton','2025-01-08 09:40:11','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1203-2',10,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('91b3143d-cd9f-11ef-96a0-2cf05d502fc1','245820102','15 containers said to contain 21600 BOX\r\n15 X 20 CONTAINER\r\n21600 BOX\r\nPHOTOCOPY PAPER','2025-01-08 09:04:29','ad6e193e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','MAT1757',15,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('990c87fe-cd08-11ef-96a0-2cf05d502fc1','APU085012','1 Container Said to Contain 2350 CARTONS','2025-01-07 15:03:47','ad6e193e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1276-2',1,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('9c4bd545-cda8-11ef-96a0-2cf05d502fc1','245530087','3 containers said to contain 5046 CARTONS\r\n5040 CARTONS OF MONOSODIUM GLUTAMATE\r\n6 CARTONS OF SUN UMBRELLAS','2025-01-08 10:09:12','ad6e193e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','MAT1692-3',3,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('9fbf26e7-cd9b-11ef-96a0-2cf05d502fc1','APU097475','2 CONTAINERS SAID TO CONTAIN 5460 CARTONS OF :\r\nCOCOA COATED WAFERS 190532\r\nPROMOTION MATERIALS 660110','2025-01-08 08:36:15','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1288-2',2,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('aa8e3eef-cd0b-11ef-96a0-2cf05d502fc1','QGD1343452','SABA DETERGENT POWDER\r\n30G*150SACHETS /BAG','2025-01-07 15:25:45','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1236-1',7,'a0efe016-c116-11ef-9b34-0efd67b0fe77'),
('aab93c48-cdd9-11ef-96a0-2cf05d502fc1','APU095378','4 containers said to contain 5200 Bag\r\n4x20\' Dry of Butterfly Popcorn\r\nIn 5200 Polypropylene bags of 20 kg\r\n','2025-01-08 16:00:22','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','MAT1785',4,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('afe16447-cd98-11ef-96a0-2cf05d502fc1','246497049','2 containers said to contain 658 CARTONS\r\nOFFICE CHAIR 9401390000   \r\nVISITOR CHAIR  9401719000 ','2025-01-08 08:15:13','ad6e197a-ccf5-11ef-96a0-2cf05d502fc1','d4926b56-ccf5-11ef-96a0-2cf05d502fc1','DC2508',2,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('b127ec36-cda3-11ef-96a0-2cf05d502fc1','246456330','5 containers said to contain 13590 PACKAGE\r\nPASTA','2025-01-08 09:34:00','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1270-2',7,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('bd9777bc-cd0e-11ef-96a0-2cf05d502fc1','DXB0908428A','1x40\'HC FCL CNTR STC\r\nTOTAL: 1900 CARTONS\r\nINSTANT FULL CREAM MILK POWDER\r\nBRAND: MILGRO\r\n(400G X 24 TINS PER CARTON)','2025-01-07 15:47:46','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1305',1,'a0efe016-c116-11ef-9b34-0efd67b0fe77'),
('bf153744-ccfe-11ef-96a0-2cf05d502fc1','APU087814','2 Containers said to contain 5400 Carton\r\n\r\n02X40 HC FT CONTAINER WITH TOTAL 5.400 CARTONS \r\nCONTAINING: CONFECTIONERY ','2025-01-07 13:53:16','ad6e193e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1289-1',2,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('c432bd3f-cdc1-11ef-96a0-2cf05d502fc1','236858682','10 containers said to contain 32500 CARTONS\r\nTIGER SPAGHETTI 200 GR X 40 IN CARTON 1,2 MM','2025-01-08 13:09:17','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1179',10,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('ca84670a-cda1-11ef-96a0-2cf05d502fc1','245864305','2 containers said to contain 1589 PACKAGES\r\nHPMC(CELLOVIS 60M)\r\nESTER ALCOHOL (ALCOTER)\r\nLAB TEST SIEVE\r\nSCREEN\r\nIRON OXIDE RED 130','2025-01-08 09:20:23','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','57ad7ac7-cd91-11ef-96a0-2cf05d502fc1','DC2512-1',2,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('cf04c917-cd11-11ef-96a0-2cf05d502fc1','ANT1837891','PACKAGING MATERIAL','2025-01-07 16:09:43','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','d4926cff-ccf5-11ef-96a0-2cf05d502fc1','DC2501-2',2,'a0efe016-c116-11ef-9b34-0efd67b0fe77'),
('d4f21bf9-cdc0-11ef-96a0-2cf05d502fc1','245718478','4 containers said to contain 56 PACKAGES\r\n\r\nSH-P75 PLATES COMPLETE WAFER LINE','2025-01-08 13:02:35','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','d4926cff-ccf5-11ef-96a0-2cf05d502fc1','DC2294',4,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('d62f74c3-cd93-11ef-96a0-2cf05d502fc1','245460321','1 Container Said to Contain 1730 CARTONS\r\nPOCKET TISSUE','2025-01-08 07:40:30','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','MAT1736',1,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('da492b47-cdbd-11ef-96a0-2cf05d502fc1','245867605','10 containers said to contain 27000 CARTONS\r\nSPAGHETTI\r\n20 UNIT X 500 GR =  10 KGS EACH CARTON','2025-01-08 12:41:16','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1292-1',10,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('ddfeb923-cdc4-11ef-96a0-2cf05d502fc1','P0524MR009','1 VEHICULE(S) NEUF(S)\r\nMAKE: CATERPILLAR\r\nTYPE:EXCAVATOR 330-07 - 120M³\r\nCHASSIS:CAT00330TKEL50101\r\nYEAR:2024\r\nCOD: 2024-274899','2025-01-08 13:31:28','ad6e195e-ccf5-11ef-96a0-2cf05d502fc1','d490e06d-ccf5-11ef-96a0-2cf05d502fc1','DC2485',1,'7e52ceb3-cdc5-11ef-96a0-2cf05d502fc1'),
('de83709e-cdbe-11ef-96a0-2cf05d502fc1','246173077','1 Container Said to Contain 1.830 CARTONS WITH CONFECTIONERY. \r\n\r\n','2025-01-08 12:48:32','ad6e19ef-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','MAT1726-2',1,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('dfefe02b-cd9c-11ef-96a0-2cf05d502fc1','247204567','2 containers said to contain 5400 CARTONS\r\n2X40\' HC CONTAINERS\r\nTOTAL 5400 CARTONS\r\nMAMA STRAWBERRY LOLLIPOPS','2025-01-08 08:45:12','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','MAT1742',2,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('e1950876-cd05-11ef-96a0-2cf05d502fc1','APU096098','1 Container Said to Contain 2060 Box\r\n\r\n01x40 HC CONTAINING 2060\r\nBOXES WITH:\r\nDUCREM DIP GRANULETI\r\nDUCREM DIP BALL','2025-01-07 14:44:21','ad6e193e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1324',1,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('e66e91ac-cd99-11ef-96a0-2cf05d502fc1','APU097470','1  Container Said to Contain 2050 BOXES\r\n253 Golda Cocoa Coated Wafer 15 Grx48x12\r\n628 Golda White Cocoa Coated with Milk Cream Wafer 15 Grx48x6','2025-01-08 08:23:54','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','NAB1312',1,'bc00dda6-c116-11ef-9b34-0efd67b0fe77'),
('f8a168e8-cda7-11ef-96a0-2cf05d502fc1','246318389','2 containers said to contain 20 PACKAGES\r\nDOOR SHEET','2025-01-08 10:04:38','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','d490e1d2-ccf5-11ef-96a0-2cf05d502fc1','DC2526-1',2,'bc00dda6-c116-11ef-9b34-0efd67b0fe77');
/*!40000 ALTER TABLE `bol` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_bol` BEFORE INSERT ON `bol` FOR EACH ROW BEGIN
    SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `type` varchar(32) NOT NULL DEFAULT 'other'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES
('5c5f7e8c-bf74-11ef-9b34-0efd67b0fe77','CMA-CGM','2024-12-21 08:19:55','shipping'),
('427059c0-bf7d-11ef-9b34-0efd67b0fe77','Compnay 2','2024-12-21 09:23:37','other'),
('b1659a00-c399-11ef-9b34-0efd67b0fe77','Transportation','2024-12-26 14:57:14','transportation'),
('20a5ec12-c39a-11ef-9b34-0efd67b0fe77','shipping company','2024-12-26 15:00:21','shipping'),
('a79e94cc-c425-11ef-9b34-0efd67b0fe77','DHL','2024-12-27 07:39:07','transportation'),
('ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','STE INTERNATIONAL BUSINESS ALLIANCE SARLU / IBA','2025-01-07 12:48:21','other'),
('ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','AFRI FOOD SARLU','2025-01-07 12:48:21','other'),
('ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','KIN TRADE BUSINESS SARLU / KTB','2025-01-07 12:48:21','other'),
('ad6e193e-ccf5-11ef-96a0-2cf05d502fc1','STE KONGO COMMERCE GENERAL SARL / KCG','2025-01-07 12:48:21','other'),
('ad6e195e-ccf5-11ef-96a0-2cf05d502fc1','BEST BUILDING COMPANY SARLU / BBC','2025-01-07 12:48:21','other'),
('ad6e197a-ccf5-11ef-96a0-2cf05d502fc1','STYLE DE VIE SARLU / SDV','2025-01-07 12:48:21','other'),
('ad6e1996-ccf5-11ef-96a0-2cf05d502fc1','COMECOM SARL','2025-01-07 12:48:21','other'),
('ad6e19b7-ccf5-11ef-96a0-2cf05d502fc1','STE COMPANY AGRO PASTORAL DU CONGO SARL / CAP CONGO','2025-01-07 12:48:21','other'),
('ad6e19d4-ccf5-11ef-96a0-2cf05d502fc1','ECO-TRANS SARLU','2025-01-07 12:48:21','other'),
('ad6e19ef-ccf5-11ef-96a0-2cf05d502fc1','AFRICA SUPPLY AND TRADING CORPORATION SARLU / ASTRA','2025-01-07 12:48:21','other'),
('ad6e1a0b-ccf5-11ef-96a0-2cf05d502fc1','SOCIETE IMMOBILIERE DE KINSHASA SARLU / SIMMOKIN','2025-01-07 12:48:21','other'),
('ad6e1a27-ccf5-11ef-96a0-2cf05d502fc1','STE INFINTY PRO-COMPANY SARL / IPROCO','2025-01-07 12:48:21','other'),
('ad6e1a43-ccf5-11ef-96a0-2cf05d502fc1','STE IMMOBILIERE CONGOLAISE DE REFERENCE SARL / SICOREF','2025-01-07 12:48:21','other'),
('ad6e1a5e-ccf5-11ef-96a0-2cf05d502fc1','TECHNO METAL INDUSTRIE SARLU / TMI','2025-01-07 12:48:21','other'),
('ad6e1a79-ccf5-11ef-96a0-2cf05d502fc1','INDUSTRIE FORESTIERE DU CONGO SARL / IFCO','2025-01-07 12:48:21','other');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_companies` BEFORE INSERT ON `companies` FOR EACH ROW BEGIN
    SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contacts` (
  `id` varchar(36) NOT NULL,
  `description` varchar(512) NOT NULL,
  `phone_number` varchar(36) NOT NULL,
  `email` varchar(36) NOT NULL,
  `isactive` tinyint(1) NOT NULL,
  `organisation_id` varchar(36) DEFAULT NULL,
  `name` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `organization_id` (`organisation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contacts`
--

LOCK TABLES `contacts` WRITE;
/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
INSERT INTO `contacts` VALUES
('50ba6dae-beb0-11ef-aadb-0efd67b0fe77','This is just a test','76313174','aliossaily24@gmail.com',1,'f0cccc1a-bea1-11ef-aadb-0efd67b0fe77','Ali Osseili'),
('7cb9aa4c-becd-11ef-aadb-0efd67b0fe77','testing after added feature','426243642365','test@email.com',1,'7b447ee6-bea3-11ef-aadb-0efd67b0fe77','TEstEst Test');
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_contacts` BEFORE INSERT ON `contacts` FOR EACH ROW BEGIN
    -- Set the `id` field to a new UUID
    SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `container_routes`
--

DROP TABLE IF EXISTS `container_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `container_routes` (
  `id` varchar(36) NOT NULL,
  `container_id` varchar(36) NOT NULL,
  `transportation_company` varchar(64) NOT NULL,
  `expected_arrival` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `destination` varchar(64) NOT NULL,
  `arrived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`,`arrived`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
 PARTITION BY LIST (`arrived`)
(PARTITION `arrived_0` VALUES IN (0) ENGINE = InnoDB,
 PARTITION `arrived_1` VALUES IN (1) ENGINE = InnoDB);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `container_routes`
--

LOCK TABLES `container_routes` WRITE;
/*!40000 ALTER TABLE `container_routes` DISABLE KEYS */;
/*!40000 ALTER TABLE `container_routes` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_container_routes` BEFORE INSERT ON `container_routes` FOR EACH ROW BEGIN
    -- Generate a UUID and set it for the 'id' column
    SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `containers`
--

DROP TABLE IF EXISTS `containers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `containers` (
  `id` varchar(36) NOT NULL,
  `content` text DEFAULT NULL,
  `weight` double NOT NULL,
  `type` varchar(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `number` varchar(64) NOT NULL,
  `bol_id` varchar(36) NOT NULL,
  `no_embark` char(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `number` (`number`),
  UNIQUE KEY `unique_bol_number` (`bol_id`,`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `containers`
--

LOCK TABLES `containers` WRITE;
/*!40000 ALTER TABLE `containers` DISABLE KEYS */;
INSERT INTO `containers` VALUES
('00cf4207-cdbe-11ef-96a0-2cf05d502fc1','',27675,'20\'','2025-01-08 12:42:20','MSKU3672771','da492b47-cdbd-11ef-96a0-2cf05d502fc1',NULL),
('02c1da7e-cd9d-11ef-96a0-2cf05d502fc1','',28350,'40\'','2025-01-08 08:46:10','MRKU3776687','dfefe02b-cd9c-11ef-96a0-2cf05d502fc1',NULL),
('036dc8a9-cda8-11ef-96a0-2cf05d502fc1','',28000,'20\'','2025-01-08 10:04:56','HASU1403434','f8a168e8-cda7-11ef-96a0-2cf05d502fc1',NULL),
('04bd80c7-cdbe-11ef-96a0-2cf05d502fc1','',27675,'20\'','2025-01-08 12:42:27','MRKU9912461','da492b47-cdbd-11ef-96a0-2cf05d502fc1',NULL),
('0676df62-cd9c-11ef-96a0-2cf05d502fc1','',17845,'40\'','2025-01-08 08:39:07','MRSU3482540','9fbf26e7-cd9b-11ef-96a0-2cf05d502fc1',NULL),
('0798a8b6-cdbe-11ef-96a0-2cf05d502fc1','',27675,'20\'','2025-01-08 12:42:32','MSKU5269430','da492b47-cdbd-11ef-96a0-2cf05d502fc1',NULL),
('0aca6324-cdc1-11ef-96a0-2cf05d502fc1','',5330,'40\'','2025-01-08 13:04:06','MRKU3197772','d4f21bf9-cdc0-11ef-96a0-2cf05d502fc1',NULL),
('0c3b4910-cdbe-11ef-96a0-2cf05d502fc1','',27675,'20\'','2025-01-08 12:42:40','MRKU8224335','da492b47-cdbd-11ef-96a0-2cf05d502fc1',NULL),
('0f383b79-cdbe-11ef-96a0-2cf05d502fc1','',27675,'20\'','2025-01-08 12:42:45','MRKU9460295','da492b47-cdbd-11ef-96a0-2cf05d502fc1',NULL),
('138aa2de-cd11-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 16:04:29','TRHU2681129','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('14ca7d54-cd9c-11ef-96a0-2cf05d502fc1','',18925,'40\'','2025-01-08 08:39:31','MRSU3244373','9fbf26e7-cd9b-11ef-96a0-2cf05d502fc1',NULL),
('180966a0-cd13-11ef-96a0-2cf05d502fc1','',18400,'20\'','2025-01-07 16:18:55','TEMU1056397','00394cdd-cd13-11ef-96a0-2cf05d502fc1',NULL),
('1dc6cf50-cd05-11ef-96a0-2cf05d502fc1','SPAGHETTI',27843.75,'20\'','2025-01-07 14:38:52','TGHU1755821','898fe37b-cd00-11ef-96a0-2cf05d502fc1',NULL),
('207e16d7-cd9f-11ef-96a0-2cf05d502fc1','',24496,'20\'','2025-01-08 09:01:19','TLLU2231957     ','0e50bee7-cd9f-11ef-96a0-2cf05d502fc1',NULL),
('2149aa19-cdca-11ef-96a0-2cf05d502fc1','',19074.961,'20\'','2025-01-08 14:09:09','DFSU3135615','5fba868d-cdc6-11ef-96a0-2cf05d502fc1',NULL),
('2408aa9b-cdc2-11ef-96a0-2cf05d502fc1','',5000,'40\'','2025-01-08 13:11:57','MRSU5089810','12e150de-cdc2-11ef-96a0-2cf05d502fc1',NULL),
('247a010b-cdbf-11ef-96a0-2cf05d502fc1','',24564,'40\'','2025-01-08 12:50:30','TCNU6190808','15213cae-cdbf-11ef-96a0-2cf05d502fc1',NULL),
('25f181b1-cda4-11ef-96a0-2cf05d502fc1','',28160,'20\'','2025-01-08 09:37:16','SUDU1912440','b127ec36-cda3-11ef-96a0-2cf05d502fc1',NULL),
('2a027fa7-cdc2-11ef-96a0-2cf05d502fc1','',4872,'40\'','2025-01-08 13:12:07','MRKU6159457','12e150de-cdc2-11ef-96a0-2cf05d502fc1',NULL),
('2f6822ad-cda4-11ef-96a0-2cf05d502fc1','',27760,'20\'','2025-01-08 09:37:32','MSKU5610742','b127ec36-cda3-11ef-96a0-2cf05d502fc1',NULL),
('3117f5de-cd06-11ef-96a0-2cf05d502fc1','',14914.4,'40\'','2025-01-07 14:46:34','HASU4688351','e1950876-cd05-11ef-96a0-2cf05d502fc1',NULL),
('31715c59-cda2-11ef-96a0-2cf05d502fc1','',24268.8,'20\'','2025-01-08 09:23:16','MRKU8072090','19c65bfd-cda2-11ef-96a0-2cf05d502fc1',NULL),
('31b51136-cdc2-11ef-96a0-2cf05d502fc1','',5000,'40\'','2025-01-08 13:12:20','MRSU4199920','12e150de-cdc2-11ef-96a0-2cf05d502fc1',NULL),
('34d57bab-cda2-11ef-96a0-2cf05d502fc1','',24268.8,'20\'','2025-01-08 09:23:22','MRKU7457163','19c65bfd-cda2-11ef-96a0-2cf05d502fc1',NULL),
('35487e6a-cda4-11ef-96a0-2cf05d502fc1','',27860,'20\'','2025-01-08 09:37:41','TLLU2303229','b127ec36-cda3-11ef-96a0-2cf05d502fc1',NULL),
('38cf36c5-cda2-11ef-96a0-2cf05d502fc1','',24268.8,'20\'','2025-01-08 09:23:28','MRKU8778255','19c65bfd-cda2-11ef-96a0-2cf05d502fc1',NULL),
('3c8cf120-cd0f-11ef-96a0-2cf05d502fc1','',23921,'40\'','2025-01-07 15:51:19','SEKU5773901','bd9777bc-cd0e-11ef-96a0-2cf05d502fc1',NULL),
('3ce6538c-cda2-11ef-96a0-2cf05d502fc1','',24268.8,'20\'','2025-01-08 09:23:35','MRKU9820540','19c65bfd-cda2-11ef-96a0-2cf05d502fc1',NULL),
('3f1bbc8d-cd00-11ef-96a0-2cf05d502fc1','',27000,'40','2025-01-07 14:04:01','MRSU5902241','bf153744-ccfe-11ef-96a0-2cf05d502fc1',NULL),
('3fe731c4-cda4-11ef-96a0-2cf05d502fc1','',28080,'20\'','2025-01-08 09:37:59','MRKU8081980','b127ec36-cda3-11ef-96a0-2cf05d502fc1',NULL),
('3ff07c88-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 15:58:34','TGHU1785301','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('410baebd-cfff-11ef-96a0-2cf05d502fc1','',10000,'40\'','2025-01-11 09:34:28','MRKU3755616','283dca50-cfff-11ef-96a0-2cf05d502fc1',NULL),
('429ff4b8-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 15:58:38','TEMU0920175','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('45798d44-cd9b-11ef-96a0-2cf05d502fc1','',24470,'40\'','2025-01-08 08:33:43','TCKU6780862','35731d22-cd9b-11ef-96a0-2cf05d502fc1',NULL),
('463a2974-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 15:58:44','GESU1276580','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('49c39b99-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 15:58:50','TCLU3795470','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('4a22f4a8-cd0d-11ef-96a0-2cf05d502fc1','',2730,'20\'','2025-01-07 15:37:22','TCKU1056962','3c956aa4-cd0d-11ef-96a0-2cf05d502fc1',NULL),
('4a9d1c95-cfff-11ef-96a0-2cf05d502fc1','',8380,'40\'','2025-01-11 09:34:44','SUDU8889357','283dca50-cfff-11ef-96a0-2cf05d502fc1',NULL),
('4c21b246-cda8-11ef-96a0-2cf05d502fc1','',27108,'20\'','2025-01-08 10:06:58','MSKU5115245','381ec88d-cda8-11ef-96a0-2cf05d502fc1',NULL),
('4c6bd02f-cdc1-11ef-96a0-2cf05d502fc1','',26163,'40\'','2025-01-08 13:05:56','MRSU6336690','409274d9-cdc1-11ef-96a0-2cf05d502fc1',NULL),
('4c8797f1-cd00-11ef-96a0-2cf05d502fc1','',27000,'40','2025-01-07 14:04:23','MRSU5440267','bf153744-ccfe-11ef-96a0-2cf05d502fc1',NULL),
('4e35b7c7-cd11-11ef-96a0-2cf05d502fc1','',15635,'20\'','2025-01-07 16:06:07','CSOU1255885','3f6484b4-cd11-11ef-96a0-2cf05d502fc1',NULL),
('4e60b775-cd9b-11ef-96a0-2cf05d502fc1','',24470,'40\'','2025-01-08 08:33:58','MSKU9762316','35731d22-cd9b-11ef-96a0-2cf05d502fc1',NULL),
('4e9d4441-cd9d-11ef-96a0-2cf05d502fc1','',28080,'40\'','2025-01-08 08:48:18','MRSU4824592','3fd2496d-cd9d-11ef-96a0-2cf05d502fc1',NULL),
('4f14ba18-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 15:58:59','APZU2111492','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('53c3f99b-cfff-11ef-96a0-2cf05d502fc1','',10180,'40\'','2025-01-11 09:34:59','SUDU8873725','283dca50-cfff-11ef-96a0-2cf05d502fc1',NULL),
('54762f29-cd9b-11ef-96a0-2cf05d502fc1','',24470,'40\'','2025-01-08 08:34:08','CAAU7219052','35731d22-cd9b-11ef-96a0-2cf05d502fc1',NULL),
('55657122-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 15:59:10','APZU3743776','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('5771601f-cd11-11ef-96a0-2cf05d502fc1','',15635,'20\'','2025-01-07 16:06:23','FCIU2148065','3f6484b4-cd11-11ef-96a0-2cf05d502fc1',NULL),
('58bdea7e-cd0c-11ef-96a0-2cf05d502fc1','',28000,'40\'','2025-01-07 15:30:37','TEMU7450617','aa8e3eef-cd0b-11ef-96a0-2cf05d502fc1',NULL),
('58e0ef6d-cda6-11ef-96a0-2cf05d502fc1','',20085,'20\'','2025-01-08 09:53:00','MRKU6928103','4c56a566-cda6-11ef-96a0-2cf05d502fc1',NULL),
('592e418c-cd05-11ef-96a0-2cf05d502fc1','',27843.75,'20\'','2025-01-07 14:40:32','TLLU2198592','898fe37b-cd00-11ef-96a0-2cf05d502fc1',NULL),
('59512af6-cd9b-11ef-96a0-2cf05d502fc1','',24470,'40\'','2025-01-08 08:34:16','CAAU8600192','35731d22-cd9b-11ef-96a0-2cf05d502fc1',NULL),
('59c403dc-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 15:59:17','GESU1214167','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('5af1b29e-cd9d-11ef-96a0-2cf05d502fc1','',28080,'40\'','2025-01-08 08:48:38','MRSU5313030','3fd2496d-cd9d-11ef-96a0-2cf05d502fc1',NULL),
('5b7f15df-cfff-11ef-96a0-2cf05d502fc1','',9680,'40\'','2025-01-11 09:35:12','TCKU7170191','283dca50-cfff-11ef-96a0-2cf05d502fc1',NULL),
('5bb7b160-cd11-11ef-96a0-2cf05d502fc1','',15635,'20\'','2025-01-07 16:06:30','APZU3260377','3f6484b4-cd11-11ef-96a0-2cf05d502fc1',NULL),
('5cf45751-cda6-11ef-96a0-2cf05d502fc1','',20085,'20\'','2025-01-08 09:53:07','MRKU7457013','4c56a566-cda6-11ef-96a0-2cf05d502fc1',NULL),
('5d896c12-cd0c-11ef-96a0-2cf05d502fc1','',28000,'40\'','2025-01-07 15:30:46','BHCU5021951','aa8e3eef-cd0b-11ef-96a0-2cf05d502fc1','B'),
('5ee9131b-cd9b-11ef-96a0-2cf05d502fc1','',24470,'40\'','2025-01-08 08:34:26','MRKU3989661','35731d22-cd9b-11ef-96a0-2cf05d502fc1',NULL),
('5f7de27b-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 15:59:27','CMAU1883922','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('5f95bc82-cd11-11ef-96a0-2cf05d502fc1','',15635,'20\'','2025-01-07 16:06:36','CMAU0739103','3f6484b4-cd11-11ef-96a0-2cf05d502fc1',NULL),
('606cdd93-cda6-11ef-96a0-2cf05d502fc1','',20085,'20\'','2025-01-08 09:53:13','MRKU7295252','4c56a566-cda6-11ef-96a0-2cf05d502fc1',NULL),
('60e93347-cd05-11ef-96a0-2cf05d502fc1','',27843.75,'20\'','2025-01-07 14:40:45','MSKU7373228','898fe37b-cd00-11ef-96a0-2cf05d502fc1',NULL),
('6326cd9f-cd0c-11ef-96a0-2cf05d502fc1','',28000,'40\'','2025-01-07 15:30:55','SEKU6092667','aa8e3eef-cd0b-11ef-96a0-2cf05d502fc1',NULL),
('637326f8-cd11-11ef-96a0-2cf05d502fc1','',15635,'20\'','2025-01-07 16:06:43','TCKU3932540','3f6484b4-cd11-11ef-96a0-2cf05d502fc1',NULL),
('63761ef4-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 15:59:33','TCLU7434157','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('643bedaa-cda6-11ef-96a0-2cf05d502fc1','',20085,'20\'','2025-01-08 09:53:19','MRKU8540117','4c56a566-cda6-11ef-96a0-2cf05d502fc1',NULL),
('65a61f86-cdbf-11ef-96a0-2cf05d502fc1','',24564,'40\'','2025-01-08 12:52:19','GCXU6238935','4bef52a0-cdbf-11ef-96a0-2cf05d502fc1',NULL),
('65e678db-cfff-11ef-96a0-2cf05d502fc1','',8480,'40\'','2025-01-11 09:35:30','MRKU3362917','283dca50-cfff-11ef-96a0-2cf05d502fc1',NULL),
('66ee60f9-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 15:59:39','TCKU1748533','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('681e1084-cd05-11ef-96a0-2cf05d502fc1','',27843.75,'20\'','2025-01-07 14:40:57','MSKU5199713','898fe37b-cd00-11ef-96a0-2cf05d502fc1',NULL),
('68b39227-cda6-11ef-96a0-2cf05d502fc1','',20085,'20\'','2025-01-08 09:53:27','MSKU4272933','4c56a566-cda6-11ef-96a0-2cf05d502fc1',NULL),
('6a845a62-cd0b-11ef-96a0-2cf05d502fc1','',24764,'40\'','2025-01-07 15:23:58','MEDU4498677','51e90e08-cd0b-11ef-96a0-2cf05d502fc1',NULL),
('6b6e36e1-cd9f-11ef-96a0-2cf05d502fc1','',26405,'20\'','2025-01-08 09:03:25','MRKU6762619','5fb2a13c-cd9f-11ef-96a0-2cf05d502fc1',NULL),
('6b6f9fa6-cd9e-11ef-96a0-2cf05d502fc1','',24075,'20\'','2025-01-08 08:56:15','TIIU2161380','27f44aaa-cd9e-11ef-96a0-2cf05d502fc1',NULL),
('6be20224-cfff-11ef-96a0-2cf05d502fc1','',8380,'40\'','2025-01-11 09:35:40','CAXU8101731','283dca50-cfff-11ef-96a0-2cf05d502fc1',NULL),
('6be76bc9-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 15:59:48','GLDU5697938','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('6dcd6a91-cda6-11ef-96a0-2cf05d502fc1','',20085,'20\'','2025-01-08 09:53:35','MSKU7132088','4c56a566-cda6-11ef-96a0-2cf05d502fc1',NULL),
('6ed0a8ed-cd0c-11ef-96a0-2cf05d502fc1','',28000,'40\'','2025-01-07 15:31:15','TCLU8932025','aa8e3eef-cd0b-11ef-96a0-2cf05d502fc1',NULL),
('6f01c9cf-cda5-11ef-96a0-2cf05d502fc1','',27700,'20\'','2025-01-08 09:46:28','SUDU7761079','4752f3e7-cda5-11ef-96a0-2cf05d502fc1',NULL),
('70484a91-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 15:59:55','APZU2134570','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('7294ee2e-cda6-11ef-96a0-2cf05d502fc1','',20085,'20\'','2025-01-08 09:53:43','TEMU2319754','4c56a566-cda6-11ef-96a0-2cf05d502fc1',NULL),
('72f54f14-cfff-11ef-96a0-2cf05d502fc1','',10180,'40\'','2025-01-11 09:35:51','TCNU7195532','283dca50-cfff-11ef-96a0-2cf05d502fc1',NULL),
('7427fcfd-cd05-11ef-96a0-2cf05d502fc1','',27843.75,'20\'','2025-01-07 14:41:17','HASU1081092','898fe37b-cd00-11ef-96a0-2cf05d502fc1',NULL),
('78dbfe14-cda5-11ef-96a0-2cf05d502fc1','',27660,'20\'','2025-01-08 09:46:44','MSKU4175337','4752f3e7-cda5-11ef-96a0-2cf05d502fc1',NULL),
('7a011e5d-cfff-11ef-96a0-2cf05d502fc1','',10000,'40\'','2025-01-11 09:36:03','MSKU0807873','283dca50-cfff-11ef-96a0-2cf05d502fc1',NULL),
('7ae446b9-cda2-11ef-96a0-2cf05d502fc1','',11250,'40\'','2025-01-08 09:25:19','CAAU9231801','6900f175-cda2-11ef-96a0-2cf05d502fc1',NULL),
('7d490767-cd05-11ef-96a0-2cf05d502fc1','',27843.75,'20\'','2025-01-07 14:41:32','TIIU2356464','898fe37b-cd00-11ef-96a0-2cf05d502fc1',NULL),
('7d768f2f-cd07-11ef-96a0-2cf05d502fc1','',25435.76,'40\'','2025-01-07 14:55:52','MRSU5916841','534d20d7-cd07-11ef-96a0-2cf05d502fc1',NULL),
('7ef21110-cda5-11ef-96a0-2cf05d502fc1','',27680,'20\'','2025-01-08 09:46:55','MRKU9310206','4752f3e7-cda5-11ef-96a0-2cf05d502fc1',NULL),
('7f903722-cd0a-11ef-96a0-2cf05d502fc1','',27200,'20\'','2025-01-07 15:17:24','TGHU0963266','330b6888-cd0a-11ef-96a0-2cf05d502fc1',NULL),
('813f5fdd-cfff-11ef-96a0-2cf05d502fc1','',10180,'40\'','2025-01-11 09:36:15','MSKU1969631','283dca50-cfff-11ef-96a0-2cf05d502fc1',NULL),
('8507fefa-cda5-11ef-96a0-2cf05d502fc1','',27640,'20\'','2025-01-08 09:47:05','MRKU7333100','4752f3e7-cda5-11ef-96a0-2cf05d502fc1',NULL),
('8544d71e-cd05-11ef-96a0-2cf05d502fc1','',27843.75,'20\'','2025-01-07 14:41:46','MRKU9063636','898fe37b-cd00-11ef-96a0-2cf05d502fc1',NULL),
('889a5787-cd0e-11ef-96a0-2cf05d502fc1','',26785,'40\'','2025-01-07 15:46:17','CMAU7391534','7d47b035-cd0e-11ef-96a0-2cf05d502fc1',NULL),
('88be9cad-cd12-11ef-96a0-2cf05d502fc1','',8950,'40\' Open Top','2025-01-07 16:14:55','HLBU8071448','651f428d-cd12-11ef-96a0-2cf05d502fc1',NULL),
('8b9f1a09-cda5-11ef-96a0-2cf05d502fc1','',27420,'20\'','2025-01-08 09:47:16','MRSU0210404','4752f3e7-cda5-11ef-96a0-2cf05d502fc1',NULL),
('8f1bbea2-cdc1-11ef-96a0-2cf05d502fc1','',12980,'40\' Flat Rack','2025-01-08 13:07:48','MAEU3413453','7c71fbff-cdc1-11ef-96a0-2cf05d502fc1',NULL),
('8ff36521-cd09-11ef-96a0-2cf05d502fc1','',26100,'40\' Reefer','2025-01-07 15:10:42','HLBU9680710','7c0c9b29-cd09-11ef-96a0-2cf05d502fc1',NULL),
('9066b3cc-cd0f-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 15:53:39','TGHU0972782','85985879-cd0f-11ef-96a0-2cf05d502fc1',NULL),
('90c2069a-cd0a-11ef-96a0-2cf05d502fc1','',27200,'20\'','2025-01-07 15:17:52','SEGU3582178','330b6888-cd0a-11ef-96a0-2cf05d502fc1',NULL),
('9127200b-cdc5-11ef-96a0-2cf05d502fc1','',29600,'Conventionnel','2025-01-08 13:36:29','CAT00330TKEL50101','ddfeb923-cdc4-11ef-96a0-2cf05d502fc1',NULL),
('9580c111-cd0d-11ef-96a0-2cf05d502fc1','',23921,'40\'','2025-01-07 15:39:29','TLLU8657071','75a48e36-cd0d-11ef-96a0-2cf05d502fc1',NULL),
('9780da10-cdc1-11ef-96a0-2cf05d502fc1','',17180,'40\' Flat Rack','2025-01-08 13:08:02','MAEU3422671','7c71fbff-cdc1-11ef-96a0-2cf05d502fc1',NULL),
('9a782d26-cdbf-11ef-96a0-2cf05d502fc1','',24564,'40\'','2025-01-08 12:53:48','SELU4107127','8ab36ed8-cdbf-11ef-96a0-2cf05d502fc1',NULL),
('9b791530-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 16:01:07','TCKU3338461','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('9b91fa03-cd0a-11ef-96a0-2cf05d502fc1','',27200,'20\'','2025-01-07 15:18:11','HLBU3647823','330b6888-cd0a-11ef-96a0-2cf05d502fc1',NULL),
('9b9b5ad5-cd0c-11ef-96a0-2cf05d502fc1','',28000,'40\'','2025-01-07 15:32:30','CMAU4687271','aa8e3eef-cd0b-11ef-96a0-2cf05d502fc1',NULL),
('9cbef022-cdbe-11ef-96a0-2cf05d502fc1','',4180,'40\'','2025-01-08 12:46:42','GCXU6340145','8d97853f-cdbe-11ef-96a0-2cf05d502fc1',NULL),
('9dfd069c-cdc1-11ef-96a0-2cf05d502fc1','',17180,'40\' Flat Rack','2025-01-08 13:08:13','MAEU3430640','7c71fbff-cdc1-11ef-96a0-2cf05d502fc1',NULL),
('a08d1ad4-cd9f-11ef-96a0-2cf05d502fc1','',18599.04,'20\'','2025-01-08 09:04:54','TEMU2258920','91b3143d-cd9f-11ef-96a0-2cf05d502fc1',NULL),
('a0e59086-cd0c-11ef-96a0-2cf05d502fc1','',28000,'40\'','2025-01-07 15:32:39','CMAU8823334','aa8e3eef-cd0b-11ef-96a0-2cf05d502fc1',NULL),
('a286a0d4-cdbe-11ef-96a0-2cf05d502fc1','',4060,'40\'','2025-01-08 12:46:52','MRKU2258533','8d97853f-cdbe-11ef-96a0-2cf05d502fc1',NULL),
('a2e7eeea-cd0a-11ef-96a0-2cf05d502fc1','',27200,'20\'','2025-01-07 15:18:23','FANU1330929','330b6888-cd0a-11ef-96a0-2cf05d502fc1',NULL),
('a59263f4-cd9f-11ef-96a0-2cf05d502fc1','',18599.04,'20\'','2025-01-08 09:05:02','MRKU8290070','91b3143d-cd9f-11ef-96a0-2cf05d502fc1',NULL),
('a6f4a9ec-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 16:01:27','TCLU3969952','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('a7bd2e17-cd13-11ef-96a0-2cf05d502fc1','',27000,'20\'','2025-01-07 16:22:56','HLBU3042241','62a5cda2-cd13-11ef-96a0-2cf05d502fc1',NULL),
('a9818ecf-cd0f-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 15:54:21','BEAU2229930','85985879-cd0f-11ef-96a0-2cf05d502fc1',NULL),
('aa340052-cda8-11ef-96a0-2cf05d502fc1','',25404.4,'40\'','2025-01-08 10:09:36','GCXU5756293','9c4bd545-cda8-11ef-96a0-2cf05d502fc1',NULL),
('aad25c4a-cd9f-11ef-96a0-2cf05d502fc1','',18599.04,'20\'','2025-01-08 09:05:11','BSIU2961302','91b3143d-cd9f-11ef-96a0-2cf05d502fc1',NULL),
('ad62b3fe-cda4-11ef-96a0-2cf05d502fc1','',28046.25,'20\'','2025-01-08 09:41:03','MRKU9437398','8e940cb0-cda4-11ef-96a0-2cf05d502fc1',NULL),
('ae674cc8-cd9f-11ef-96a0-2cf05d502fc1','',18599.04,'20\'','2025-01-08 09:05:17','MSKU4056370','91b3143d-cd9f-11ef-96a0-2cf05d502fc1',NULL),
('ae889f3d-cd07-11ef-96a0-2cf05d502fc1','',25435.76,'40\'','2025-01-07 14:57:14','HASU5002300','534d20d7-cd07-11ef-96a0-2cf05d502fc1',NULL),
('af94d7b6-cda8-11ef-96a0-2cf05d502fc1','',25404.4,'40\'','2025-01-08 10:09:45','MRKU2780419','9c4bd545-cda8-11ef-96a0-2cf05d502fc1',NULL),
('b1cd19a8-cd9f-11ef-96a0-2cf05d502fc1','',18599.04,'20\'','2025-01-08 09:05:23','MSKU7613186','91b3143d-cd9f-11ef-96a0-2cf05d502fc1',NULL),
('b1f5ae10-cda4-11ef-96a0-2cf05d502fc1','',28046.25,'20\'','2025-01-08 09:41:11','MRKU8323370','8e940cb0-cda4-11ef-96a0-2cf05d502fc1',NULL),
('b4adfb87-cdd9-11ef-96a0-2cf05d502fc1','',26100,'20\'','2025-01-08 16:00:39','MRKU6705630','aab93c48-cdd9-11ef-96a0-2cf05d502fc1',NULL),
('b4f2fc05-cd9f-11ef-96a0-2cf05d502fc1','',18599.04,'20\'','2025-01-08 09:05:28','MSKU5969326','91b3143d-cd9f-11ef-96a0-2cf05d502fc1',NULL),
('b4f9c380-cda8-11ef-96a0-2cf05d502fc1','',25404.4,'40\'','2025-01-08 10:09:54','TCKU7381234','9c4bd545-cda8-11ef-96a0-2cf05d502fc1',NULL),
('b53fffde-cdc9-11ef-96a0-2cf05d502fc1','',22172.46,'20\'','2025-01-08 14:06:08','CMAU3051686','5fba868d-cdc6-11ef-96a0-2cf05d502fc1',NULL),
('b6536207-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 16:01:52','SEGU1015030','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('b74a172b-cd04-11ef-96a0-2cf05d502fc1','',27843.75,'20\'','2025-01-07 14:36:00','TEMU0699357','898fe37b-cd00-11ef-96a0-2cf05d502fc1',NULL),
('b7afa645-cd9a-11ef-96a0-2cf05d502fc1','',23100,'40\'','2025-01-08 08:29:45','MRKU4342427','3da00aee-cd9a-11ef-96a0-2cf05d502fc1',NULL),
('b7badf89-cda4-11ef-96a0-2cf05d502fc1','',28046.25,'20\'','2025-01-08 09:41:20','MSKU4114310','8e940cb0-cda4-11ef-96a0-2cf05d502fc1',NULL),
('b84ba468-cdd9-11ef-96a0-2cf05d502fc1','',26100,'20\'','2025-01-08 16:00:45','MSKU7619081','aab93c48-cdd9-11ef-96a0-2cf05d502fc1',NULL),
('b859e759-cd9f-11ef-96a0-2cf05d502fc1','',18599.04,'20\'','2025-01-08 09:05:34','SUDU7596903','91b3143d-cd9f-11ef-96a0-2cf05d502fc1',NULL),
('b9fb4833-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 16:01:59','TCLU3822540','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('ba1a0f39-cd08-11ef-96a0-2cf05d502fc1','',27748.8,'40\'','2025-01-07 15:04:43','MRKU5754990','990c87fe-cd08-11ef-96a0-2cf05d502fc1',NULL),
('bd58e392-cdd9-11ef-96a0-2cf05d502fc1','',26100,'20\'','2025-01-08 16:00:53','MRKU7738371','aab93c48-cdd9-11ef-96a0-2cf05d502fc1',NULL),
('bd5e605c-cd04-11ef-96a0-2cf05d502fc1','',27843.75,'20\'','2025-01-07 14:36:10','SUDU7497222','898fe37b-cd00-11ef-96a0-2cf05d502fc1',NULL),
('bd66df4a-cda4-11ef-96a0-2cf05d502fc1','',28046.25,'20\'','2025-01-08 09:41:30','MRKU6535739','8e940cb0-cda4-11ef-96a0-2cf05d502fc1',NULL),
('c056ab1b-cd98-11ef-96a0-2cf05d502fc1','',8720,'40\'','2025-01-08 08:15:41','SUDU8913664','afe16447-cd98-11ef-96a0-2cf05d502fc1',NULL),
('c1ecc090-cdd9-11ef-96a0-2cf05d502fc1','',26100,'20\'','2025-01-08 16:01:01','MSKU5977450','aab93c48-cdd9-11ef-96a0-2cf05d502fc1',NULL),
('c277d745-cda4-11ef-96a0-2cf05d502fc1','',28046.25,'20\'','2025-01-08 09:41:38','MRKU9859710','8e940cb0-cda4-11ef-96a0-2cf05d502fc1',NULL),
('c3a4632f-cd9f-11ef-96a0-2cf05d502fc1','',18599.04,'20\'','2025-01-08 09:05:53','MRKU7893470','91b3143d-cd9f-11ef-96a0-2cf05d502fc1',NULL),
('c72e2274-cd9f-11ef-96a0-2cf05d502fc1','',18599.04,'20\'','2025-01-08 09:05:59','MRSU0163447','91b3143d-cd9f-11ef-96a0-2cf05d502fc1',NULL),
('c75e0d4d-cda3-11ef-96a0-2cf05d502fc1','',28120,'20\'','2025-01-08 09:34:37','MSKU4281241','b127ec36-cda3-11ef-96a0-2cf05d502fc1',NULL),
('cf25f3e9-cd96-11ef-96a0-2cf05d502fc1','',9590,'40\'','2025-01-08 08:01:47','TCKU7229134','d62f74c3-cd93-11ef-96a0-2cf05d502fc1',NULL),
('cf29e401-cd98-11ef-96a0-2cf05d502fc1','',11750,'40\'','2025-01-08 08:16:06','MRSU6975900','afe16447-cd98-11ef-96a0-2cf05d502fc1',NULL),
('d281159f-cdc1-11ef-96a0-2cf05d502fc1','',26975,'20\'','2025-01-08 13:09:41','GESU3353109','c432bd3f-cdc1-11ef-96a0-2cf05d502fc1',NULL),
('d286cfb1-cd0f-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 15:55:30','TCLU7193912','85985879-cd0f-11ef-96a0-2cf05d502fc1',NULL),
('d4902a63-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 16:02:43','APZU3766072','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('d49f7d8c-cd9f-11ef-96a0-2cf05d502fc1','',18599.04,'20\'','2025-01-08 09:06:21','MRKU8548904','91b3143d-cd9f-11ef-96a0-2cf05d502fc1',NULL),
('d50e8961-cda4-11ef-96a0-2cf05d502fc1','',28046.25,'20\'','2025-01-08 09:42:10','CAAU2409363','8e940cb0-cda4-11ef-96a0-2cf05d502fc1',NULL),
('d60107b9-cdc1-11ef-96a0-2cf05d502fc1','',26975,'20\'','2025-01-08 13:09:47','HASU1365041','c432bd3f-cdc1-11ef-96a0-2cf05d502fc1',NULL),
('d713edb2-cd0f-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 15:55:38','CAIU6033976','85985879-cd0f-11ef-96a0-2cf05d502fc1',NULL),
('d8dd9e7b-cd04-11ef-96a0-2cf05d502fc1','',27843.75,'20\'','2025-01-07 14:36:56','HASU1113097','898fe37b-cd00-11ef-96a0-2cf05d502fc1',NULL),
('d8fe9ab3-cdc1-11ef-96a0-2cf05d502fc1','',26975,'20\'','2025-01-08 13:09:52','TCKU3374725','c432bd3f-cdc1-11ef-96a0-2cf05d502fc1',NULL),
('d9030c8b-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 16:02:51','CRSU1435730','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('d982e83e-cda1-11ef-96a0-2cf05d502fc1','',26608,'20\'','2025-01-08 09:20:49','TCKU1999919','ca84670a-cda1-11ef-96a0-2cf05d502fc1',NULL),
('d9a60ca7-cda4-11ef-96a0-2cf05d502fc1','',28046.25,'20\'','2025-01-08 09:42:17','TCLU6901278','8e940cb0-cda4-11ef-96a0-2cf05d502fc1',NULL),
('dc7f8c08-cdc1-11ef-96a0-2cf05d502fc1','',26975,'20\'','2025-01-08 13:09:57','MRKU8787961','c432bd3f-cdc1-11ef-96a0-2cf05d502fc1',NULL),
('dc8a17f0-cd10-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 16:02:57','TCLU7422691','2d6e4860-cd10-11ef-96a0-2cf05d502fc1',NULL),
('def2366c-cd0f-11ef-96a0-2cf05d502fc1','',21876.8,'20\'','2025-01-07 15:55:51','FCIU3420769','85985879-cd0f-11ef-96a0-2cf05d502fc1',NULL),
('dfa7b106-cdc1-11ef-96a0-2cf05d502fc1','',26975,'20\'','2025-01-08 13:10:03','MRKU7395204','c432bd3f-cdc1-11ef-96a0-2cf05d502fc1',NULL),
('e07d96d0-cda4-11ef-96a0-2cf05d502fc1','',28046.25,'20\'','2025-01-08 09:42:29','MRKU7762085','8e940cb0-cda4-11ef-96a0-2cf05d502fc1',NULL),
('e0b8274f-cda0-11ef-96a0-2cf05d502fc1','',18599.04,'20\'','2025-01-08 09:13:51','MRKU7700885','91b3143d-cd9f-11ef-96a0-2cf05d502fc1',NULL),
('e228e6df-cdc0-11ef-96a0-2cf05d502fc1','',9800,'40\'','2025-01-08 13:02:57','INKU6203239','d4f21bf9-cdc0-11ef-96a0-2cf05d502fc1',NULL),
('e38759d4-cdc1-11ef-96a0-2cf05d502fc1','',26975,'20\'','2025-01-08 13:10:09','TEMU3929392','c432bd3f-cdc1-11ef-96a0-2cf05d502fc1',NULL),
('e5bc6a6b-cda1-11ef-96a0-2cf05d502fc1','',26737,'40\'','2025-01-08 09:21:09','TLLU4702470','ca84670a-cda1-11ef-96a0-2cf05d502fc1',NULL),
('e607a654-cda4-11ef-96a0-2cf05d502fc1','',28046.25,'20\'','2025-01-08 09:42:38','MSKU7947286','8e940cb0-cda4-11ef-96a0-2cf05d502fc1',NULL),
('e853c62a-cdc1-11ef-96a0-2cf05d502fc1','',26975,'20\'','2025-01-08 13:10:17','TCLU6187279','c432bd3f-cdc1-11ef-96a0-2cf05d502fc1',NULL),
('e8af15ea-cd9f-11ef-96a0-2cf05d502fc1','',18599.04,'20\'','2025-01-08 09:06:55','MSKU5095489','91b3143d-cd9f-11ef-96a0-2cf05d502fc1',NULL),
('ea1f5bee-cdbe-11ef-96a0-2cf05d502fc1','',26077.514,'40\'','2025-01-08 12:48:52','CAAU8264863 ','de83709e-cdbe-11ef-96a0-2cf05d502fc1',NULL),
('eb070122-cd9c-11ef-96a0-2cf05d502fc1','',28350,'40\'','2025-01-08 08:45:30','CIPU5250479','dfefe02b-cd9c-11ef-96a0-2cf05d502fc1',NULL),
('eb4b3bd1-cdc1-11ef-96a0-2cf05d502fc1','',26975,'20\'','2025-01-08 13:10:22','MSKU7672569','c432bd3f-cdc1-11ef-96a0-2cf05d502fc1',NULL),
('ebcf260d-cda4-11ef-96a0-2cf05d502fc1','',28046.25,'20\'','2025-01-08 09:42:48','SUDU1446784','8e940cb0-cda4-11ef-96a0-2cf05d502fc1',NULL),
('ec921803-cd0b-11ef-96a0-2cf05d502fc1','',28000,'40\'','2025-01-07 15:27:36','BEAU4744055','aa8e3eef-cd0b-11ef-96a0-2cf05d502fc1','B'),
('ed056d3e-cdbd-11ef-96a0-2cf05d502fc1','',27675,'20\'','2025-01-08 12:41:47','MRKU7033653','da492b47-cdbd-11ef-96a0-2cf05d502fc1',NULL),
('ed3a52fc-cd97-11ef-96a0-2cf05d502fc1','',22581,'40\'','2025-01-08 08:09:47','FFAU4799161','23850897-cd97-11ef-96a0-2cf05d502fc1',NULL),
('ef482fbd-cd9f-11ef-96a0-2cf05d502fc1','',18599.04,'20\'','2025-01-08 09:07:06','MSKU4283732','91b3143d-cd9f-11ef-96a0-2cf05d502fc1',NULL),
('f00d62ab-cdc1-11ef-96a0-2cf05d502fc1','',26975,'20\'','2025-01-08 13:10:30','TCKU1119206','c432bd3f-cdc1-11ef-96a0-2cf05d502fc1',NULL),
('f13f7123-cdc0-11ef-96a0-2cf05d502fc1','',16820,'40\'','2025-01-08 13:03:23','TRHU4585042','d4f21bf9-cdc0-11ef-96a0-2cf05d502fc1',NULL),
('f32f8594-cdc1-11ef-96a0-2cf05d502fc1','',26975,'20\'','2025-01-08 13:10:36','MSKU3106584','c432bd3f-cdc1-11ef-96a0-2cf05d502fc1',NULL),
('f3e2c912-cdbd-11ef-96a0-2cf05d502fc1','',27675,'20\'','2025-01-08 12:41:59','MSKU4272595','da492b47-cdbd-11ef-96a0-2cf05d502fc1',NULL),
('f3f86914-cd99-11ef-96a0-2cf05d502fc1','',17420,'40\'','2025-01-08 08:24:17','GCXU6272436','e66e91ac-cd99-11ef-96a0-2cf05d502fc1',NULL),
('f4cedfeb-cd97-11ef-96a0-2cf05d502fc1','',22653,'40\'','2025-01-08 08:09:59','MSKU1030350','23850897-cd97-11ef-96a0-2cf05d502fc1',NULL),
('f72e41bc-cdbd-11ef-96a0-2cf05d502fc1','',27675,'20\'','2025-01-08 12:42:04','TEMU2081970','da492b47-cdbd-11ef-96a0-2cf05d502fc1',NULL),
('fa4301fb-cd9f-11ef-96a0-2cf05d502fc1','',18599.04,'20\'','2025-01-08 09:07:24','MRKU7395992','91b3143d-cd9f-11ef-96a0-2cf05d502fc1',NULL),
('fa4c66b9-cdbd-11ef-96a0-2cf05d502fc1','',27675,'20\'','2025-01-08 12:42:09','TGHU3994255','da492b47-cdbd-11ef-96a0-2cf05d502fc1',NULL),
('fdc80d0e-cd07-11ef-96a0-2cf05d502fc1','',25435.76,'40\'','2025-01-07 14:59:27','MRKU5871702','534d20d7-cd07-11ef-96a0-2cf05d502fc1',NULL),
('fe0bf875-cdbd-11ef-96a0-2cf05d502fc1','',27675,'20\'','2025-01-08 12:42:16','MRSU0358808','da492b47-cdbd-11ef-96a0-2cf05d502fc1',NULL),
('fe5f426f-cd9f-11ef-96a0-2cf05d502fc1','',18599.04,'20\'','2025-01-08 09:07:31','MRKU8339118','91b3143d-cd9f-11ef-96a0-2cf05d502fc1',NULL),
('fe7cc1fe-cdc0-11ef-96a0-2cf05d502fc1','',2390,'20\'','2025-01-08 13:03:45','MRKU6748797','d4f21bf9-cdc0-11ef-96a0-2cf05d502fc1',NULL),
('fea94c3f-cda7-11ef-96a0-2cf05d502fc1','',28000,'20\'','2025-01-08 10:04:48','MRKU8609131','f8a168e8-cda7-11ef-96a0-2cf05d502fc1',NULL);
/*!40000 ALTER TABLE `containers` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_containers` BEFORE INSERT ON `containers` FOR EACH ROW BEGIN
    -- Generate a UUID and set it for the 'id' column
    SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `update_bol_has_containers` AFTER INSERT ON `containers` FOR EACH ROW BEGIN
    UPDATE bol
    SET has_containers = COALESCE(has_containers, 0) + 1
    WHERE id = NEW.bol_id;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER decrement_has_containers
AFTER DELETE ON containers
FOR EACH ROW 
BEGIN	
	UPDATE bol
    SET has_containers = has_containers - 1
    WHERE id = OLD.bol_id;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `conventional_report`
--

DROP TABLE IF EXISTS `conventional_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conventional_report` (
  `id` varchar(36) NOT NULL,
  `article` varchar(128) NOT NULL,
  `quantity` int(11) NOT NULL,
  `weight` int(11) NOT NULL,
  `transportation` varchar(128) NOT NULL,
  `note` text NOT NULL,
  `destination` varchar(64) NOT NULL,
  `dossier_id` varchar(36) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conventional_report`
--

LOCK TABLES `conventional_report` WRITE;
/*!40000 ALTER TABLE `conventional_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `conventional_report` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_conventional_report` BEFORE INSERT ON `conventional_report` FOR EACH ROW BEGIN
        SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `dossier_status_history`
--

DROP TABLE IF EXISTS `dossier_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dossier_status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dossier_id` varchar(36) NOT NULL,
  `old_status` varchar(64) DEFAULT NULL,
  `new_status` varchar(64) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dossier_status_history`
--

LOCK TABLES `dossier_status_history` WRITE;
/*!40000 ALTER TABLE `dossier_status_history` DISABLE KEYS */;
INSERT INTO `dossier_status_history` VALUES
(11,'ae917810-cdc2-11ef-96a0-2cf05d502fc1','Confirmation','','2025-01-08 13:19:44'),
(12,'053a2564-cdc2-11ef-96a0-2cf05d502fc1','A Saisir','','2025-01-08 13:19:57'),
(13,'47ba56a3-cdc2-11ef-96a0-2cf05d502fc1','A Saisir','','2025-01-08 13:20:06'),
(14,'95814075-cdc2-11ef-96a0-2cf05d502fc1','Confirmation','','2025-01-08 13:20:16'),
(15,'f73236ce-cdc2-11ef-96a0-2cf05d502fc1','Confirmation','','2025-01-08 13:20:24'),
(16,'053a2564-cdc2-11ef-96a0-2cf05d502fc1','','confirmation','2025-01-08 15:29:25'),
(17,'47ba56a3-cdc2-11ef-96a0-2cf05d502fc1','','confirmation','2025-01-08 15:29:33'),
(18,'95814075-cdc2-11ef-96a0-2cf05d502fc1','','confirmation','2025-01-08 15:29:43'),
(19,'ae917810-cdc2-11ef-96a0-2cf05d502fc1','','confirmation','2025-01-08 15:29:54'),
(20,'f73236ce-cdc2-11ef-96a0-2cf05d502fc1','','confirmation','2025-01-08 15:30:05');
/*!40000 ALTER TABLE `dossier_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dossiers`
--

DROP TABLE IF EXISTS `dossiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dossiers` (
  `id` varchar(36) NOT NULL,
  `ship_id` varchar(36) NOT NULL,
  `dossier_number` varchar(255) NOT NULL,
  `company_id` varchar(36) DEFAULT NULL,
  `site_id` varchar(36) DEFAULT NULL,
  `content` text NOT NULL,
  `bol_id` varchar(36) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `type` varchar(64) NOT NULL,
  `status` varchar(64) NOT NULL DEFAULT 'a saisir',
  `numero_declaration` varchar(64) DEFAULT NULL,
  `numero_liquidation` varchar(64) DEFAULT NULL,
  `bad_number` varchar(64) DEFAULT NULL,
  `charge_local_number` varchar(64) DEFAULT NULL,
  `conventional_report_is_filled` tinyint(1) NOT NULL DEFAULT 0,
  `is_high_priority` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dossiers`
--

LOCK TABLES `dossiers` WRITE;
/*!40000 ALTER TABLE `dossiers` DISABLE KEYS */;
INSERT INTO `dossiers` VALUES
('02ce22be-ce61-11ef-96a0-2cf05d502fc1','dd5f5aba-ce60-11ef-96a0-2cf05d502fc1','IM/24/1330','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','POPCORN','aab93c48-cdd9-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-09 08:09:12','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('053a2564-cdc2-11ef-96a0-2cf05d502fc1','9a41ea95-cdc1-11ef-96a0-2cf05d502fc1','IM/24/0477','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','DETERGENT','aa8e3eef-cd0b-11ef-96a0-2cf05d502fc1','CMA-CGM','2025-01-08 13:11:06','IM4','confirmation',NULL,NULL,NULL,NULL,0,0),
('084f5213-ce90-11ef-96a0-2cf05d502fc1','d984a32b-ce66-11ef-96a0-2cf05d502fc1','IM/24/1516',NULL,NULL,'SPAGHETTI','b127ec36-cda3-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-09 13:45:47','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('14dc0412-cdda-11ef-96a0-2cf05d502fc1','a4c29c14-cdd9-11ef-96a0-2cf05d502fc1','IM/24/1219','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','HUILE','4c56a566-cda6-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-08 16:03:20','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('1b54e1b8-cdab-11ef-96a0-2cf05d502fc1','252ec169-cda1-11ef-96a0-2cf05d502fc1','IM/24/1309','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','57ad7ac7-cd91-11ef-96a0-2cf05d502fc1','DIVERSES','00394cdd-cd13-11ef-96a0-2cf05d502fc1','','2025-01-08 10:27:04','IM4','Confirmation',NULL,NULL,NULL,'13',0,0),
('1bc96ec6-ce90-11ef-96a0-2cf05d502fc1','d984a32b-ce66-11ef-96a0-2cf05d502fc1','IM/24/1522',NULL,NULL,'SPAGHETTI','8e940cb0-cda4-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-09 13:46:20','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('239bb98f-cdd8-11ef-96a0-2cf05d502fc1','d577c23b-cdd7-11ef-96a0-2cf05d502fc1','IM/24/1121','ad6e193e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','CONFISERIE','bf153744-ccfe-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-08 15:49:26','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('25e35f50-ce8f-11ef-96a0-2cf05d502fc1','d984a32b-ce66-11ef-96a0-2cf05d502fc1','IM/24/1275',NULL,NULL,'RIZ','19c65bfd-cda2-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-09 13:39:27','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('2d787d3e-cdc5-11ef-96a0-2cf05d502fc1','9a41ea95-cdc1-11ef-96a0-2cf05d502fc1','IM/24/1348','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','TOMATE','3f6484b4-cd11-11ef-96a0-2cf05d502fc1','CMA-CGM','2025-01-08 13:33:42','IM4','Confirmation',NULL,NULL,NULL,NULL,0,0),
('31a41bb4-ce90-11ef-96a0-2cf05d502fc1','d984a32b-ce66-11ef-96a0-2cf05d502fc1','IM/24/1523',NULL,NULL,'SPAGHETTI','4752f3e7-cda5-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-09 13:46:57','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('47662ae6-cdc4-11ef-96a0-2cf05d502fc1','9a41ea95-cdc1-11ef-96a0-2cf05d502fc1','IM/24/1323','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','TOMATE','85985879-cd0f-11ef-96a0-2cf05d502fc1','CMA-CGM','2025-01-08 13:27:16','IM4','Confirmation',NULL,NULL,NULL,NULL,0,0),
('47ba56a3-cdc2-11ef-96a0-2cf05d502fc1','9a41ea95-cdc1-11ef-96a0-2cf05d502fc1','IM/24/1140','ad6e197a-ccf5-11ef-96a0-2cf05d502fc1','d4926b56-ccf5-11ef-96a0-2cf05d502fc1','MACHINE','3c956aa4-cd0d-11ef-96a0-2cf05d502fc1','CMA-CGM','2025-01-08 13:12:57','IM4','confirmation',NULL,NULL,NULL,NULL,0,0),
('4d9a32b6-ce66-11ef-96a0-2cf05d502fc1','7f485e99-ce61-11ef-96a0-2cf05d502fc1','IM/24/0786','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','d4926cff-ccf5-11ef-96a0-2cf05d502fc1','DIVERSES','409274d9-cdc1-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-09 08:47:05','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('50970968-cdd8-11ef-96a0-2cf05d502fc1','d577c23b-cdd7-11ef-96a0-2cf05d502fc1','IM/24/1162','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','SPAGHETTI','898fe37b-cd00-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-08 15:50:41','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('69d9634d-cdce-11ef-96a0-2cf05d502fc1','282a2e6d-cdce-11ef-96a0-2cf05d502fc1','IM/24/1256','ad6e19b7-ccf5-11ef-96a0-2cf05d502fc1','d490de75-ccf5-11ef-96a0-2cf05d502fc1','HARICOT','7c0c9b29-cd09-11ef-96a0-2cf05d502fc1','CONNEX','2025-01-08 14:39:49','TR8 EXO','A Saisir',NULL,NULL,NULL,NULL,0,0),
('6ae0cb42-cdd8-11ef-96a0-2cf05d502fc1','d577c23b-cdd7-11ef-96a0-2cf05d502fc1','IM/24/1361','ad6e193e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','DIVERSES','e1950876-cd05-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-08 15:51:25','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('70500d2f-ce66-11ef-96a0-2cf05d502fc1','7f485e99-ce61-11ef-96a0-2cf05d502fc1','IM/24/1349','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','CONFISERIE','8ab36ed8-cdbf-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-09 08:48:03','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('786eb7a4-cdda-11ef-96a0-2cf05d502fc1','a4c29c14-cdd9-11ef-96a0-2cf05d502fc1','IM/24/1271','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','d490e1d2-ccf5-11ef-96a0-2cf05d502fc1','DIVERSES','f8a168e8-cda7-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-08 16:06:07','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('78d1b360-ce8f-11ef-96a0-2cf05d502fc1','d984a32b-ce66-11ef-96a0-2cf05d502fc1','IM/24/1483',NULL,NULL,'THE NOIR','6900f175-cda2-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-09 13:41:47','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('7f0aac33-ce79-11ef-96a0-2cf05d502fc1','d984a32b-ce66-11ef-96a0-2cf05d502fc1','IM/24/0785','ad6e19b7-ccf5-11ef-96a0-2cf05d502fc1','d490de75-ccf5-11ef-96a0-2cf05d502fc1','PRODUIT CHIMIQUE','0e50bee7-cd9f-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-09 11:04:28','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('84917e78-ce66-11ef-96a0-2cf05d502fc1','7f485e99-ce61-11ef-96a0-2cf05d502fc1','IM/24/1399','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','CONFISERIE','4bef52a0-cdbf-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-09 08:48:37','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('8e112382-cdd8-11ef-96a0-2cf05d502fc1','d577c23b-cdd7-11ef-96a0-2cf05d502fc1','IM/24/1528','ad6e193e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','DIVERSES','534d20d7-cd07-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-08 15:52:24','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('8f959759-cdce-11ef-96a0-2cf05d502fc1','282a2e6d-cdce-11ef-96a0-2cf05d502fc1','IM/24/1466','ad6e19b7-ccf5-11ef-96a0-2cf05d502fc1','d490de75-ccf5-11ef-96a0-2cf05d502fc1','PRODUIT CHIMIQUE','330b6888-cd0a-11ef-96a0-2cf05d502fc1','CONNEX','2025-01-08 14:40:52','TR8 EXO','A Saisir',NULL,NULL,NULL,NULL,0,0),
('93664adc-cdda-11ef-96a0-2cf05d502fc1','a4c29c14-cdd9-11ef-96a0-2cf05d502fc1','IM/24/1274','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','d490e1d2-ccf5-11ef-96a0-2cf05d502fc1','BICARBONATE','381ec88d-cda8-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-08 16:06:52','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('95814075-cdc2-11ef-96a0-2cf05d502fc1','9a41ea95-cdc1-11ef-96a0-2cf05d502fc1','IM/24/1215','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','LAIT','75a48e36-cd0d-11ef-96a0-2cf05d502fc1','CMA-CGM','2025-01-08 13:15:08','IM4','confirmation',NULL,NULL,NULL,NULL,0,0),
('96a44e9a-cdab-11ef-96a0-2cf05d502fc1','252ec169-cda1-11ef-96a0-2cf05d502fc1','IM/24/1488','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','57ad7ac7-cd91-11ef-96a0-2cf05d502fc1','DIVERSES','62a5cda2-cd13-11ef-96a0-2cf05d502fc1','','2025-01-08 10:30:31','IM4','Confirmation',NULL,NULL,NULL,'14',0,0),
('974d7114-ce66-11ef-96a0-2cf05d502fc1','7f485e99-ce61-11ef-96a0-2cf05d502fc1','IM/24/1400','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','CONFISERIE','15213cae-cdbf-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-09 08:49:08','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('a1c9412c-cdc4-11ef-96a0-2cf05d502fc1','9a41ea95-cdc1-11ef-96a0-2cf05d502fc1','IM/24/1324','ad6e190e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','TOMATE','2d6e4860-cd10-11ef-96a0-2cf05d502fc1','CMA-CGM','2025-01-08 13:29:47','IM4','Confirmation',NULL,NULL,NULL,NULL,0,0),
('aa66ce2d-ce8e-11ef-96a0-2cf05d502fc1','d984a32b-ce66-11ef-96a0-2cf05d502fc1','IM/24/0976',NULL,NULL,'DIVERSES','5fb2a13c-cd9f-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-09 13:36:00','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('ae917810-cdc2-11ef-96a0-2cf05d502fc1','9a41ea95-cdc1-11ef-96a0-2cf05d502fc1','IM/24/1216','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','LAIT','7d47b035-cd0e-11ef-96a0-2cf05d502fc1','CMA-CGM','2025-01-08 13:15:50','IM4','confirmation',NULL,NULL,NULL,NULL,0,0),
('b1651087-cdda-11ef-96a0-2cf05d502fc1','a4c29c14-cdd9-11ef-96a0-2cf05d502fc1','IM/24/1415','ad6e193e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','MONOSODIUM','9c4bd545-cda8-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-08 16:07:43','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('bae476c2-ce66-11ef-96a0-2cf05d502fc1','7f485e99-ce61-11ef-96a0-2cf05d502fc1','IM/24/1502','ad6e19ef-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','CONFISERIE','de83709e-cdbe-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-09 08:50:08','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('bce9db4e-cdd8-11ef-96a0-2cf05d502fc1','d577c23b-cdd7-11ef-96a0-2cf05d502fc1','IM/24/1530','ad6e193e-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','CONFISERIE','990c87fe-cd08-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-08 15:53:43','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('c7403b13-cdcd-11ef-96a0-2cf05d502fc1','9a41ea95-cdc1-11ef-96a0-2cf05d502fc1','IM/24/1526','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','d4926cff-ccf5-11ef-96a0-2cf05d502fc1','EMBALLAGE','5fba868d-cdc6-11ef-96a0-2cf05d502fc1','CMA-CGM','2025-01-08 14:35:16','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('c8180e69-cff7-11ef-96a0-2cf05d502fc1','9a41ea95-cdc1-11ef-96a0-2cf05d502fc1','IM/24/0477-B','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','DETERGENT','aa8e3eef-cd0b-11ef-96a0-2cf05d502fc1','CMA-CGM','2025-01-08 13:11:06','IM4','confirmation',NULL,NULL,NULL,NULL,0,0),
('d13be17a-ce8e-11ef-96a0-2cf05d502fc1','d984a32b-ce66-11ef-96a0-2cf05d502fc1','IM/24/1187',NULL,NULL,'PAPIER DUPLICATEUR','91b3143d-cd9f-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-09 13:37:05','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('d1a1faa4-ce8e-11ef-96a0-2cf05d502fc1','d984a32b-ce66-11ef-96a0-2cf05d502fc1','IM/24/1187',NULL,NULL,'PAPIER DUPLICATEUR','91b3143d-cd9f-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-09 13:37:06','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('d9c5b4d7-cda1-11ef-96a0-2cf05d502fc1','252ec169-cda1-11ef-96a0-2cf05d502fc1','IM/24/1132','ad6e165e-ccf5-11ef-96a0-2cf05d502fc1','d490e06d-ccf5-11ef-96a0-2cf05d502fc1','BETONNIERE ET APPAREILS','651f428d-cd12-11ef-96a0-2cf05d502fc1','','2025-01-08 09:20:49','IM4','Confirmation',NULL,NULL,NULL,'12',0,0),
('f5563240-ce8e-11ef-96a0-2cf05d502fc1','d984a32b-ce66-11ef-96a0-2cf05d502fc1','IM/24/1206',NULL,NULL,'DIVERSES','ca84670a-cda1-11ef-96a0-2cf05d502fc1','MAERSK','2025-01-09 13:38:06','IM4','A Saisir',NULL,NULL,NULL,NULL,0,0),
('f73236ce-cdc2-11ef-96a0-2cf05d502fc1','9a41ea95-cdc1-11ef-96a0-2cf05d502fc1','IM/24/1262','ad6e18a1-ccf5-11ef-96a0-2cf05d502fc1','d4926b78-ccf5-11ef-96a0-2cf05d502fc1','LAIT','bd9777bc-cd0e-11ef-96a0-2cf05d502fc1','CMA-CGM','2025-01-08 13:17:52','IM4','confirmation',NULL,NULL,NULL,NULL,0,1);
/*!40000 ALTER TABLE `dossiers` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_dossiers` BEFORE INSERT ON `dossiers` FOR EACH ROW BEGIN
        SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_update_dossiers_status` AFTER UPDATE ON `dossiers` FOR EACH ROW BEGIN
  -- Only insert a record if the status has changed
  IF NEW.status != OLD.status THEN
    INSERT INTO `dossier_status_history` (`dossier_id`, `old_status`, `new_status`, `changed_at`)
    VALUES (OLD.id, OLD.status, NEW.status, CURRENT_TIMESTAMP);
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs` (
  `id` varchar(36) NOT NULL,
  `dossier_id` varchar(36) NOT NULL,
  `user_id` int(5) NOT NULL,
  `time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
INSERT INTO `logs` VALUES
('973626fc-c39b-11ef-9b34-0efd67b0fe77','e07afa90-c2d8-11ef-9b34-0efd67b0fe77',7,'2024-12-26 16:10:49'),
('8202b630-c425-11ef-9b34-0efd67b0fe77','5ae394b4-c364-11ef-9b34-0efd67b0fe77',7,'2024-12-27 08:38:04'),
('1c3d9eea-c435-11ef-9b34-0efd67b0fe77','071b2618-c435-11ef-9b34-0efd67b0fe77',7,'2024-12-27 10:29:45'),
('568d04f2-c50a-11ef-9b34-0efd67b0fe77','4ee21bde-c50a-11ef-9b34-0efd67b0fe77',7,'2024-12-28 11:56:06');
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_logs` BEFORE INSERT ON `logs` FOR EACH ROW BEGIN
  -- Check if log_id is NULL or not provided, then generate a UUID
    SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `lookup_references`
--

DROP TABLE IF EXISTS `lookup_references`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lookup_references` (
  `id` char(36) NOT NULL,
  `descr` varchar(255) NOT NULL,
  `type` enum('table','field') NOT NULL,
  `description_2` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lookup_references`
--

LOCK TABLES `lookup_references` WRITE;
/*!40000 ALTER TABLE `lookup_references` DISABLE KEYS */;
INSERT INTO `lookup_references` VALUES
('0548dae0-aef2-11ef-9eb4-0efd67b0fe78','id','field','companies'),
('1089d49a-aef2-11ef-9eb4-0efd67b0fe78','company_name','field','companies'),
('1bbd62aa-aef2-11ef-9eb4-0efd67b0fe78','id','field','site_name'),
('1dfa97f8-aef3-11ef-9eb4-0efd67b0fe78','true','field','true'),
('23f78090-aef2-11ef-9eb4-0efd67b0fe78','site_name','field','sites'),
('26a583d6-aef3-11ef-9eb4-0efd67b0fe78','false','field','false'),
('b7c2da00-aef1-11ef-9eb4-0efd67b0fe78','companies','table',''),
('bc5046b6-aef1-11ef-9eb4-0efd67b0fe78','sites','table','');
/*!40000 ALTER TABLE `lookup_references` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_lookup_references` BEFORE INSERT ON `lookup_references` FOR EACH ROW BEGIN
    -- Generate a new GUID (UUID) before the insert
    SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `organisations`
--

DROP TABLE IF EXISTS `organisations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organisations` (
  `id` varchar(36) NOT NULL,
  `name` varchar(64) NOT NULL,
  `isactive` tinyint(1) NOT NULL,
  `address_id` varchar(36) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `address_id` (`address_id`),
  CONSTRAINT `organisations_ibfk_1` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organisations`
--

LOCK TABLES `organisations` WRITE;
/*!40000 ALTER TABLE `organisations` DISABLE KEYS */;
/*!40000 ALTER TABLE `organisations` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_organisations` BEFORE INSERT ON `organisations` FOR EACH ROW BEGIN
    -- Set the `id` field to a new UUID
    SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `parks`
--

DROP TABLE IF EXISTS `parks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parks` (
  `id` varchar(36) NOT NULL,
  `name` varchar(64) NOT NULL,
  `contact_id` varchar(36) NOT NULL,
  `isactive` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  CONSTRAINT `parks_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parks`
--

LOCK TABLES `parks` WRITE;
/*!40000 ALTER TABLE `parks` DISABLE KEYS */;
/*!40000 ALTER TABLE `parks` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_parks` BEFORE INSERT ON `parks` FOR EACH ROW BEGIN
    -- Set the `id` field to a new UUID
    SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `ports`
--

DROP TABLE IF EXISTS `ports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ports` (
  `id` varchar(36) NOT NULL,
  `name` varchar(64) NOT NULL,
  `description` varchar(512) NOT NULL,
  `container_only` tinyint(1) NOT NULL,
  `conventional_only` tinyint(1) NOT NULL,
  `franchise_mgt` int(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ports`
--

LOCK TABLES `ports` WRITE;
/*!40000 ALTER TABLE `ports` DISABLE KEYS */;
INSERT INTO `ports` VALUES
('3dec99e0-be06-11ef-aadb-0efd67b0fe77','SOCOPE','SOCOPE SARL ROUTE ANGO',0,1,73523755),
('a33a6662-c13d-11ef-9b34-0efd67b0fe77','MGT','MATADI GATEWAY TERMINAL',1,0,4524),
('dcc164f5-cd9f-11ef-96a0-2cf05d502fc1','MCTC','A COTE ONATRA',1,0,4),
('eb5ddf52-c1db-11ef-9b34-0efd67b0fe77','SCTP','ONATRA',1,1,0);
/*!40000 ALTER TABLE `ports` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_ports` BEFORE INSERT ON `ports` FOR EACH ROW BEGIN
    -- Set the `id` field to a new UUID
    SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `shipping_lines`
--

DROP TABLE IF EXISTS `shipping_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipping_lines` (
  `id` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_lines`
--

LOCK TABLES `shipping_lines` WRITE;
/*!40000 ALTER TABLE `shipping_lines` DISABLE KEYS */;
INSERT INTO `shipping_lines` VALUES
('03101185-cd9f-11ef-96a0-2cf05d502fc1','MSC / BOLLORE','2025-01-08 09:00:30'),
('7e52ceb3-cdc5-11ef-96a0-2cf05d502fc1','LMC','2025-01-08 13:35:57'),
('a0efe016-c116-11ef-9b34-0efd67b0fe77','CMA CGM','2024-12-23 10:29:06'),
('bc00dda6-c116-11ef-9b34-0efd67b0fe77','MAERSK','2024-12-23 10:29:06'),
('c1c395bc-c116-11ef-9b34-0efd67b0fe77','HAPAG LLOYD / CONNEX AFRICA','2024-12-23 10:29:06'),
('c9312260-c116-11ef-9b34-0efd67b0fe77','COSCO / POLYTRA','2024-12-23 10:29:06');
/*!40000 ALTER TABLE `shipping_lines` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_shipping_lines_insert` BEFORE INSERT ON `shipping_lines` FOR EACH ROW BEGIN

        SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `ships`
--

DROP TABLE IF EXISTS `ships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ships` (
  `id` varchar(36) NOT NULL,
  `serial_number` varchar(36) NOT NULL,
  `port_id` varchar(36) NOT NULL,
  `date` date DEFAULT NULL,
  `note` varchar(512) NOT NULL,
  `vessel_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_conventional` tinyint(1) NOT NULL DEFAULT 0,
  `conventional_report_is_filled` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `port_id` (`port_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ships`
--

LOCK TABLES `ships` WRITE;
/*!40000 ALTER TABLE `ships` DISABLE KEYS */;
INSERT INTO `ships` VALUES
('1955aec2-c514-11ef-9b34-0efd67b0fe77','SN 234652','a33a6662-c13d-11ef-9b34-0efd67b0fe77','2025-01-09','Test SN 2','6dfd2a9e-c12d-11ef-9b34-0efd67b0fe77','2024-12-28 12:05:58',1,0),
('252ec169-cda1-11ef-96a0-2cf05d502fc1','511A','a33a6662-c13d-11ef-9b34-0efd67b0fe77','2024-12-31','VOYAGE ','45eae56b-cd9f-11ef-96a0-2cf05d502fc1','2025-01-08 09:15:46',0,0),
('282a2e6d-cdce-11ef-96a0-2cf05d502fc1','512A','a33a6662-c13d-11ef-9b34-0efd67b0fe77',NULL,'CONNEX','e64d7482-cd9e-11ef-96a0-2cf05d502fc1','2025-01-08 14:37:58',0,0),
('34560456-c50a-11ef-9b34-0efd67b0fe77','SN 2450924','eb5ddf52-c1db-11ef-9b34-0efd67b0fe77','2024-12-31','This is a test','6dfd2a9e-c12d-11ef-9b34-0efd67b0fe77','2024-12-28 10:55:09',0,0),
('4aa0e058-cc37-11ef-bead-0efd67b0fe78','SN 2304-95','eb5ddf52-c1db-11ef-9b34-0efd67b0fe77','2025-01-23','asdfasdf','6dfd2a9e-c12d-11ef-9b34-0efd67b0fe77','2025-01-06 14:05:31',1,0),
('7f485e99-ce61-11ef-96a0-2cf05d502fc1','006A','a33a6662-c13d-11ef-9b34-0efd67b0fe77','2025-01-07','MAERSK - 501S - AJOUTE','66744a5c-ce61-11ef-96a0-2cf05d502fc1','2025-01-09 08:12:41',0,0),
('9a41ea95-cdc1-11ef-96a0-2cf05d502fc1','507A','a33a6662-c13d-11ef-9b34-0efd67b0fe77','2025-01-02','02430R','3bf2fb4f-cd9f-11ef-96a0-2cf05d502fc1','2025-01-08 13:08:06',0,0),
('a33ab086-c9bc-11ef-9b34-0efd67b0fe77','SN 67920384','eb5ddf52-c1db-11ef-9b34-0efd67b0fe77','2025-01-25','Note','6dfd2a9e-c12d-11ef-9b34-0efd67b0fe77','2025-01-03 10:22:30',0,0),
('a4c29c14-cdd9-11ef-96a0-2cf05d502fc1','004A','a33a6662-c13d-11ef-9b34-0efd67b0fe77',NULL,'MAERSK - 2501','54c37a59-cd9f-11ef-96a0-2cf05d502fc1','2025-01-08 16:00:12',0,0),
('a5e58eac-cc12-11ef-9b34-0efd67b0fe77','SN 23057235','eb5ddf52-c1db-11ef-9b34-0efd67b0fe77','2025-01-23','This is a test for conventional SN','6dfd2a9e-c12d-11ef-9b34-0efd67b0fe77','2025-01-06 09:43:13',1,0),
('b0946e68-cdcf-11ef-96a0-2cf05d502fc1','002A','eb5ddf52-c1db-11ef-9b34-0efd67b0fe77','2025-01-04','BOLLORE','2b18b551-cd9f-11ef-96a0-2cf05d502fc1','2025-01-08 14:48:57',0,0),
('d577c23b-cdd7-11ef-96a0-2cf05d502fc1','513A','a33a6662-c13d-11ef-9b34-0efd67b0fe77',NULL,'MAERSK - 2457','d333ec3d-cd9e-11ef-96a0-2cf05d502fc1','2025-01-08 15:47:15',0,0),
('d984a32b-ce66-11ef-96a0-2cf05d502fc1','003A','a33a6662-c13d-11ef-9b34-0efd67b0fe77',NULL,'MAERSK - 2501','7359cbdc-cd9f-11ef-96a0-2cf05d502fc1','2025-01-09 08:50:59',0,0),
('dd5f5aba-ce60-11ef-96a0-2cf05d502fc1','516A','a33a6662-c13d-11ef-9b34-0efd67b0fe77',NULL,'MAERSK - 2457 - AJOUTE','d333ec3d-cd9e-11ef-96a0-2cf05d502fc1','2025-01-09 08:08:09',0,0),
('eebd9ea6-cdd8-11ef-96a0-2cf05d502fc1','516A','a33a6662-c13d-11ef-9b34-0efd67b0fe77',NULL,'MAERSK - 2457 - AJOUTE','d333ec3d-cd9e-11ef-96a0-2cf05d502fc1','2025-01-08 15:55:06',0,0);
/*!40000 ALTER TABLE `ships` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_ships` BEFORE INSERT ON `ships` FOR EACH ROW BEGIN
    -- Set the `id` field to a new UUID
    SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `sites`
--

DROP TABLE IF EXISTS `sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sites` (
  `id` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sites`
--

LOCK TABLES `sites` WRITE;
/*!40000 ALTER TABLE `sites` DISABLE KEYS */;
INSERT INTO `sites` VALUES
('4cb0fb6a-bf78-11ef-9b34-0efd67b0fe77','Site B','2024-12-21 08:48:07'),
('a01abffc-bf78-11ef-9b34-0efd67b0fe77','Test Add Site','2024-12-21 08:50:27'),
('9d1d1834-c105-11ef-9b34-0efd67b0fe77','Site A','2024-12-23 08:12:12'),
('ddefc5ea-c11a-11ef-9b34-0efd67b0fe77','Kinshasa Mall','2024-12-23 10:44:20'),
('d490de75-ccf5-11ef-96a0-2cf05d502fc1','AGRICOLE BANDUNDU','2025-01-07 12:49:27'),
('d490e025-ccf5-11ef-96a0-2cf05d502fc1','ALJAWAD RESTAURANT','2025-01-07 12:49:27'),
('d490e06d-ccf5-11ef-96a0-2cf05d502fc1','BBC','2025-01-07 12:49:27'),
('d490e08c-ccf5-11ef-96a0-2cf05d502fc1','CALKIN','2025-01-07 12:49:27'),
('d490e0ae-ccf5-11ef-96a0-2cf05d502fc1','CAP CONGO','2025-01-07 12:49:27'),
('d490e0c9-ccf5-11ef-96a0-2cf05d502fc1','CCA','2025-01-07 12:49:27'),
('d490e1b7-ccf5-11ef-96a0-2cf05d502fc1','CENTRALE BETON','2025-01-07 12:49:27'),
('d490e1d2-ccf5-11ef-96a0-2cf05d502fc1','COFUFER','2025-01-07 12:49:27'),
('d490e2a3-ccf5-11ef-96a0-2cf05d502fc1','COMECOM','2025-01-07 12:49:27'),
('d4926ac2-ccf5-11ef-96a0-2cf05d502fc1','CROWN MINING','2025-01-07 12:49:27'),
('d4926b33-ccf5-11ef-96a0-2cf05d502fc1','ECOTRANS','2025-01-07 12:49:27'),
('d4926b56-ccf5-11ef-96a0-2cf05d502fc1','GALLERY','2025-01-07 12:49:27'),
('d4926b78-ccf5-11ef-96a0-2cf05d502fc1','AFRIFOOD','2025-01-07 12:49:27'),
('d4926b9a-ccf5-11ef-96a0-2cf05d502fc1','IFCO','2025-01-07 12:49:27'),
('d4926bb7-ccf5-11ef-96a0-2cf05d502fc1','LUBUMBASHI','2025-01-07 12:49:27'),
('d4926bd2-ccf5-11ef-96a0-2cf05d502fc1','PROJET AGRICOLE MBANKANA','2025-01-07 12:49:27'),
('d4926bf4-ccf5-11ef-96a0-2cf05d502fc1','AGROPALM','2025-01-07 12:49:27'),
('d4926c10-ccf5-11ef-96a0-2cf05d502fc1','SICILIA','2025-01-07 12:49:27'),
('d4926c2c-ccf5-11ef-96a0-2cf05d502fc1','SICOREF','2025-01-07 12:49:27'),
('d4926c49-ccf5-11ef-96a0-2cf05d502fc1','SIMMOKIN','2025-01-07 12:49:27'),
('d4926c63-ccf5-11ef-96a0-2cf05d502fc1','SOAP BAR','2025-01-07 12:49:27'),
('d4926ca7-ccf5-11ef-96a0-2cf05d502fc1','SPC','2025-01-07 12:49:27'),
('d4926cc9-ccf5-11ef-96a0-2cf05d502fc1','TMI','2025-01-07 12:49:27'),
('d4926ce4-ccf5-11ef-96a0-2cf05d502fc1','TRANSKAT','2025-01-07 12:49:27'),
('d4926cff-ccf5-11ef-96a0-2cf05d502fc1','USINE BISCOF','2025-01-07 12:49:27'),
('d4926d1f-ccf5-11ef-96a0-2cf05d502fc1','USINE BRIQUE','2025-01-07 12:49:27'),
('d4926d3b-ccf5-11ef-96a0-2cf05d502fc1','USINE EXTINCTEUR','2025-01-07 12:49:27'),
('d4926d58-ccf5-11ef-96a0-2cf05d502fc1','USINE GALERIE','2025-01-07 12:49:27'),
('d4926d72-ccf5-11ef-96a0-2cf05d502fc1','USINE PEINTURE','2025-01-07 12:49:27'),
('d4926d8d-ccf5-11ef-96a0-2cf05d502fc1','USINE SAVON','2025-01-07 12:49:27'),
('d4926daa-ccf5-11ef-96a0-2cf05d502fc1','WE PLAY','2025-01-07 12:49:27'),
('d4926dc5-ccf5-11ef-96a0-2cf05d502fc1','VIRTUAL REALITY','2025-01-07 12:49:27'),
('57ad7ac7-cd91-11ef-96a0-2cf05d502fc1','Eagle Color','2025-01-08 07:22:39');
/*!40000 ALTER TABLE `sites` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_sites` BEFORE INSERT ON `sites` FOR EACH ROW BEGIN
    SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `transportation_companies`
--

DROP TABLE IF EXISTS `transportation_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transportation_companies` (
  `id` varchar(36) NOT NULL,
  `name` varchar(64) NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `is_private` tinyint(1) NOT NULL,
  `contact_id` varchar(36) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  CONSTRAINT `transportation_companies_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transportation_companies`
--

LOCK TABLES `transportation_companies` WRITE;
/*!40000 ALTER TABLE `transportation_companies` DISABLE KEYS */;
/*!40000 ALTER TABLE `transportation_companies` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_transportation_companies` BEFORE INSERT ON `transportation_companies` FOR EACH ROW BEGIN
    -- Set the `id` field to a new UUID
    SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `trucks`
--

DROP TABLE IF EXISTS `trucks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trucks` (
  `id` varchar(36) NOT NULL,
  `reg_number` varchar(12) NOT NULL,
  `description` text NOT NULL,
  `contact_id` varchar(36) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  CONSTRAINT `trucks_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trucks`
--

LOCK TABLES `trucks` WRITE;
/*!40000 ALTER TABLE `trucks` DISABLE KEYS */;
/*!40000 ALTER TABLE `trucks` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_trucks` BEFORE INSERT ON `trucks` FOR EACH ROW BEGIN
    -- Set the `id` field to a new UUID
    SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `useremail` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `isadmin` tinyint(1) DEFAULT 0,
  `canadd` tinyint(1) NOT NULL DEFAULT 0,
  `canedit` tinyint(1) NOT NULL DEFAULT 0,
  `candelete` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `useremail` (`useremail`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(7,'henry@gmail.com','ali','$2y$10$vC41AOMLc.nfBlZFOwukkuN/44tpQIlIjnGdRMMOVdlzOTf5fT5zq','754bcf4b23f6b6f887e30182f22ac0b7bd577256d26e7e744546ac8403ee855a3aa236909dd98571731913e85f8dd1b1e7c9','2020-09-24 17:53:37',1,1,1,1),
(11,'ali@gmail.com','alioss','$2y$10$aIgMmwf3aF39Vo19LR1qB.LXMxPLTDz1rVPd.R8xXqSl1SCwJ.dUi','e3f8fe5f5d6236dce4dbce6aed0586d37b803d33da855047a06154a82f27bcdf98137d74a414adf3aa660fa6c33f010be0fa','2024-11-25 10:39:58',0,1,1,1),
(12,'test@email.com','alijamil','$2y$10$JZ1Qg.jWSD.xPjfhF5diA.R0lvArJ5eVwyBGFkOcADl86jgVPFN1i','83e0c5819c815e507c0690ef07b8fdccc412a5a7eb2abfbdb8123b6adc252649ad4ff05f9cb67fb150834088190a5128273f','2024-12-30 11:21:20',0,0,0,0),
(13,'testtest@test.test','test','$2y$10$/NWzf5L6SzQZ8lyn/d8B8OLe0XuBagvnNYw4Jziyf2eu4dMNnTWt2','35448c560e5a1a12d9708c2fb25d31cd30c8cf7d92cd6df5e3f3f8785bff85eec602abac6f08f81ff3ba6fc3fd674c2ef4b0','2025-01-07 13:44:01',0,1,1,1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vessels`
--

DROP TABLE IF EXISTS `vessels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vessels` (
  `id` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `shipping_line_id` varchar(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vessels`
--

LOCK TABLES `vessels` WRITE;
/*!40000 ALTER TABLE `vessels` DISABLE KEYS */;
INSERT INTO `vessels` VALUES
('2b18b551-cd9f-11ef-96a0-2cf05d502fc1','SIGMAF','03101185-cd9f-11ef-96a0-2cf05d502fc1','2025-01-08 09:01:37'),
('3bf2fb4f-cd9f-11ef-96a0-2cf05d502fc1','SC PHOENIX','a0efe016-c116-11ef-9b34-0efd67b0fe77','2025-01-08 09:02:05'),
('45eae56b-cd9f-11ef-96a0-2cf05d502fc1','CABINDA EXPRESS ','c1c395bc-c116-11ef-9b34-0efd67b0fe77','2025-01-08 09:02:22'),
('54c37a59-cd9f-11ef-96a0-2cf05d502fc1','FALKENBERG','bc00dda6-c116-11ef-9b34-0efd67b0fe77','2025-01-08 09:02:47'),
('66744a5c-ce61-11ef-96a0-2cf05d502fc1','X-PRESS MEGHNA','bc00dda6-c116-11ef-9b34-0efd67b0fe77','2025-01-09 08:11:59'),
('7359cbdc-cd9f-11ef-96a0-2cf05d502fc1','ONEGO MYSTRAL ','bc00dda6-c116-11ef-9b34-0efd67b0fe77','2025-01-08 09:03:38'),
('d333ec3d-cd9e-11ef-96a0-2cf05d502fc1','APALOS ','bc00dda6-c116-11ef-9b34-0efd67b0fe77','2025-01-08 08:59:09'),
('e64d7482-cd9e-11ef-96a0-2cf05d502fc1','MEDKON NLG','c1c395bc-c116-11ef-9b34-0efd67b0fe77','2025-01-08 08:59:42');
/*!40000 ALTER TABLE `vessels` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_shipping_lines` BEFORE INSERT ON `vessels` FOR EACH ROW BEGIN
        SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouses` (
  `id` varchar(36) NOT NULL,
  `name` varchar(64) NOT NULL,
  `description` text NOT NULL,
  `contact_id` varchar(36) NOT NULL,
  `admin_user_id` varchar(36) NOT NULL,
  `manager_user_id` varchar(36) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  CONSTRAINT `warehouses_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_insert_warehouses` BEFORE INSERT ON `warehouses` FOR EACH ROW BEGIN
    -- Set the `id` field to a new UUID
    SET NEW.id = UUID();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-01-13  0:00:00
