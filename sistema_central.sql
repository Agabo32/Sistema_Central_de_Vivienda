-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-03-2025 a las 19:57:43
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_central`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acabado`
--

CREATE TABLE `acabado` (
  `ID_ACABADO` int(11) NOT NULL,
  `ID_CONSTRUCCION` int(11) NOT NULL,
  `COLOCACION_VENTANAS` float NOT NULL,
  `COLOACION_PUERTAS` float NOT NULL,
  `INSTALACIONES_ELECTRICAS+` float NOT NULL,
  `FRISOS` float NOT NULL,
  `SOBRE_PISO` float NOT NULL,
  `CERAMICA_DE_BAÑO` float NOT NULL,
  `COLOCACION_PÚERTAS_INT` float NOT NULL,
  `EQUIPOS_Y_ACC_SANITARIOS` float NOT NULL,
  `COLOCACION_DE_LAVAPLATOS` float NOT NULL,
  `PINTURA` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acondicionamiento`
--

CREATE TABLE `acondicionamiento` (
  `ID_ACONDICIONAMIENTO` int(11) NOT NULL,
  `ID_CONSTRUCCION` int(11) NOT NULL,
  `LIMPIEZA` float NOT NULL,
  `REPLANTEO` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `beneficiario`
--

CREATE TABLE `beneficiario` (
  `ID_BENEFICIARIOS` int(11) NOT NULL,
  `ID_CASA` int(11) NOT NULL,
  `NOMB_BENEFICIARIO` varchar(50) NOT NULL,
  `APE_BENFICIARIO` varchar(50) NOT NULL,
  `CEDULA` int(8) NOT NULL,
  `TIPO_DOCUMENTO` enum('V','E','J') NOT NULL,
  `TELEFONO` int(11) NOT NULL,
  `ID_COD_OBRA` int(11) NOT NULL,
  `STATUS_BENE` enum('ACTIVO','INACTIVO') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `casa`
--

CREATE TABLE `casa` (
  `ID_CASA` int(11) NOT NULL,
  `ID_BENEFICIARIOS` int(11) NOT NULL,
  `ID_UBI_CASA` int(11) NOT NULL,
  `ID_DATOS_CASA` int(11) NOT NULL,
  `ID_OBSERVACION` int(11) NOT NULL,
  `ID_MODELO_CONSTRUCTIVO_1` int(11) NOT NULL,
  `ID_MODELO_CONSTRUCTIVO_2` int(11) NOT NULL,
  `FECHA_CULMINACION` date NOT NULL,
  `ACTA` varchar(50) NOT NULL,
  `FIASCALIZADOR` varchar(50) NOT NULL,
  `STATUS` enum('ACTIVO','INACTIVO') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cerramiento`
--

CREATE TABLE `cerramiento` (
  `ID_CERRAMIENTO` int(11) NOT NULL,
  `ID_CONSTRUCCION` int(11) NOT NULL,
  `BLOQUEADO` float NOT NULL,
  `COLOCACION_CORREAS` float NOT NULL,
  `COLOCACION _TECHO` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ciudad`
--

CREATE TABLE `ciudad` (
  `id_ciudad` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `ciudad` varchar(255) NOT NULL,
  `capital` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `codigo de obra`
--

CREATE TABLE `codigo de obra` (
  `ID_COD_OBRA` int(11) NOT NULL,
  `ID_BENEFICIARIOS` int(11) NOT NULL,
  `COD_OBRA` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comunidad`
--

CREATE TABLE `comunidad` (
  `ID_COMUNIDAD` int(11) NOT NULL,
  `id_parroquia` int(11) NOT NULL,
  `Comunidad` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `construccion`
--

CREATE TABLE `construccion` (
  `ID_CONSTRUCCION` int(11) NOT NULL,
  `ID_DATOS_CASA` int(11) NOT NULL,
  `ID_ACONDICIONAMIENTO` int(11) NOT NULL,
  `ID_FUNDACION` int(11) NOT NULL,
  `ID_ACABADOS` int(11) NOT NULL,
  `ID_CERRAMIENTO` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `credenciales`
--

CREATE TABLE `credenciales` (
  `ID_CREDENCIALES` int(11) NOT NULL,
  `ID_USUARIO` int(11) NOT NULL,
  `ID_ROL` int(11) NOT NULL,
  `NOMB_USUARIO` varchar(255) NOT NULL,
  `CONTRASEÑA_HASH` varchar(7) NOT NULL,
  `FECHA_CREACION` datetime NOT NULL,
  `ULTIMO_ACCESO` datetime NOT NULL,
  `INTENTOS` int(11) NOT NULL,
  `STATUS_CUENTA` enum('ACTIVO','INACTIVO') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `datos_casa`
--

CREATE TABLE `datos_casa` (
  `ID_DATOS_CASA` int(11) NOT NULL,
  `ID_CONSTRUCCION` int(11) NOT NULL,
  `ID_CASA` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado`
--

CREATE TABLE `estado` (
  `id_estado` int(11) NOT NULL,
  `estado` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estructura`
--

CREATE TABLE `estructura` (
  `ID_ESTRUCTURA` int(11) NOT NULL,
  `ID_CONSTRUCCION` int(11) NOT NULL,
  `ARMADO_DE_COLUMNAS` float NOT NULL,
  `VACIADO_DE_COLUMNAS` float NOT NULL,
  `ARMADO_DE_VIGAS` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fundacion`
--

CREATE TABLE `fundacion` (
  `ID_FUNDACION` int(11) NOT NULL,
  `ID_CONSTRUCCION` int(11) NOT NULL,
  `EXCAVACION` float NOT NULL,
  `ACERO_VIGAS` float NOT NULL,
  `ENCOFRADO_Y_COLOCACION` float NOT NULL,
  `INSTALACIONES_ELECTRICAS` float NOT NULL,
  `VACIOADO_DE_LOSA` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modelo constructivo 1`
--

CREATE TABLE `modelo constructivo 1` (
  `ID_MODELO_CONSTRUCTIVO 1` int(11) NOT NULL,
  `ID_CASA` int(11) NOT NULL,
  `MODELO_CONSTUCTIVO` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modelo constructivo 2`
--

CREATE TABLE `modelo constructivo 2` (
  `MODELO_CONSTRUCTIVO_2` int(11) NOT NULL,
  `ID_CASA` int(11) NOT NULL,
  `MODELO_CONSTUCTIVO` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `municipio`
--

CREATE TABLE `municipio` (
  `id_municipio` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `municipio` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `observaciones`
--

CREATE TABLE `observaciones` (
  `ID_OBSERVACION` int(11) NOT NULL,
  `ID_CASA` int(11) NOT NULL,
  `Observaciones_Responsables` text NOT NULL,
  `Observaciones_de_Fiscalizadores` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parroquias`
--

CREATE TABLE `parroquias` (
  `id_parroquia` int(11) NOT NULL,
  `id_municipio` int(11) NOT NULL,
  `parroquia` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `ID_ROL` int(11) NOT NULL,
  `ID_CREDENCIALES` int(11) NOT NULL,
  `NOM_ROL` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubicacion`
--

CREATE TABLE `ubicacion` (
  `ID_UBICACION` int(11) NOT NULL,
  `ID_UBI_CASA` int(11) NOT NULL,
  `ID_COMUNIDAD` int(11) NOT NULL,
  `ID_PARROQUIA` int(11) NOT NULL,
  `ID_MUNICIPIO` int(11) NOT NULL,
  `ID_DIRECCION_EXACTA` int(11) NOT NULL,
  `UTM_NORTE` varchar(50) NOT NULL,
  `UTM_ESTE` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubi_casa`
--

CREATE TABLE `ubi_casa` (
  `ID_UBI_CASA` int(11) NOT NULL,
  `ID_UBICACION` int(11) NOT NULL,
  `ID_CASA` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `ID_USUARIO` int(11) NOT NULL,
  `ID_CREDENCIALES` int(11) NOT NULL,
  `NOMBRE` varchar(255) NOT NULL,
  `APELLIDO` varchar(255) NOT NULL,
  `CEDULA` int(8) NOT NULL,
  `TIPO_DOCUMENTO` enum('V','E','J') NOT NULL,
  `CORREO` varchar(255) NOT NULL,
  `TELEFONO` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `acabado`
--
ALTER TABLE `acabado`
  ADD PRIMARY KEY (`ID_ACABADO`);

--
-- Indices de la tabla `acondicionamiento`
--
ALTER TABLE `acondicionamiento`
  ADD PRIMARY KEY (`ID_ACONDICIONAMIENTO`);

--
-- Indices de la tabla `beneficiario`
--
ALTER TABLE `beneficiario`
  ADD PRIMARY KEY (`ID_BENEFICIARIOS`);

--
-- Indices de la tabla `casa`
--
ALTER TABLE `casa`
  ADD PRIMARY KEY (`ID_CASA`);

--
-- Indices de la tabla `cerramiento`
--
ALTER TABLE `cerramiento`
  ADD PRIMARY KEY (`ID_CERRAMIENTO`);

--
-- Indices de la tabla `ciudad`
--
ALTER TABLE `ciudad`
  ADD PRIMARY KEY (`id_ciudad`);

--
-- Indices de la tabla `codigo de obra`
--
ALTER TABLE `codigo de obra`
  ADD PRIMARY KEY (`ID_COD_OBRA`);

--
-- Indices de la tabla `comunidad`
--
ALTER TABLE `comunidad`
  ADD PRIMARY KEY (`ID_COMUNIDAD`);

--
-- Indices de la tabla `construccion`
--
ALTER TABLE `construccion`
  ADD PRIMARY KEY (`ID_CONSTRUCCION`),
  ADD UNIQUE KEY `ID_CONSTRUCCION` (`ID_CONSTRUCCION`);

--
-- Indices de la tabla `credenciales`
--
ALTER TABLE `credenciales`
  ADD PRIMARY KEY (`ID_CREDENCIALES`);

--
-- Indices de la tabla `datos_casa`
--
ALTER TABLE `datos_casa`
  ADD PRIMARY KEY (`ID_DATOS_CASA`);

--
-- Indices de la tabla `estado`
--
ALTER TABLE `estado`
  ADD PRIMARY KEY (`id_estado`);

--
-- Indices de la tabla `estructura`
--
ALTER TABLE `estructura`
  ADD PRIMARY KEY (`ID_ESTRUCTURA`);

--
-- Indices de la tabla `modelo constructivo 1`
--
ALTER TABLE `modelo constructivo 1`
  ADD PRIMARY KEY (`ID_MODELO_CONSTRUCTIVO 1`);

--
-- Indices de la tabla `modelo constructivo 2`
--
ALTER TABLE `modelo constructivo 2`
  ADD PRIMARY KEY (`MODELO_CONSTRUCTIVO_2`);

--
-- Indices de la tabla `municipio`
--
ALTER TABLE `municipio`
  ADD PRIMARY KEY (`id_municipio`);

--
-- Indices de la tabla `observaciones`
--
ALTER TABLE `observaciones`
  ADD PRIMARY KEY (`ID_OBSERVACION`);

--
-- Indices de la tabla `parroquias`
--
ALTER TABLE `parroquias`
  ADD PRIMARY KEY (`id_parroquia`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`ID_ROL`);

--
-- Indices de la tabla `ubicacion`
--
ALTER TABLE `ubicacion`
  ADD PRIMARY KEY (`ID_UBICACION`);

--
-- Indices de la tabla `ubi_casa`
--
ALTER TABLE `ubi_casa`
  ADD PRIMARY KEY (`ID_UBI_CASA`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`ID_USUARIO`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `acabado`
--
ALTER TABLE `acabado`
  MODIFY `ID_ACABADO` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `acondicionamiento`
--
ALTER TABLE `acondicionamiento`
  MODIFY `ID_ACONDICIONAMIENTO` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `beneficiario`
--
ALTER TABLE `beneficiario`
  MODIFY `ID_BENEFICIARIOS` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `casa`
--
ALTER TABLE `casa`
  MODIFY `ID_CASA` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cerramiento`
--
ALTER TABLE `cerramiento`
  MODIFY `ID_CERRAMIENTO` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ciudad`
--
ALTER TABLE `ciudad`
  MODIFY `id_ciudad` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `codigo de obra`
--
ALTER TABLE `codigo de obra`
  MODIFY `ID_COD_OBRA` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comunidad`
--
ALTER TABLE `comunidad`
  MODIFY `ID_COMUNIDAD` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `construccion`
--
ALTER TABLE `construccion`
  MODIFY `ID_CONSTRUCCION` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `credenciales`
--
ALTER TABLE `credenciales`
  MODIFY `ID_CREDENCIALES` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `datos_casa`
--
ALTER TABLE `datos_casa`
  MODIFY `ID_DATOS_CASA` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estado`
--
ALTER TABLE `estado`
  MODIFY `id_estado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estructura`
--
ALTER TABLE `estructura`
  MODIFY `ID_ESTRUCTURA` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modelo constructivo 1`
--
ALTER TABLE `modelo constructivo 1`
  MODIFY `ID_MODELO_CONSTRUCTIVO 1` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modelo constructivo 2`
--
ALTER TABLE `modelo constructivo 2`
  MODIFY `MODELO_CONSTRUCTIVO_2` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `municipio`
--
ALTER TABLE `municipio`
  MODIFY `id_municipio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `observaciones`
--
ALTER TABLE `observaciones`
  MODIFY `ID_OBSERVACION` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `parroquias`
--
ALTER TABLE `parroquias`
  MODIFY `id_parroquia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `ID_ROL` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ubicacion`
--
ALTER TABLE `ubicacion`
  MODIFY `ID_UBICACION` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ubi_casa`
--
ALTER TABLE `ubi_casa`
  MODIFY `ID_UBI_CASA` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `ID_USUARIO` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
