/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;
DROP TABLE IF EXISTS `ConfiguracionSistema`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConfiguracionSistema` (
  `idConfig` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(30) NOT NULL COMMENT 'login | navbar | footer | dashboard | general | emails | reportes',
  `nombreSistema` varchar(100) DEFAULT 'Mi Condominio',
  `nombreEmpresa` varchar(100) DEFAULT 'Empresa S.A.',
  `slogan` varchar(255) DEFAULT 'Gestión inteligente',
  `descripcion` text DEFAULT NULL COMMENT 'Descripción corta del sistema',
  `version` varchar(10) DEFAULT '1.0.0',
  `nit` varchar(20) DEFAULT NULL COMMENT 'NIT de la empresa',
  `telefono` varchar(20) DEFAULT NULL COMMENT 'Teléfono de contacto',
  `correoContacto` varchar(100) DEFAULT NULL COMMENT 'Email de contacto',
  `direccion` varchar(255) DEFAULT NULL COMMENT 'Dirección física',
  `sitioWeb` varchar(100) DEFAULT NULL COMMENT 'URL del sitio web',
  `loginTitulo` varchar(100) DEFAULT 'Bienvenido',
  `loginSubtitulo` varchar(150) DEFAULT 'Accede a tu cuenta',
  `loginTextBoton` varchar(50) DEFAULT 'Iniciar Sesión',
  `loginLabelUsuario` varchar(50) DEFAULT 'Email / Usuario',
  `loginPlaceholderUs` varchar(100) DEFAULT 'nombre@ejemplo.com',
  `loginLabelPassword` varchar(50) DEFAULT 'Contraseña',
  `loginLabelRecordar` varchar(50) DEFAULT 'Recordar sesión',
  `loginLinkOlvide` varchar(50) DEFAULT 'Olvidé mi contraseña',
  `loginMensajeBienve` varchar(255) DEFAULT 'Ingresa tus credenciales para continuar',
  `loginTextRegistro` varchar(100) DEFAULT 'Crear una cuenta',
  `navbarNombreSistema` varchar(100) DEFAULT 'Sistema',
  `navbarTextBuscar` varchar(50) DEFAULT 'Buscar...',
  `navbarTextPerfil` varchar(50) DEFAULT 'Perfil de Usuario',
  `navbarTextSalir` varchar(50) DEFAULT 'Cerrar Sesión',
  `navbarTextNotif` varchar(50) DEFAULT 'Notificaciones',
  `navbarTextMarcar` varchar(100) DEFAULT 'Marcar todo como leído',
  `navbarTextHistorial` varchar(100) DEFAULT 'Historial de Notificaciones',
  `navbarTextStatus` varchar(50) DEFAULT 'Actualizar estado',
  `footerTexto` varchar(255) DEFAULT 'Todos los derechos reservados',
  `footerVersion` varchar(20) DEFAULT 'v1.0.0',
  `footerLinkPrivacy` varchar(50) DEFAULT 'Política de privacidad',
  `footerLinkTerminos` varchar(50) DEFAULT 'Términos',
  `footerLinkCookies` varchar(50) DEFAULT 'Cookies',
  `footerAnio` year(4) DEFAULT 2026,
  `emailAsuntoReset` varchar(150) DEFAULT 'Restablecer contraseña',
  `emailAsuntoBienve` varchar(150) DEFAULT 'Bienvenido al sistema',
  `emailAsuntoCuota` varchar(150) DEFAULT 'Recordatorio de cuota pendiente',
  `emailFirma` varchar(255) DEFAULT 'El equipo de administración',
  `colorPrimario` varchar(7) DEFAULT '#2A7BE4',
  `colorSecundario` varchar(7) DEFAULT '#6c757d',
  `colorAccent` varchar(7) DEFAULT '#00d27a',
  `colorFondo` varchar(7) DEFAULT '#ffffff',
  `colorTexto` varchar(7) DEFAULT '#000000',
  `imgLogo` text DEFAULT NULL COMMENT 'Logo principal (navbar, login)',
  `imgLogoOscuro` text DEFAULT NULL COMMENT 'Logo para modo oscuro',
  `imgFavicon` text DEFAULT NULL COMMENT 'Favicon del navegador',
  `imgFondoLogin` text DEFAULT NULL COMMENT 'Fondo del panel izquierdo del login',
  `imgBannerDashboard` text DEFAULT NULL COMMENT 'Banner principal del dashboard',
  `imgAvatarDefault` text DEFAULT NULL COMMENT 'Avatar por defecto para usuarios sin foto',
  `imgFondoEmail` text DEFAULT NULL COMMENT 'Encabezado de imagen en correos',
  `imgLogoEmail` text DEFAULT NULL COMMENT 'Logo pequeño para correos',
  `imgLogoReporte` text DEFAULT NULL COMMENT 'Logo en reportes PDF',
  `imgFondoError404` text DEFAULT NULL COMMENT 'Imagen para página de error 404',
  `zonaHoraria` varchar(50) DEFAULT 'America/Guatemala',
  `formatoFecha` varchar(20) DEFAULT 'd/m/Y',
  `formatoHora` varchar(10) DEFAULT 'H:i',
  `moneda` varchar(10) DEFAULT 'Q',
  `monedaCodigo` varchar(5) DEFAULT 'GTQ',
  `idioma` varchar(10) DEFAULT 'es',
  `diasMora` int(11) DEFAULT 5 COMMENT 'Días de gracia antes de aplicar mora',
  `porcentajeMora` decimal(5,2) DEFAULT 5.00,
  `maxIntentosSesion` int(11) DEFAULT 3 COMMENT 'Intentos fallidos antes de bloquear',
  `bloqueoMinutos` smallint(5) unsigned NOT NULL DEFAULT 15 COMMENT 'Minutos de bloqueo tras agotar los intentos (usuario + IP)',
  `sesionExpiraMin` int(11) DEFAULT 60 COMMENT 'Minutos hasta expirar sesión inactiva',
  `passwordMinimo` tinyint(3) unsigned NOT NULL DEFAULT 12 COMMENT 'Largo mínimo de la contraseña',
  `passwordMayusculas` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Exige mayúsculas y minúsculas',
  `passwordNumeros` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Exige números',
  `passwordSimbolos` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Exige símbolos',
  `passwordVenceDias` smallint(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Días de vigencia (0 = no vence)',
  `passwordHistorial` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Últimas contraseñas que no se pueden repetir (0 = sin control)',
  `estado` int(11) DEFAULT 1,
  `fechaCreacion` datetime DEFAULT current_timestamp(),
  `fechaActualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `actualizadoPor` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`idConfig`),
  KEY `actualizadoPor` (`actualizadoPor`),
  CONSTRAINT `ConfiguracionSistema_ibfk_1` FOREIGN KEY (`actualizadoPor`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `accion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `accion` (
  `id_accion` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(30) NOT NULL COMMENT 'ver, crear, editar…',
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `orden` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Orden de la columna en la matriz de permisos',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_accion`),
  UNIQUE KEY `accion_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `actividad_venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `actividad_venta` (
  `id_actividad` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_oportunidad` int(10) unsigned DEFAULT NULL,
  `id_prospecto` int(10) unsigned DEFAULT NULL,
  `id_cliente` int(10) unsigned DEFAULT NULL,
  `id_responsable` int(10) unsigned NOT NULL,
  `tipo` enum('LLAMADA','EMAIL','REUNION','DEMO','PROPUESTA','SEGUIMIENTO','OTRO') NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_programada` datetime NOT NULL,
  `fecha_realizada` datetime DEFAULT NULL,
  `duracion_min` smallint(5) unsigned DEFAULT NULL,
  `resultado` text DEFAULT NULL,
  `estado` enum('PENDIENTE','EN_CURSO','COMPLETADA','CANCELADA','REPROGRAMADA') NOT NULL DEFAULT 'PENDIENTE',
  `prioridad` enum('BAJA','MEDIA','ALTA','URGENTE') NOT NULL DEFAULT 'MEDIA',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_actividad`),
  KEY `idx_act_empresa` (`id_empresa`),
  KEY `idx_act_oportunidad` (`id_oportunidad`),
  KEY `idx_act_prospecto` (`id_prospecto`),
  KEY `idx_act_responsable` (`id_responsable`),
  KEY `idx_act_fecha` (`fecha_programada`),
  KEY `idx_act_estado` (`estado`),
  KEY `fk_act_cliente` (`id_cliente`),
  KEY `idx_act_pendientes` (`id_responsable`,`estado`,`fecha_programada`),
  CONSTRAINT `fk_act_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE SET NULL,
  CONSTRAINT `fk_act_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_act_oportunidad` FOREIGN KEY (`id_oportunidad`) REFERENCES `oportunidad` (`id_oportunidad`) ON DELETE SET NULL,
  CONSTRAINT `fk_act_prospecto` FOREIGN KEY (`id_prospecto`) REFERENCES `prospecto` (`id_prospecto`) ON DELETE SET NULL,
  CONSTRAINT `fk_act_responsable` FOREIGN KEY (`id_responsable`) REFERENCES `empleado` (`id_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tareas y actividades del equipo de ventas';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asignacion_contrato`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `asignacion_contrato` (
  `id_asignacion` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_contrato` int(10) unsigned NOT NULL,
  `id_empleado` int(10) unsigned NOT NULL,
  `id_sitio` int(10) unsigned DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `rol_en_sitio` varchar(150) DEFAULT NULL COMMENT 'Ej: Supervisor de limpieza, Agente de seguridad',
  `turno` enum('MANANA','TARDE','NOCHE','MIXTO') DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_asignacion`),
  KEY `idx_asign_contrato` (`id_contrato`),
  KEY `idx_asign_empleado` (`id_empleado`),
  KEY `idx_asign_sitio` (`id_sitio`),
  CONSTRAINT `fk_asign_contrato` FOREIGN KEY (`id_contrato`) REFERENCES `contrato_servicio` (`id_contrato`) ON DELETE CASCADE,
  CONSTRAINT `fk_asign_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`),
  CONSTRAINT `fk_asign_sitio` FOREIGN KEY (`id_sitio`) REFERENCES `sitio_trabajo` (`id_sitio`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Personal de Rolsa desplegado en cada contrato y sitio del cliente';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asistencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `asistencia` (
  `id_asistencia` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_empleado` int(10) unsigned NOT NULL,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `fecha` date NOT NULL,
  `hora_entrada` datetime DEFAULT NULL,
  `hora_salida` datetime DEFAULT NULL,
  `tipo` enum('NORMAL','HORAS_EXTRA','DESCANSO','FERIADO') NOT NULL DEFAULT 'NORMAL',
  `estado` enum('PRESENTE','AUSENTE','TARDE','PERMISO','VACACIONES','LICENCIA') NOT NULL DEFAULT 'PRESENTE',
  `minutos_tarde` smallint(5) unsigned NOT NULL DEFAULT 0,
  `horas_extra` decimal(5,2) NOT NULL DEFAULT 0.00,
  `observaciones` varchar(300) DEFAULT NULL,
  `registrado_por` enum('BIOMETRICO','MANUAL','SISTEMA') NOT NULL DEFAULT 'SISTEMA',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_asistencia`),
  UNIQUE KEY `uq_asistencia_dia` (`id_empleado`,`fecha`),
  KEY `idx_asistencia_fecha` (`fecha`),
  KEY `idx_asistencia_empresa` (`id_empresa`),
  KEY `idx_asistencia_estado` (`estado`),
  KEY `idx_asistencia_periodo` (`id_empresa`,`fecha`,`estado`),
  CONSTRAINT `fk_asist_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON DELETE CASCADE,
  CONSTRAINT `fk_asist_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de asistencia diaria — alta frecuencia de escritura';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auditoria_acceso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auditoria_acceso` (
  `id_auditoria` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int(10) unsigned DEFAULT NULL COMMENT 'NULL si login fallido con usuario inexistente',
  `username_intento` varchar(60) NOT NULL,
  `accion` enum('LOGIN_OK','LOGIN_FAIL','LOGOUT','CAMBIO_PASSWORD','RESET_PASSWORD','BLOQUEO','DESACTIVADO','LOGIN_FAIL_2FA','SESION_CERRADA','DESBLOQUEO') NOT NULL,
  `ip_address` varchar(45) NOT NULL COMMENT 'IPv4 o IPv6',
  `user_agent` varchar(500) DEFAULT NULL,
  `detalle` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_audit_usuario` (`id_usuario`),
  KEY `idx_audit_fecha` (`created_at`),
  KEY `idx_audit_accion` (`accion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de accesos y eventos de seguridad';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auditoria_cambio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auditoria_cambio` (
  `id_cambio` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int(10) unsigned DEFAULT NULL,
  `id_empresa` tinyint(3) unsigned DEFAULT NULL,
  `tabla_afectada` varchar(100) NOT NULL,
  `id_registro` varchar(36) NOT NULL COMMENT 'PK del registro afectado como string',
  `accion` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `datos_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_anteriores`)),
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_nuevos`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_cambio`),
  KEY `idx_cambio_tabla` (`tabla_afectada`,`id_registro`),
  KEY `idx_cambio_usuario` (`id_usuario`),
  KEY `idx_cambio_fecha` (`created_at`),
  KEY `idx_cambio_empresa` (`id_empresa`,`id_cambio`),
  KEY `idx_cambio_empresa_tabla` (`id_empresa`,`tabla_afectada`,`id_registro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auditoría completa de cambios en datos críticos';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bodega`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bodega` (
  `id_bodega` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_sucursal` smallint(5) unsigned DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `ubicacion` varchar(300) DEFAULT NULL,
  `responsable_id` int(10) unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_bodega`),
  KEY `idx_bodega_empresa` (`id_empresa`),
  KEY `fk_bodega_sucursal` (`id_sucursal`),
  KEY `fk_bodega_responsable` (`responsable_id`),
  CONSTRAINT `fk_bodega_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_bodega_responsable` FOREIGN KEY (`responsable_id`) REFERENCES `empleado` (`id_empleado`) ON DELETE SET NULL,
  CONSTRAINT `fk_bodega_sucursal` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursal` (`id_sucursal`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campana`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campana` (
  `id_campana` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_linea` tinyint(3) unsigned DEFAULT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` enum('EMAIL','WHATSAPP','LLAMADAS','REDES_SOCIALES','EVENTO','REFERIDOS','OTRO') NOT NULL,
  `objetivo` enum('AWARENESS','LEADS','REACTIVACION','FIDELIZACION','UPSELL') NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `presupuesto` decimal(15,4) DEFAULT NULL,
  `gasto_real` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `meta_leads` smallint(5) unsigned DEFAULT NULL,
  `leads_generados` smallint(5) unsigned NOT NULL DEFAULT 0,
  `estado` enum('PLANIFICADA','ACTIVA','PAUSADA','FINALIZADA','CANCELADA') NOT NULL DEFAULT 'PLANIFICADA',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_campana`),
  KEY `idx_campana_empresa` (`id_empresa`),
  KEY `idx_campana_estado` (`estado`),
  KEY `idx_campana_fechas` (`fecha_inicio`,`fecha_fin`),
  KEY `fk_campana_linea` (`id_linea`),
  CONSTRAINT `fk_campana_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_campana_linea` FOREIGN KEY (`id_linea`) REFERENCES `linea_negocio` (`id_linea`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campana_contacto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campana_contacto` (
  `id_contacto_campana` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_campana` int(10) unsigned NOT NULL,
  `tipo_contacto` enum('PROSPECTO','CLIENTE') NOT NULL,
  `id_prospecto` int(10) unsigned DEFAULT NULL,
  `id_cliente` int(10) unsigned DEFAULT NULL,
  `estado_envio` enum('PENDIENTE','ENVIADO','ENTREGADO','ABIERTO','CLICK','REBOTADO','BAJA') NOT NULL DEFAULT 'PENDIENTE',
  `fecha_envio` datetime DEFAULT NULL,
  `fecha_apertura` datetime DEFAULT NULL,
  `resultado` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id_contacto_campana`),
  KEY `idx_cc_campana` (`id_campana`),
  KEY `idx_cc_prospecto` (`id_prospecto`),
  KEY `idx_cc_cliente` (`id_cliente`),
  CONSTRAINT `fk_cc_campana` FOREIGN KEY (`id_campana`) REFERENCES `campana` (`id_campana`) ON DELETE CASCADE,
  CONSTRAINT `fk_cc_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE,
  CONSTRAINT `fk_cc_prospecto` FOREIGN KEY (`id_prospecto`) REFERENCES `prospecto` (`id_prospecto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Contactos incluidos en cada campaña con estado de envío';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cargo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cargo` (
  `id_cargo` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_depto_org` smallint(5) unsigned DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `nivel_jerarquico` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '1=Operativo, 2=Supervisión, 3=Gerencia, 4=Dirección',
  `salario_min` decimal(15,4) DEFAULT NULL,
  `salario_max` decimal(15,4) DEFAULT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `requiere_vehiculo` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_cargo`),
  KEY `idx_cargo_empresa` (`id_empresa`),
  KEY `idx_cargo_depto` (`id_depto_org`),
  KEY `fk_cargo_moneda` (`moneda`),
  CONSTRAINT `fk_cargo_depto` FOREIGN KEY (`id_depto_org`) REFERENCES `departamento_org` (`id_depto_org`) ON DELETE SET NULL,
  CONSTRAINT `fk_cargo_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_cargo_moneda` FOREIGN KEY (`moneda`) REFERENCES `moneda` (`codigo`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categoria_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria_producto` (
  `id_categoria` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_padre` smallint(5) unsigned DEFAULT NULL,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_categoria`),
  KEY `idx_cat_empresa` (`id_empresa`),
  KEY `fk_cat_padre` (`id_padre`),
  CONSTRAINT `fk_cat_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_cat_padre` FOREIGN KEY (`id_padre`) REFERENCES `categoria_producto` (`id_categoria`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categoria_ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria_ticket` (
  `id_categoria` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `prioridad_default` enum('BAJA','MEDIA','ALTA','CRITICA') NOT NULL DEFAULT 'MEDIA',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_categoria`),
  KEY `idx_cattic_empresa` (`id_empresa`),
  CONSTRAINT `fk_cattic_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `centro_costo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `centro_costo` (
  `id_centro` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_centro`),
  UNIQUE KEY `uq_centro_empresa_codigo` (`id_empresa`,`codigo`),
  CONSTRAINT `fk_centro_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cliente` (
  `id_cliente` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL COMMENT 'Empresa de Rolsa que atiende al cliente',
  `id_industria` smallint(5) unsigned DEFAULT NULL,
  `id_pais` tinyint(3) unsigned NOT NULL,
  `id_municipio` smallint(5) unsigned DEFAULT NULL,
  `razon_social` varchar(250) NOT NULL,
  `nombre_comercial` varchar(150) DEFAULT NULL,
  `nit` varchar(20) DEFAULT NULL,
  `tipo_persona` enum('JURIDICA','NATURAL') NOT NULL DEFAULT 'JURIDICA',
  `email_principal` varchar(150) DEFAULT NULL,
  `telefono_principal` varchar(20) DEFAULT NULL,
  `sitio_web` varchar(200) DEFAULT NULL,
  `direccion_fiscal` varchar(300) DEFAULT NULL,
  `segmento` enum('GRANDE','MEDIANA','PEQUENA','GOBIERNO','ONG') DEFAULT NULL,
  `categoria` enum('A','B','C') DEFAULT NULL COMMENT 'Segmentación por valor / volumen',
  `moneda_facturacion` char(3) NOT NULL DEFAULT 'GTQ',
  `dias_credito` tinyint(3) unsigned NOT NULL DEFAULT 30,
  `limite_credito` decimal(15,4) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_cliente`),
  KEY `idx_cliente_empresa` (`id_empresa`),
  KEY `idx_cliente_pais` (`id_pais`),
  KEY `idx_cliente_nit` (`nit`),
  KEY `idx_cliente_activo` (`activo`,`deleted_at`),
  KEY `fk_cliente_industria` (`id_industria`),
  KEY `fk_cliente_moneda` (`moneda_facturacion`),
  KEY `idx_cliente_municipio` (`id_municipio`),
  FULLTEXT KEY `ftx_cliente_busqueda` (`razon_social`,`nombre_comercial`,`email_principal`),
  CONSTRAINT `fk_cliente_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_cliente_industria` FOREIGN KEY (`id_industria`) REFERENCES `industria` (`id_industria`) ON DELETE SET NULL,
  CONSTRAINT `fk_cliente_moneda` FOREIGN KEY (`moneda_facturacion`) REFERENCES `moneda` (`codigo`) ON UPDATE CASCADE,
  CONSTRAINT `fk_cliente_pais` FOREIGN KEY (`id_pais`) REFERENCES `pais` (`id_pais`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Socios comerciales — multi-empresa, multi-país';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cobertura_rotativo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cobertura_rotativo` (
  `id_cobertura` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_rotativo` int(10) unsigned NOT NULL COMMENT 'Empleado rotativo que cubre',
  `id_titular` int(10) unsigned DEFAULT NULL COMMENT 'Empleado cubierto (vacío si el puesto está vacante)',
  `motivo` enum('VACACIONES','SUSPENSION','INCAPACIDAD','PERMISO','LICENCIA','AUSENCIA','VACANTE','OTRO') NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `paga_fines_semana` tinyint(1) NOT NULL DEFAULT 1,
  `dias` decimal(5,2) NOT NULL,
  `tarifa_dia` decimal(15,4) NOT NULL,
  `total` decimal(15,4) NOT NULL,
  `estado` enum('VIGENTE','ANULADA') NOT NULL DEFAULT 'VIGENTE',
  `observaciones` varchar(300) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `anulada_por` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_cobertura`),
  KEY `cobertura_rotativo_id_empresa_index` (`id_empresa`),
  KEY `cobertura_rotativo_id_rotativo_index` (`id_rotativo`),
  KEY `cobertura_rotativo_id_titular_index` (`id_titular`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Coberturas del personal rotativo: quién cubre a quién, cuántos días y a qué tarifa';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `concepto_nomina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `concepto_nomina` (
  `id_concepto` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tipo` enum('INGRESO','DEDUCCION') NOT NULL,
  `afecta_igss` tinyint(1) NOT NULL DEFAULT 0,
  `afecta_isr` tinyint(1) NOT NULL DEFAULT 0,
  `es_fijo` tinyint(1) NOT NULL DEFAULT 1,
  `formula` varchar(500) DEFAULT NULL COMMENT 'Expresión para cálculo automático',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_concepto`),
  UNIQUE KEY `uq_concepto_codigo` (`id_empresa`,`codigo`),
  CONSTRAINT `fk_concepto_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bonificaciones, horas extra, IGSS, ISR, descuentos de préstamo';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contacto_cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacto_cliente` (
  `id_contacto` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_cliente` int(10) unsigned NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `cargo` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `es_contacto_principal` tinyint(1) NOT NULL DEFAULT 0,
  `recibe_facturas` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_contacto`),
  KEY `idx_contacto_cliente` (`id_cliente`),
  CONSTRAINT `fk_contacto_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contrato_laboral`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contrato_laboral` (
  `id_contrato` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empleado` int(10) unsigned NOT NULL,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `numero_contrato` varchar(50) NOT NULL,
  `tipo` enum('INDEFINIDO','TIEMPO_DEFINIDO','PRUEBA','OBRA','OUTSOURCING') NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL COMMENT 'NULL = indefinido',
  `salario_base` decimal(15,4) NOT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `jornada` enum('COMPLETA','PARCIAL','NOCTURNA','MIXTA') NOT NULL DEFAULT 'COMPLETA',
  `horas_semana` tinyint(3) unsigned NOT NULL DEFAULT 44 COMMENT 'Legal GT: 44 horas',
  `url_contrato` varchar(500) DEFAULT NULL,
  `estado` enum('VIGENTE','VENCIDO','RENOVADO','RESCINDIDO') NOT NULL DEFAULT 'VIGENTE',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_contrato`),
  UNIQUE KEY `uq_contrato_numero` (`id_empresa`,`numero_contrato`),
  KEY `idx_contrato_empleado` (`id_empleado`),
  KEY `idx_contrato_estado` (`estado`),
  KEY `idx_contrato_vence` (`fecha_fin`),
  CONSTRAINT `fk_contrato_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON DELETE CASCADE,
  CONSTRAINT `fk_contrato_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contrato_servicio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contrato_servicio` (
  `id_contrato` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL COMMENT 'Empresa de Rolsa firmante',
  `id_cliente` int(10) unsigned NOT NULL,
  `id_vendedor` int(10) unsigned DEFAULT NULL COMMENT 'Empleado de ventas responsable',
  `numero_contrato` varchar(60) NOT NULL,
  `nombre_proyecto` varchar(200) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL COMMENT 'NULL = indefinido',
  `fecha_firma` date DEFAULT NULL,
  `valor_mensual` decimal(15,4) NOT NULL,
  `valor_total_estimado` decimal(15,4) DEFAULT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `periodicidad_factura` enum('MENSUAL','QUINCENAL','SEMANAL','UNICA','ANUAL') NOT NULL DEFAULT 'MENSUAL',
  `dia_facturacion` tinyint(3) unsigned DEFAULT NULL COMMENT 'Día del mes en que se factura',
  `url_contrato` varchar(500) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `estado` enum('BORRADOR','VIGENTE','SUSPENDIDO','VENCIDO','CANCELADO','RENOVADO') NOT NULL DEFAULT 'BORRADOR',
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_contrato`),
  UNIQUE KEY `uq_contrato_num` (`id_empresa`,`numero_contrato`),
  KEY `idx_contrato_cliente` (`id_cliente`),
  KEY `idx_contrato_empresa` (`id_empresa`),
  KEY `idx_contrato_vendedor` (`id_vendedor`),
  KEY `idx_contrato_estado` (`estado`),
  KEY `idx_contrato_vence` (`fecha_fin`),
  KEY `fk_cs_moneda` (`moneda`),
  KEY `idx_cs_vencimiento_estado` (`fecha_fin`,`estado`),
  CONSTRAINT `fk_cs_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`),
  CONSTRAINT `fk_cs_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_cs_moneda` FOREIGN KEY (`moneda`) REFERENCES `moneda` (`codigo`) ON UPDATE CASCADE,
  CONSTRAINT `fk_cs_vendedor` FOREIGN KEY (`id_vendedor`) REFERENCES `empleado` (`id_empleado`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Contratos de servicio entre empresa Rolsa y cliente';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contrato_servicio_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contrato_servicio_detalle` (
  `id_detalle` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_contrato` int(10) unsigned NOT NULL,
  `id_tipo_servicio` smallint(5) unsigned NOT NULL,
  `id_sitio` int(10) unsigned DEFAULT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT 1.00,
  `precio_unitario` decimal(15,4) NOT NULL,
  `descuento_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(15,4) NOT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `idx_csd_contrato` (`id_contrato`),
  KEY `fk_csd_servicio` (`id_tipo_servicio`),
  KEY `fk_csd_sitio` (`id_sitio`),
  CONSTRAINT `fk_csd_contrato` FOREIGN KEY (`id_contrato`) REFERENCES `contrato_servicio` (`id_contrato`) ON DELETE CASCADE,
  CONSTRAINT `fk_csd_servicio` FOREIGN KEY (`id_tipo_servicio`) REFERENCES `tipo_servicio` (`id_tipo_servicio`),
  CONSTRAINT `fk_csd_sitio` FOREIGN KEY (`id_sitio`) REFERENCES `sitio_trabajo` (`id_sitio`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cuenta_contable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuenta_contable` (
  `id_cuenta` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_padre` smallint(5) unsigned DEFAULT NULL,
  `codigo` varchar(20) NOT NULL COMMENT 'Ej: 1.1.01.001',
  `nombre` varchar(200) NOT NULL,
  `tipo` enum('ACTIVO','PASIVO','PATRIMONIO','INGRESO','GASTO','COSTO') NOT NULL,
  `naturaleza` enum('DEUDORA','ACREEDORA') NOT NULL,
  `nivel` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `permite_movimiento` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 = cuenta de agrupación',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_cuenta`),
  UNIQUE KEY `uq_cuenta_empresa_codigo` (`id_empresa`,`codigo`),
  KEY `idx_cuenta_empresa` (`id_empresa`),
  KEY `idx_cuenta_padre` (`id_padre`),
  CONSTRAINT `fk_cuenta_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_cuenta_padre` FOREIGN KEY (`id_padre`) REFERENCES `cuenta_contable` (`id_cuenta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departamento_org`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `departamento_org` (
  `id_depto_org` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_padre` smallint(5) unsigned DEFAULT NULL COMMENT 'Jerarquía de departamentos',
  `nombre` varchar(150) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `centro_costo` varchar(20) DEFAULT NULL COMMENT 'Código de centro de costo contable',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_depto_org`),
  KEY `idx_depto_org_empresa` (`id_empresa`),
  KEY `idx_depto_org_padre` (`id_padre`),
  CONSTRAINT `fk_depto_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_depto_padre` FOREIGN KEY (`id_padre`) REFERENCES `departamento_org` (`id_depto_org`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Estructura organizacional jerárquica de la empresa';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalle_factura`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_factura` (
  `id_linea` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_factura` int(10) unsigned NOT NULL,
  `id_tipo_servicio` smallint(5) unsigned DEFAULT NULL,
  `descripcion` varchar(300) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT 1.00,
  `precio_unitario` decimal(15,4) NOT NULL,
  `descuento` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `subtotal` decimal(15,4) NOT NULL,
  `es_afecto_iva` tinyint(1) NOT NULL DEFAULT 1,
  `id_cuenta` smallint(5) unsigned DEFAULT NULL,
  `id_centro` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_linea`),
  KEY `idx_dfact_factura` (`id_factura`),
  KEY `fk_dfact_servicio` (`id_tipo_servicio`),
  KEY `fk_dfact_cuenta` (`id_cuenta`),
  KEY `idx_dfact_contabilidad` (`id_centro`,`id_cuenta`),
  CONSTRAINT `fk_dfact_centro` FOREIGN KEY (`id_centro`) REFERENCES `centro_costo` (`id_centro`) ON DELETE SET NULL,
  CONSTRAINT `fk_dfact_cuenta` FOREIGN KEY (`id_cuenta`) REFERENCES `cuenta_contable` (`id_cuenta`) ON DELETE SET NULL,
  CONSTRAINT `fk_dfact_factura` FOREIGN KEY (`id_factura`) REFERENCES `factura` (`id_factura`) ON DELETE CASCADE,
  CONSTRAINT `fk_dfact_servicio` FOREIGN KEY (`id_tipo_servicio`) REFERENCES `tipo_servicio` (`id_tipo_servicio`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalle_nomina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_nomina` (
  `id_detalle` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_periodo` smallint(5) unsigned NOT NULL,
  `id_empleado` int(10) unsigned NOT NULL,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `cargo_snapshot` varchar(150) DEFAULT NULL,
  `salario_base` decimal(15,4) NOT NULL,
  `dias_trabajados` decimal(5,2) NOT NULL DEFAULT 0.00,
  `horas_extra` decimal(8,2) NOT NULL DEFAULT 0.00,
  `total_ingresos` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `total_deducciones` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `liquido_pagar` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `cuota_igss_emp` decimal(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Cuota empleado IGSS',
  `cuota_igss_pat` decimal(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Cuota patronal IGSS',
  `isr_retenido` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `estado_pago` enum('CALCULADO','APROBADO','PAGADO','RECHAZADO') NOT NULL DEFAULT 'CALCULADO',
  `numero_cheque` varchar(50) DEFAULT NULL,
  `banco_destino` varchar(100) DEFAULT NULL,
  `cuenta_destino` varchar(30) DEFAULT NULL,
  `fecha_pago` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_detalle`),
  UNIQUE KEY `uq_nomina_periodo_emp` (`id_periodo`,`id_empleado`),
  KEY `idx_nomina_empleado` (`id_empleado`),
  KEY `idx_nomina_empresa` (`id_empresa`),
  KEY `idx_nomina_estado` (`estado_pago`),
  CONSTRAINT `fk_nomina_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`),
  CONSTRAINT `fk_nomina_periodo` FOREIGN KEY (`id_periodo`) REFERENCES `periodo_nomina` (`id_periodo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Recibo de pago calculado por período — Snapshot de datos históricos';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalle_nomina_concepto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_nomina_concepto` (
  `id_linea` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_detalle` int(10) unsigned NOT NULL,
  `id_concepto` smallint(5) unsigned NOT NULL,
  `tipo` enum('INGRESO','DEDUCCION') NOT NULL,
  `monto` decimal(15,4) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id_linea`),
  KEY `idx_dnc_detalle` (`id_detalle`),
  KEY `idx_dnc_concepto` (`id_concepto`),
  CONSTRAINT `fk_dnc_concepto` FOREIGN KEY (`id_concepto`) REFERENCES `concepto_nomina` (`id_concepto`),
  CONSTRAINT `fk_dnc_detalle` FOREIGN KEY (`id_detalle`) REFERENCES `detalle_nomina` (`id_detalle`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalle_orden_compra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_orden_compra` (
  `id_linea` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_oc` int(10) unsigned NOT NULL,
  `id_producto` int(10) unsigned NOT NULL,
  `id_centro` smallint(5) unsigned DEFAULT NULL,
  `id_cuenta` smallint(5) unsigned DEFAULT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `cantidad_pedida` decimal(12,4) NOT NULL,
  `cantidad_recibida` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `precio_unitario` decimal(15,4) NOT NULL,
  `descuento` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `subtotal` decimal(15,4) NOT NULL,
  PRIMARY KEY (`id_linea`),
  KEY `idx_doc_oc` (`id_oc`),
  KEY `idx_doc_producto` (`id_producto`),
  KEY `fk_doc_cuenta` (`id_cuenta`),
  KEY `idx_doc_contabilidad` (`id_centro`,`id_cuenta`),
  CONSTRAINT `fk_doc_centro` FOREIGN KEY (`id_centro`) REFERENCES `centro_costo` (`id_centro`) ON DELETE SET NULL,
  CONSTRAINT `fk_doc_cuenta` FOREIGN KEY (`id_cuenta`) REFERENCES `cuenta_contable` (`id_cuenta`) ON DELETE SET NULL,
  CONSTRAINT `fk_doc_oc` FOREIGN KEY (`id_oc`) REFERENCES `orden_compra` (`id_oc`) ON DELETE CASCADE,
  CONSTRAINT `fk_doc_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalle_recepcion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_recepcion` (
  `id_detalle_rec` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_recepcion` int(10) unsigned NOT NULL,
  `id_linea` int(10) unsigned NOT NULL,
  `id_producto` int(10) unsigned NOT NULL,
  `cantidad_recibida` decimal(12,4) NOT NULL,
  `costo_unitario` decimal(15,4) NOT NULL,
  `subtotal` decimal(15,4) NOT NULL,
  PRIMARY KEY (`id_detalle_rec`),
  KEY `idx_drec_recepcion` (`id_recepcion`),
  KEY `idx_drec_linea` (`id_linea`),
  KEY `idx_drec_producto` (`id_producto`),
  CONSTRAINT `fk_drec_linea` FOREIGN KEY (`id_linea`) REFERENCES `detalle_orden_compra` (`id_linea`),
  CONSTRAINT `fk_drec_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`),
  CONSTRAINT `fk_drec_recepcion` FOREIGN KEY (`id_recepcion`) REFERENCES `recepcion_mercaderia` (`id_recepcion`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle de productos recibidos por recepción';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `division_geografica`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `division_geografica` (
  `id_division` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_pais` tinyint(3) unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('departamento','estado','provincia','distrito') NOT NULL DEFAULT 'departamento',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_division`),
  KEY `idx_division_pais` (`id_pais`),
  CONSTRAINT `fk_division_pais` FOREIGN KEY (`id_pais`) REFERENCES `pais` (`id_pais`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Departamentos, estados o provincias por país';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ejecucion_presupuesto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ejecucion_presupuesto` (
  `id_ejecucion` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `documento` enum('ORDEN_COMPRA','FACTURA') NOT NULL,
  `id_documento` int(10) unsigned NOT NULL,
  `id_presupuesto` int(10) unsigned DEFAULT NULL,
  `mes` tinyint(3) unsigned DEFAULT NULL,
  `monto` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `revertido_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_ejecucion`),
  KEY `idx_ejecucion_documento` (`documento`,`id_documento`),
  KEY `idx_ejecucion_presupuesto` (`id_presupuesto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `empleado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `empleado` (
  `id_empleado` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_sucursal` smallint(5) unsigned DEFAULT NULL,
  `id_cargo` smallint(5) unsigned DEFAULT NULL,
  `id_depto_org` smallint(5) unsigned DEFAULT NULL,
  `id_supervisor` int(10) unsigned DEFAULT NULL COMMENT 'Jefe directo (auto-referencia)',
  `primer_nombre` varchar(80) NOT NULL,
  `segundo_nombre` varchar(80) DEFAULT NULL,
  `primer_apellido` varchar(80) NOT NULL,
  `segundo_apellido` varchar(80) DEFAULT NULL,
  `apellido_casada` varchar(80) DEFAULT NULL,
  `dpi_nit` varchar(20) NOT NULL COMMENT 'DPI en GT, DUI en SV, DNI en HN/NI/CR',
  `tipo_doc_id` enum('DPI','DUI','DNI','PASAPORTE','OTRO') NOT NULL DEFAULT 'DPI',
  `nit_personal` varchar(20) DEFAULT NULL,
  `igss_afiliacion` varchar(20) DEFAULT NULL COMMENT 'IGSS-GT / IHSS-HN / CCSS-CR / ISSS-SV',
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` enum('M','F','OTRO','NO_ESPECIFICA') DEFAULT NULL,
  `estado_civil` enum('SOLTERO','CASADO','UNION_LIBRE','DIVORCIADO','VIUDO') DEFAULT NULL,
  `nacionalidad` varchar(80) DEFAULT NULL,
  `email_personal` varchar(150) DEFAULT NULL,
  `email_corporativo` varchar(150) DEFAULT NULL,
  `telefono_personal` varchar(20) DEFAULT NULL,
  `telefono_emergencia` varchar(20) DEFAULT NULL,
  `contacto_emergencia` varchar(200) DEFAULT NULL,
  `id_municipio` smallint(5) unsigned DEFAULT NULL,
  `direccion` varchar(300) DEFAULT NULL,
  `codigo_empleado` varchar(30) DEFAULT NULL COMMENT 'Código interno de la empresa',
  `fecha_ingreso` date NOT NULL,
  `fecha_baja` date DEFAULT NULL,
  `tipo_contrato` enum('INDEFINIDO','TIEMPO_DEFINIDO','PRUEBA','OBRA','OUTSOURCING') NOT NULL DEFAULT 'INDEFINIDO',
  `es_rotativo` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Cubre a otros empleados y cobra por día',
  `tarifa_dia` decimal(15,4) DEFAULT NULL COMMENT 'Tarifa diaria habitual del rotativo',
  `modalidad_trabajo` enum('PRESENCIAL','REMOTO','HIBRIDO') NOT NULL DEFAULT 'PRESENCIAL',
  `estado` enum('ACTIVO','INACTIVO','SUSPENDIDO','VACACIONES','LICENCIA','BAJA') NOT NULL DEFAULT 'ACTIVO',
  `motivo_baja` varchar(300) DEFAULT NULL,
  `foto_url` varchar(500) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL COMMENT 'Soft delete',
  PRIMARY KEY (`id_empleado`),
  UNIQUE KEY `uq_empleado_doc_empresa` (`id_empresa`,`dpi_nit`),
  UNIQUE KEY `uq_empleado_codigo` (`id_empresa`,`codigo_empleado`),
  KEY `idx_emp_empresa` (`id_empresa`),
  KEY `idx_emp_cargo` (`id_cargo`),
  KEY `idx_emp_sucursal` (`id_sucursal`),
  KEY `idx_emp_supervisor` (`id_supervisor`),
  KEY `idx_emp_estado` (`estado`,`deleted_at`),
  KEY `idx_emp_ingreso` (`fecha_ingreso`),
  KEY `fk_emp_depto` (`id_depto_org`),
  KEY `fk_emp_municipio` (`id_municipio`),
  KEY `idx_emp_empresa_estado` (`id_empresa`,`estado`,`fecha_ingreso`),
  CONSTRAINT `fk_emp_cargo` FOREIGN KEY (`id_cargo`) REFERENCES `cargo` (`id_cargo`) ON DELETE SET NULL,
  CONSTRAINT `fk_emp_depto` FOREIGN KEY (`id_depto_org`) REFERENCES `departamento_org` (`id_depto_org`) ON DELETE SET NULL,
  CONSTRAINT `fk_emp_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_emp_municipio` FOREIGN KEY (`id_municipio`) REFERENCES `municipio` (`id_municipio`) ON DELETE SET NULL,
  CONSTRAINT `fk_emp_sucursal` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursal` (`id_sucursal`) ON DELETE SET NULL,
  CONSTRAINT `fk_emp_supervisor` FOREIGN KEY (`id_supervisor`) REFERENCES `empleado` (`id_empleado`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro maestro de colaboradores — Multi-empresa, multi-país';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `empleado_documento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `empleado_documento` (
  `id_doc` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empleado` int(10) unsigned NOT NULL,
  `tipo_documento` enum('CONTRATO','DPI','TITULO','CURRICULUM','CERTIFICADO','ANTECEDENTES','OTRO') NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `url_archivo` varchar(500) NOT NULL,
  `fecha_emision` date DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL COMMENT 'Para documentos con vigencia',
  `observaciones` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_doc`),
  KEY `idx_doc_empleado` (`id_empleado`),
  KEY `idx_doc_vencimiento` (`fecha_vencimiento`),
  CONSTRAINT `fk_doc_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `empresa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresa` (
  `id_empresa` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `nombre_legal` varchar(200) NOT NULL,
  `nombre_comercial` varchar(100) NOT NULL,
  `nit` varchar(20) NOT NULL COMMENT 'RTU en Guatemala, NIT en otros países',
  `id_pais` tinyint(3) unsigned NOT NULL,
  `moneda_base` char(3) NOT NULL DEFAULT 'GTQ',
  `tasa_iva` decimal(5,2) NOT NULL DEFAULT 12.00 COMMENT 'Tasa IVA aplicable: GT=12, HN=15, NI=15, CR=13, etc.',
  `iva_incluido_en_precio` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Si TRUE: el precio digitado ya incluye IVA. Si FALSE: el precio es base y se suma IVA.',
  `email_corporativo` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(300) DEFAULT NULL,
  `id_municipio` smallint(5) unsigned DEFAULT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `fecha_fundacion` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_empresa`),
  UNIQUE KEY `uq_empresa_nit_pais` (`nit`,`id_pais`),
  KEY `idx_empresa_pais` (`id_pais`),
  KEY `fk_empresa_moneda` (`moneda_base`),
  KEY `fk_empresa_muni` (`id_municipio`),
  CONSTRAINT `fk_empresa_moneda` FOREIGN KEY (`moneda_base`) REFERENCES `moneda` (`codigo`) ON UPDATE CASCADE,
  CONSTRAINT `fk_empresa_muni` FOREIGN KEY (`id_municipio`) REFERENCES `municipio` (`id_municipio`) ON DELETE SET NULL,
  CONSTRAINT `fk_empresa_pais` FOREIGN KEY (`id_pais`) REFERENCES `pais` (`id_pais`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Empresas del conglomerado: Can Am, Adinser, ADESA, etc.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `escalacion_ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `escalacion_ticket` (
  `id_escalacion` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_ticket` int(10) unsigned NOT NULL,
  `escalado_por` int(10) unsigned DEFAULT NULL,
  `escalado_a` int(10) unsigned NOT NULL,
  `id_usuario` int(10) unsigned DEFAULT NULL,
  `motivo` varchar(500) NOT NULL,
  `nivel` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_escalacion`),
  KEY `idx_esc_ticket` (`id_ticket`),
  KEY `fk_esc_por` (`escalado_por`),
  KEY `fk_esc_a` (`escalado_a`),
  KEY `idx_escalacion_usuario` (`id_usuario`),
  CONSTRAINT `fk_esc_a` FOREIGN KEY (`escalado_a`) REFERENCES `empleado` (`id_empleado`),
  CONSTRAINT `fk_esc_por` FOREIGN KEY (`escalado_por`) REFERENCES `empleado` (`id_empleado`),
  CONSTRAINT `fk_esc_ticket` FOREIGN KEY (`id_ticket`) REFERENCES `ticket` (`id_ticket`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `etapa_funnel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `etapa_funnel` (
  `id_etapa` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `orden` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `color_hex` char(7) DEFAULT NULL COMMENT 'Color para el tablero Kanban: #FF5733',
  `probabilidad_cierre` tinyint(3) unsigned DEFAULT NULL COMMENT '0-100%',
  `es_ganada` tinyint(1) NOT NULL DEFAULT 0,
  `es_perdida` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_etapa`),
  KEY `idx_etapa_empresa` (`id_empresa`),
  CONSTRAINT `fk_etapa_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pipeline de ventas: Lead → Calificado → Propuesta → Negociación → Ganado';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evaluacion_satisfaccion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluacion_satisfaccion` (
  `id_evaluacion` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_cliente` int(10) unsigned NOT NULL,
  `id_contrato` int(10) unsigned DEFAULT NULL,
  `id_ticket` int(10) unsigned DEFAULT NULL,
  `tipo` enum('NPS','CSAT','CES','ANUAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CSAT',
  `puntuacion` tinyint(3) unsigned NOT NULL,
  `comentarios` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `canal` enum('EMAIL','WHATSAPP','TELEFONO','APP','WEB') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EMAIL',
  `fecha_respuesta` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_evaluacion`),
  KEY `idx_eval_cliente` (`id_cliente`),
  KEY `idx_eval_empresa` (`id_empresa`),
  KEY `idx_eval_fecha` (`fecha_respuesta`),
  KEY `fk_eval_contrato` (`id_contrato`),
  KEY `fk_eval_ticket` (`id_ticket`),
  CONSTRAINT `fk_eval_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`),
  CONSTRAINT `fk_eval_contrato` FOREIGN KEY (`id_contrato`) REFERENCES `contrato_servicio` (`id_contrato`) ON DELETE SET NULL,
  CONSTRAINT `fk_eval_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_eval_ticket` FOREIGN KEY (`id_ticket`) REFERENCES `ticket` (`id_ticket`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `factura`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `factura` (
  `id_factura` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_cliente` int(10) unsigned NOT NULL,
  `id_contrato` int(10) unsigned DEFAULT NULL,
  `id_serie` smallint(5) unsigned NOT NULL,
  `numero_factura` int(10) unsigned NOT NULL,
  `numero_completo` varchar(30) NOT NULL COMMENT 'Serie + Número formateado',
  `tipo` enum('FACTURA','CREDITO_FISCAL','NOTA_CREDITO','NOTA_DEBITO') NOT NULL DEFAULT 'FACTURA',
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `periodo_servicio_inicio` date DEFAULT NULL,
  `periodo_servicio_fin` date DEFAULT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `subtotal` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `descuento` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `base_imponible` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `iva` decimal(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'IVA 12% Guatemala',
  `total` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `total_pagado` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `monto_condonado` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `condonado_por` int(10) unsigned DEFAULT NULL,
  `fecha_condonacion` datetime DEFAULT NULL,
  `saldo_pendiente` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `estado` enum('BORRADOR','EMITIDA','ENVIADA','PARCIAL','PAGADA','VENCIDA','ANULADA') NOT NULL DEFAULT 'BORRADOR',
  `uuid_fel` char(36) DEFAULT NULL COMMENT 'UUID de la SAT Guatemala',
  `numero_autorizacion_fel` varchar(50) DEFAULT NULL,
  `fecha_certificacion_fel` datetime DEFAULT NULL,
  `url_pdf_fel` varchar(500) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `anulada_por` int(10) unsigned DEFAULT NULL,
  `fecha_anulacion` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_factura`),
  UNIQUE KEY `uq_factura_serie_numero` (`id_serie`,`numero_factura`),
  UNIQUE KEY `uq_factura_uuid_fel` (`uuid_fel`),
  KEY `idx_factura_cliente` (`id_cliente`),
  KEY `idx_factura_empresa` (`id_empresa`),
  KEY `idx_factura_contrato` (`id_contrato`),
  KEY `idx_factura_estado` (`estado`),
  KEY `idx_factura_vence` (`fecha_vencimiento`,`estado`),
  KEY `idx_factura_emision` (`fecha_emision`),
  KEY `idx_factura_cobranza` (`id_empresa`,`estado`,`fecha_vencimiento`),
  CONSTRAINT `fk_fact_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`),
  CONSTRAINT `fk_fact_contrato` FOREIGN KEY (`id_contrato`) REFERENCES `contrato_servicio` (`id_contrato`) ON DELETE SET NULL,
  CONSTRAINT `fk_fact_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_fact_serie` FOREIGN KEY (`id_serie`) REFERENCES `serie_facturacion` (`id_serie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Facturas emitidas con soporte FEL (SAT Guatemala)';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fuente_lead`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fuente_lead` (
  `id_fuente` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL COMMENT 'Referido, Web, LinkedIn, Expo, Llamada',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_fuente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `historial_password`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `historial_password` (
  `id_historial` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int(10) unsigned NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_historial`),
  KEY `historial_password_id_usuario_index` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Contraseñas anteriores (solo el hash) para no repetirlas';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `historial_salarial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `historial_salarial` (
  `id_historial` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empleado` int(10) unsigned NOT NULL,
  `id_cargo` smallint(5) unsigned DEFAULT NULL COMMENT 'Cargo al momento del ajuste',
  `salario_anterior` decimal(15,4) DEFAULT NULL,
  `salario_nuevo` decimal(15,4) NOT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `tipo_cambio` enum('CONTRATACION','AUMENTO','AJUSTE','PROMOCION','CORRECCION') NOT NULL,
  `fecha_efectiva` date NOT NULL,
  `motivo` varchar(300) DEFAULT NULL,
  `aprobado_por` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_historial`),
  KEY `idx_sal_empleado` (`id_empleado`),
  KEY `idx_sal_fecha` (`fecha_efectiva`),
  KEY `idx_historial_cargo` (`id_cargo`),
  CONSTRAINT `fk_sal_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Trazabilidad completa de cambios salariales';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `industria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `industria` (
  `id_industria` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id_industria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `linea_negocio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `linea_negocio` (
  `id_linea` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_linea`),
  KEY `idx_linea_empresa` (`id_empresa`),
  CONSTRAINT `fk_linea_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Outsourcing, Higiene, Facility Mgmt, Limpiezas Profundas';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meta_venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meta_venta` (
  `id_meta` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_empleado` int(10) unsigned NOT NULL,
  `id_linea` tinyint(3) unsigned DEFAULT NULL,
  `anio` year(4) NOT NULL,
  `mes` tinyint(3) unsigned NOT NULL,
  `meta_valor` decimal(15,4) NOT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `meta_contratos` smallint(5) unsigned DEFAULT NULL,
  `valor_logrado` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `contratos_logrados` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_meta`),
  UNIQUE KEY `uq_meta` (`id_empresa`,`id_empleado`,`anio`,`mes`),
  KEY `fk_meta_empleado` (`id_empleado`),
  KEY `idx_meta_linea` (`id_linea`),
  CONSTRAINT `fk_meta_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`),
  CONSTRAINT `fk_meta_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `modulo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `modulo` (
  `id_modulo` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL COMMENT 'Parte «modulo» del código de permiso: bodegas, ordenes_compra…',
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `icono` varchar(50) DEFAULT NULL COMMENT 'Ícono Feather',
  `ruta` varchar(150) DEFAULT NULL COMMENT 'Ruta en el menú lateral; vacía = no aparece en el menú',
  `orden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `id_modulo_padre` smallint(5) unsigned DEFAULT NULL,
  `grupo` varchar(60) DEFAULT NULL COMMENT 'Encabezado del menú lateral (módulos de primer nivel)',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_modulo`),
  UNIQUE KEY `modulo_codigo_unique` (`codigo`),
  KEY `idx_modulo_padre_orden` (`id_modulo_padre`,`orden`),
  CONSTRAINT `modulo_id_modulo_padre_foreign` FOREIGN KEY (`id_modulo_padre`) REFERENCES `modulo` (`id_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `moneda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `moneda` (
  `codigo` char(3) NOT NULL COMMENT 'ISO 4217: GTQ, USD, HNL, NIO, CRC',
  `nombre` varchar(60) NOT NULL,
  `simbolo` varchar(5) NOT NULL,
  `decimales` tinyint(3) unsigned NOT NULL DEFAULT 2,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo ISO 4217 de monedas';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movimiento_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimiento_inventario` (
  `id_movimiento` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_producto` int(10) unsigned NOT NULL,
  `id_bodega` smallint(5) unsigned NOT NULL,
  `tipo_movimiento` enum('ENTRADA','SALIDA','TRASLADO','AJUSTE','BAJA','DEVOLUCION') NOT NULL,
  `cantidad` decimal(12,4) NOT NULL,
  `costo_unitario` decimal(15,4) DEFAULT NULL,
  `costo_total` decimal(15,4) DEFAULT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `referencia_tipo` enum('COMPRA','CONTRATO','TRASLADO','AJUSTE','OTRO') DEFAULT NULL,
  `referencia_id` int(10) unsigned DEFAULT NULL COMMENT 'ID de la OC, contrato, etc.',
  `numero_lote` varchar(50) DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `observaciones` varchar(300) DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_movimiento`),
  KEY `idx_mov_producto` (`id_producto`),
  KEY `idx_mov_bodega` (`id_bodega`),
  KEY `idx_mov_empresa` (`id_empresa`),
  KEY `idx_mov_fecha` (`created_at`),
  KEY `idx_mov_tipo` (`tipo_movimiento`),
  KEY `idx_mov_inv_periodo` (`id_empresa`,`created_at`,`tipo_movimiento`),
  CONSTRAINT `fk_mov_bodega` FOREIGN KEY (`id_bodega`) REFERENCES `bodega` (`id_bodega`),
  CONSTRAINT `fk_mov_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mov_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kardex de movimientos de inventario — alta frecuencia';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `municipio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `municipio` (
  `id_municipio` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_division` smallint(5) unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_municipio`),
  KEY `idx_municipio_division` (`id_division`),
  CONSTRAINT `fk_municipio_division` FOREIGN KEY (`id_division`) REFERENCES `division_geografica` (`id_division`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notificacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificacion` (
  `id_notificacion` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int(10) unsigned NOT NULL,
  `tipo` varchar(50) NOT NULL COMMENT 'TICKET_NUEVO, CONTRATO_VENCE, PAGO_PENDIENTE...',
  `titulo` varchar(200) NOT NULL,
  `mensaje` text NOT NULL,
  `url_destino` varchar(500) DEFAULT NULL COMMENT 'Ruta del frontend para redireccionar',
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_leida` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_notificacion`),
  KEY `idx_notif_usuario` (`id_usuario`,`leida`),
  KEY `idx_notif_fecha` (`created_at`),
  CONSTRAINT `fk_notif_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oportunidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oportunidad` (
  `id_oportunidad` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_cliente` int(10) unsigned DEFAULT NULL COMMENT 'NULL si viene de prospecto no convertido',
  `id_prospecto` int(10) unsigned DEFAULT NULL,
  `id_etapa` smallint(5) unsigned NOT NULL,
  `id_responsable` int(10) unsigned NOT NULL,
  `id_linea` tinyint(3) unsigned DEFAULT NULL COMMENT 'Línea de negocio a la que aplica',
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `valor_estimado` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `probabilidad` tinyint(3) unsigned NOT NULL DEFAULT 50 COMMENT '0-100%',
  `valor_ponderado` decimal(15,4) GENERATED ALWAYS AS (`valor_estimado` * `probabilidad` / 100) STORED COMMENT 'Calculado automáticamente',
  `fecha_cierre_estimada` date DEFAULT NULL,
  `fecha_cierre_real` date DEFAULT NULL,
  `razon_cierre` varchar(300) DEFAULT NULL,
  `competidores` varchar(300) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_oportunidad`),
  KEY `idx_op_empresa` (`id_empresa`),
  KEY `idx_op_cliente` (`id_cliente`),
  KEY `idx_op_prospecto` (`id_prospecto`),
  KEY `idx_op_etapa` (`id_etapa`),
  KEY `idx_op_responsable` (`id_responsable`),
  KEY `idx_op_cierre` (`fecha_cierre_estimada`),
  KEY `fk_op_linea` (`id_linea`),
  CONSTRAINT `fk_op_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE SET NULL,
  CONSTRAINT `fk_op_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_op_etapa` FOREIGN KEY (`id_etapa`) REFERENCES `etapa_funnel` (`id_etapa`),
  CONSTRAINT `fk_op_linea` FOREIGN KEY (`id_linea`) REFERENCES `linea_negocio` (`id_linea`) ON DELETE SET NULL,
  CONSTRAINT `fk_op_prospecto` FOREIGN KEY (`id_prospecto`) REFERENCES `prospecto` (`id_prospecto`) ON DELETE SET NULL,
  CONSTRAINT `fk_op_responsable` FOREIGN KEY (`id_responsable`) REFERENCES `empleado` (`id_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Oportunidades de venta con pipeline y probabilidad';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orden_compra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orden_compra` (
  `id_oc` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_proveedor` int(10) unsigned NOT NULL,
  `id_bodega` smallint(5) unsigned DEFAULT NULL,
  `numero_oc` varchar(30) NOT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_entrega_esperada` date DEFAULT NULL,
  `fecha_entrega_real` date DEFAULT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `subtotal` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `iva` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `total` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `estado` enum('BORRADOR','ENVIADA','PARCIAL','RECIBIDA','CANCELADA') NOT NULL DEFAULT 'BORRADOR',
  `notas` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `aprobado_por` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_oc`),
  UNIQUE KEY `uq_oc_empresa_numero` (`id_empresa`,`numero_oc`),
  KEY `idx_oc_empresa` (`id_empresa`),
  KEY `idx_oc_proveedor` (`id_proveedor`),
  KEY `idx_oc_estado` (`estado`),
  KEY `fk_oc_bodega` (`id_bodega`),
  CONSTRAINT `fk_oc_bodega` FOREIGN KEY (`id_bodega`) REFERENCES `bodega` (`id_bodega`) ON DELETE SET NULL,
  CONSTRAINT `fk_oc_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_oc_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pago` (
  `id_pago` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_cliente` int(10) unsigned NOT NULL,
  `id_factura` int(10) unsigned DEFAULT NULL COMMENT 'NULL si es abono a cuenta',
  `referencia` varchar(100) NOT NULL COMMENT 'No. cheque, transferencia, voucher',
  `forma_pago` enum('EFECTIVO','CHEQUE','TRANSFERENCIA','TARJETA','DEPOSITO','OTRO') NOT NULL,
  `banco_origen` varchar(100) DEFAULT NULL,
  `monto` decimal(15,4) NOT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `fecha_pago` date NOT NULL,
  `fecha_acreditado` date DEFAULT NULL COMMENT 'Fecha valor en cuenta bancaria',
  `comprobante_url` varchar(500) DEFAULT NULL,
  `notas` varchar(300) DEFAULT NULL,
  `estado` enum('APLICADO','REVERTIDO','DEVUELTO') NOT NULL DEFAULT 'APLICADO',
  `revertido_por` int(10) unsigned DEFAULT NULL,
  `fecha_reversion` datetime DEFAULT NULL,
  `motivo_reversion` varchar(300) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pago`),
  KEY `idx_pago_cliente` (`id_cliente`),
  KEY `idx_pago_factura` (`id_factura`),
  KEY `idx_pago_empresa` (`id_empresa`),
  KEY `idx_pago_fecha` (`fecha_pago`),
  KEY `idx_pago_estado` (`estado`),
  CONSTRAINT `fk_pago_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`),
  CONSTRAINT `fk_pago_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pago_factura` FOREIGN KEY (`id_factura`) REFERENCES `factura` (`id_factura`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pais`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pais` (
  `id_pais` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `codigo_iso2` char(2) NOT NULL COMMENT 'ISO 3166-1 alpha-2: GT, SV, HN, NI, CR',
  `codigo_iso3` char(3) NOT NULL COMMENT 'ISO 3166-1 alpha-3',
  `nombre` varchar(100) NOT NULL,
  `prefijo_tel` varchar(6) NOT NULL COMMENT 'Ej: +502',
  `moneda_defecto` char(3) NOT NULL COMMENT 'ISO 4217: GTQ, USD, HNL, NIO, CRC',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pais`),
  UNIQUE KEY `uq_pais_iso2` (`codigo_iso2`),
  UNIQUE KEY `uq_pais_iso3` (`codigo_iso3`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de países donde opera Grupo Rolsa';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `periodo_nomina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `periodo_nomina` (
  `id_periodo` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL COMMENT 'Ej: Quincenal Enero 2025 - 1ra',
  `tipo` enum('SEMANAL','QUINCENAL','MENSUAL','CATORCENAL') NOT NULL DEFAULT 'QUINCENAL',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `fecha_pago` date NOT NULL,
  `estado` enum('ABIERTO','EN_PROCESO','CERRADO','PAGADO') NOT NULL DEFAULT 'ABIERTO',
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `total_bruto` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `total_deducciones` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `total_neto` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_periodo`),
  KEY `idx_periodo_empresa` (`id_empresa`),
  KEY `idx_periodo_fechas` (`fecha_inicio`,`fecha_fin`),
  CONSTRAINT `fk_periodo_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permiso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permiso` (
  `id_permiso` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_modulo` smallint(5) unsigned NOT NULL,
  `id_accion` smallint(5) unsigned NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_permiso`),
  UNIQUE KEY `uq_permiso_modulo_accion` (`id_modulo`,`id_accion`),
  KEY `permiso_id_accion_foreign` (`id_accion`),
  CONSTRAINT `permiso_id_accion_foreign` FOREIGN KEY (`id_accion`) REFERENCES `accion` (`id_accion`),
  CONSTRAINT `permiso_id_modulo_foreign` FOREIGN KEY (`id_modulo`) REFERENCES `modulo` (`id_modulo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `plantilla_comunicacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `plantilla_comunicacion` (
  `id_plantilla` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tipo` enum('EMAIL','WHATSAPP','SMS') NOT NULL DEFAULT 'EMAIL',
  `asunto` varchar(200) DEFAULT NULL COMMENT 'Solo para EMAIL',
  `contenido` text NOT NULL COMMENT 'Soporta variables {{nombre_cliente}}, {{nombre_empresa}}',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_plantilla`),
  KEY `fk_plantilla_empresa` (`id_empresa`),
  CONSTRAINT `fk_plantilla_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prestacion_laboral`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prestacion_laboral` (
  `id_prestacion` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empleado` int(10) unsigned NOT NULL,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_periodo` smallint(5) unsigned DEFAULT NULL,
  `tipo` enum('AGUINALDO','BONO14','INDEMNIZACION','VACACIONES_DINERO','OTRO') NOT NULL,
  `periodo_calculo` varchar(20) NOT NULL COMMENT 'Ej: 2024, 2024-S1',
  `monto_base` decimal(15,4) NOT NULL,
  `monto_calculado` decimal(15,4) NOT NULL,
  `dias_calculados` decimal(8,2) DEFAULT NULL,
  `estado` enum('CALCULADO','APROBADO','PAGADO') NOT NULL DEFAULT 'CALCULADO',
  `fecha_pago` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_prestacion`),
  UNIQUE KEY `uq_prestacion_emp_tipo_periodo` (`id_empleado`,`tipo`,`periodo_calculo`),
  KEY `idx_prestacion_empleado` (`id_empleado`),
  KEY `fk_prestacion_empresa` (`id_empresa`),
  KEY `idx_prestacion_periodo` (`id_periodo`),
  CONSTRAINT `fk_prestacion_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON DELETE CASCADE,
  CONSTRAINT `fk_prestacion_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Aguinaldo, Bono 14 y demás prestaciones legales centroamericanas';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prestamo_empleado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prestamo_empleado` (
  `id_prestamo` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empleado` int(10) unsigned NOT NULL,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `monto_total` decimal(15,4) NOT NULL,
  `monto_pendiente` decimal(15,4) NOT NULL,
  `cuota_quincenal` decimal(15,4) NOT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `fecha_otorgamiento` date NOT NULL,
  `motivo` varchar(300) DEFAULT NULL,
  `estado` enum('ACTIVO','CANCELADO','LIQUIDADO') NOT NULL DEFAULT 'ACTIVO',
  `aprobado_por` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_prestamo`),
  KEY `idx_prestamo_empleado` (`id_empleado`),
  KEY `idx_prestamo_estado` (`estado`),
  KEY `fk_prestamo_empresa` (`id_empresa`),
  CONSTRAINT `fk_prestamo_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON DELETE CASCADE,
  CONSTRAINT `fk_prestamo_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `presupuesto_anual`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `presupuesto_anual` (
  `id_presupuesto` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_centro` smallint(5) unsigned NOT NULL,
  `id_cuenta` smallint(5) unsigned NOT NULL,
  `anio` year(4) NOT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `pre_enero` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `pre_febrero` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `pre_marzo` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `pre_abril` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `pre_mayo` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `pre_junio` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `pre_julio` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `pre_agosto` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `pre_septiembre` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `pre_octubre` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `pre_noviembre` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `pre_diciembre` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `eje_enero` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `eje_febrero` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `eje_marzo` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `eje_abril` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `eje_mayo` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `eje_junio` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `eje_julio` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `eje_agosto` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `eje_septiembre` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `eje_octubre` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `eje_noviembre` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `eje_diciembre` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `total_presupuestado` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `total_ejecutado` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `estado` enum('BORRADOR','APROBADO','CERRADO') NOT NULL DEFAULT 'BORRADOR',
  `aprobado_por` int(10) unsigned DEFAULT NULL,
  `fecha_aprobacion` datetime DEFAULT NULL,
  `cerrado_por` int(10) unsigned DEFAULT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_presupuesto`),
  UNIQUE KEY `uq_ppto_anual` (`id_empresa`,`id_centro`,`id_cuenta`,`anio`),
  KEY `idx_ppto_empresa_anio` (`id_empresa`,`anio`),
  KEY `idx_ppto_estado` (`estado`),
  KEY `fk_ppto_centro` (`id_centro`),
  KEY `fk_ppto_cuenta` (`id_cuenta`),
  CONSTRAINT `fk_ppto_centro` FOREIGN KEY (`id_centro`) REFERENCES `centro_costo` (`id_centro`),
  CONSTRAINT `fk_ppto_cuenta` FOREIGN KEY (`id_cuenta`) REFERENCES `cuenta_contable` (`id_cuenta`),
  CONSTRAINT `fk_ppto_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Presupuesto anual con desglose mensual y ejecución acumulada';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto` (
  `id_producto` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_categoria` smallint(5) unsigned DEFAULT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `unidad_medida` varchar(30) NOT NULL DEFAULT 'UND' COMMENT 'UND, LT, KG, MT, CAJA',
  `precio_compra` decimal(15,4) DEFAULT NULL,
  `id_cuenta_gasto` smallint(5) unsigned DEFAULT NULL,
  `id_centro_default` smallint(5) unsigned DEFAULT NULL,
  `precio_venta` decimal(15,4) DEFAULT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `stock_minimo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_maximo` decimal(10,2) DEFAULT NULL,
  `requiere_lote` tinyint(1) NOT NULL DEFAULT 0,
  `es_perecedero` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_producto`),
  UNIQUE KEY `uq_producto_codigo` (`id_empresa`,`codigo`),
  KEY `idx_prod_empresa` (`id_empresa`),
  KEY `idx_prod_categoria` (`id_categoria`),
  KEY `fk_producto_centro` (`id_centro_default`),
  KEY `idx_producto_contabilidad` (`id_cuenta_gasto`,`id_centro_default`),
  CONSTRAINT `fk_prod_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria_producto` (`id_categoria`) ON DELETE SET NULL,
  CONSTRAINT `fk_prod_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_producto_centro` FOREIGN KEY (`id_centro_default`) REFERENCES `centro_costo` (`id_centro`) ON DELETE SET NULL,
  CONSTRAINT `fk_producto_cuenta` FOREIGN KEY (`id_cuenta_gasto`) REFERENCES `cuenta_contable` (`id_cuenta`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Insumos de limpieza, EPP, uniformes, químicos, herramientas';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `propuesta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `propuesta` (
  `id_propuesta` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_oportunidad` int(10) unsigned NOT NULL,
  `id_elaborado_por` int(10) unsigned NOT NULL,
  `numero_propuesta` varchar(30) NOT NULL,
  `version` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `titulo` varchar(200) NOT NULL,
  `valor_propuesto` decimal(15,4) NOT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `url_documento` varchar(500) DEFAULT NULL,
  `notas_internas` text DEFAULT NULL,
  `estado` enum('BORRADOR','ENVIADA','EN_REVISION','ACEPTADA','RECHAZADA','VENCIDA') NOT NULL DEFAULT 'BORRADOR',
  `motivo_rechazo` varchar(300) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_propuesta`),
  KEY `idx_prop_oportunidad` (`id_oportunidad`),
  KEY `idx_prop_estado` (`estado`),
  KEY `fk_prop_elaborado` (`id_elaborado_por`),
  CONSTRAINT `fk_prop_elaborado` FOREIGN KEY (`id_elaborado_por`) REFERENCES `empleado` (`id_empleado`),
  CONSTRAINT `fk_prop_oportunidad` FOREIGN KEY (`id_oportunidad`) REFERENCES `oportunidad` (`id_oportunidad`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prospecto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prospecto` (
  `id_prospecto` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_fuente` smallint(5) unsigned DEFAULT NULL,
  `id_asignado_a` int(10) unsigned DEFAULT NULL COMMENT 'Vendedor asignado',
  `id_pais` tinyint(3) unsigned DEFAULT NULL,
  `id_industria` smallint(5) unsigned DEFAULT NULL,
  `nombre_empresa` varchar(250) NOT NULL,
  `sitio_web` varchar(200) DEFAULT NULL,
  `empleados_estimados` varchar(20) DEFAULT NULL COMMENT '1-10, 11-50, 51-200, 200+',
  `nombre_contacto` varchar(200) NOT NULL,
  `cargo_contacto` varchar(150) DEFAULT NULL,
  `email_contacto` varchar(150) NOT NULL,
  `telefono_contacto` varchar(20) DEFAULT NULL,
  `whatsapp_contacto` varchar(20) DEFAULT NULL,
  `temperatura` enum('FRIO','TIBIO','CALIENTE') NOT NULL DEFAULT 'FRIO',
  `puntuacion_lead` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Lead scoring 0-100',
  `interes_servicio` text DEFAULT NULL,
  `presupuesto_estimado` decimal(15,4) DEFAULT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `estado` enum('NUEVO','EN_CONTACTO','CALIFICADO','NO_CALIFICADO','CONVERTIDO','DESCARTADO') NOT NULL DEFAULT 'NUEVO',
  `motivo_descarte` varchar(300) DEFAULT NULL,
  `fecha_conversion` datetime DEFAULT NULL COMMENT 'Cuando se convierte en cliente',
  `id_cliente_generado` int(10) unsigned DEFAULT NULL COMMENT 'FK al cliente creado al convertir',
  `notas` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_prospecto`),
  KEY `idx_prosp_empresa` (`id_empresa`),
  KEY `idx_prosp_asignado` (`id_asignado_a`),
  KEY `idx_prosp_estado` (`estado`),
  KEY `idx_prosp_temperatura` (`temperatura`),
  KEY `idx_prosp_cliente` (`id_cliente_generado`),
  KEY `fk_prosp_fuente` (`id_fuente`),
  KEY `fk_prosp_pais` (`id_pais`),
  KEY `fk_prosp_industria` (`id_industria`),
  FULLTEXT KEY `ftx_prospecto_busqueda` (`nombre_empresa`,`nombre_contacto`,`email_contacto`),
  CONSTRAINT `fk_prosp_asignado` FOREIGN KEY (`id_asignado_a`) REFERENCES `empleado` (`id_empleado`) ON DELETE SET NULL,
  CONSTRAINT `fk_prosp_cliente` FOREIGN KEY (`id_cliente_generado`) REFERENCES `cliente` (`id_cliente`) ON DELETE SET NULL,
  CONSTRAINT `fk_prosp_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prosp_fuente` FOREIGN KEY (`id_fuente`) REFERENCES `fuente_lead` (`id_fuente`) ON DELETE SET NULL,
  CONSTRAINT `fk_prosp_industria` FOREIGN KEY (`id_industria`) REFERENCES `industria` (`id_industria`) ON DELETE SET NULL,
  CONSTRAINT `fk_prosp_pais` FOREIGN KEY (`id_pais`) REFERENCES `pais` (`id_pais`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pipeline de leads y prospectos comerciales';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `proveedor` (
  `id_proveedor` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_pais` tinyint(3) unsigned NOT NULL,
  `razon_social` varchar(250) NOT NULL,
  `nombre_comercial` varchar(150) DEFAULT NULL,
  `nit` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(300) DEFAULT NULL,
  `contacto` varchar(200) DEFAULT NULL,
  `tipo_proveedor` enum('BIENES','SERVICIOS','AMBOS') NOT NULL DEFAULT 'BIENES',
  `dias_credito` tinyint(3) unsigned NOT NULL DEFAULT 30,
  `moneda_pago` char(3) NOT NULL DEFAULT 'GTQ',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_proveedor`),
  KEY `idx_prov_empresa` (`id_empresa`),
  KEY `fk_prov_pais` (`id_pais`),
  CONSTRAINT `fk_prov_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prov_pais` FOREIGN KEY (`id_pais`) REFERENCES `pais` (`id_pais`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recepcion_mercaderia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recepcion_mercaderia` (
  `id_recepcion` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_oc` int(10) unsigned NOT NULL,
  `id_bodega` smallint(5) unsigned NOT NULL,
  `numero_recepcion` varchar(30) NOT NULL,
  `fecha_recepcion` date NOT NULL,
  `notas` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_recepcion`),
  UNIQUE KEY `uq_recepcion_empresa_numero` (`id_empresa`,`numero_recepcion`),
  KEY `idx_recepcion_empresa` (`id_empresa`),
  KEY `idx_recepcion_oc` (`id_oc`),
  KEY `idx_recepcion_bodega` (`id_bodega`),
  CONSTRAINT `fk_recepcion_bodega` FOREIGN KEY (`id_bodega`) REFERENCES `bodega` (`id_bodega`),
  CONSTRAINT `fk_recepcion_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`),
  CONSTRAINT `fk_recepcion_oc` FOREIGN KEY (`id_oc`) REFERENCES `orden_compra` (`id_oc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cabecera de recepciones de mercadería';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol` (
  `id_rol` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `es_rol_sistema` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Roles internos no editables',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `requiere_2fa` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Obliga a sus usuarios a activar la verificación en dos pasos',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `uq_rol_empresa_nombre` (`id_empresa`,`nombre`),
  KEY `idx_rol_empresa` (`id_empresa`),
  CONSTRAINT `fk_rol_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rol_permiso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol_permiso` (
  `id_rol` smallint(5) unsigned NOT NULL,
  `id_permiso` smallint(5) unsigned NOT NULL,
  `asignado_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_rol`,`id_permiso`),
  KEY `rol_permiso_id_permiso_foreign` (`id_permiso`),
  CONSTRAINT `rol_permiso_id_permiso_foreign` FOREIGN KEY (`id_permiso`) REFERENCES `permiso` (`id_permiso`) ON DELETE CASCADE,
  CONSTRAINT `rol_permiso_id_rol_foreign` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `seguimiento_prospecto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seguimiento_prospecto` (
  `id_seguimiento` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_prospecto` int(10) unsigned NOT NULL,
  `id_realizado_por` int(10) unsigned NOT NULL,
  `tipo` enum('LLAMADA','EMAIL','REUNION','WHATSAPP','VISITA','DEMO','OTRO') NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `duracion_min` smallint(5) unsigned DEFAULT NULL,
  `resultado` enum('SIN_CONTACTO','CONTACTADO','INTERESADO','SOLICITO_PROPUESTA','NO_INTERESADO','REAGENDAR') NOT NULL,
  `resumen` text NOT NULL,
  `proxima_accion` varchar(300) DEFAULT NULL,
  `fecha_proxima_accion` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_seguimiento`),
  KEY `idx_seg_prospecto` (`id_prospecto`),
  KEY `idx_seg_fecha` (`fecha_hora`),
  KEY `idx_seg_realizado` (`id_realizado_por`),
  KEY `idx_seg_agenda` (`id_realizado_por`,`fecha_proxima_accion`),
  CONSTRAINT `fk_seg_prospecto` FOREIGN KEY (`id_prospecto`) REFERENCES `prospecto` (`id_prospecto`) ON DELETE CASCADE,
  CONSTRAINT `fk_seg_realizado` FOREIGN KEY (`id_realizado_por`) REFERENCES `empleado` (`id_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `serie_facturacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `serie_facturacion` (
  `id_serie` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `codigo_serie` varchar(10) NOT NULL,
  `tipo` enum('FACTURA','CREDITO_FISCAL','NOTA_CREDITO','NOTA_DEBITO','RECIBO') NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL,
  `ultimo_numero` int(10) unsigned NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_serie`),
  UNIQUE KEY `uq_serie_empresa_codigo` (`id_empresa`,`codigo_serie`,`tipo`),
  CONSTRAINT `fk_serie_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sitio_trabajo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sitio_trabajo` (
  `id_sitio` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_cliente` int(10) unsigned NOT NULL,
  `id_municipio` smallint(5) unsigned DEFAULT NULL,
  `nombre` varchar(200) NOT NULL COMMENT 'Ej: Bodega Central Zona 12, Planta Escuintla',
  `direccion` varchar(300) NOT NULL,
  `latitud` decimal(10,7) DEFAULT NULL,
  `longitud` decimal(10,7) DEFAULT NULL,
  `responsable_cliente` varchar(200) DEFAULT NULL COMMENT 'Jefe de sitio del cliente',
  `tel_responsable` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_sitio`),
  KEY `idx_sitio_cliente` (`id_cliente`),
  KEY `idx_sitio_municipio` (`id_municipio`),
  CONSTRAINT `fk_sitio_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sedes físicas del cliente donde se despliegan los servicios de Rolsa';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sla_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sla_config` (
  `id_sla` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL COMMENT 'SLA Estándar, SLA Premium, SLA Gobierno',
  `prioridad` enum('BAJA','MEDIA','ALTA','CRITICA') NOT NULL,
  `tiempo_primera_respuesta_hrs` tinyint(3) unsigned NOT NULL COMMENT 'Horas para 1ra respuesta',
  `tiempo_resolucion_hrs` smallint(5) unsigned NOT NULL COMMENT 'Horas para resolver',
  `aplica_fines_semana` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_sla`),
  UNIQUE KEY `uq_sla` (`id_empresa`,`prioridad`,`nombre`),
  CONSTRAINT `fk_sla_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `solicitud_ausencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitud_ausencia` (
  `id_solicitud` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empleado` int(10) unsigned NOT NULL,
  `tipo` enum('VACACIONES','PERMISO_CON_GOCE','PERMISO_SIN_GOCE','LICENCIA','INCAPACIDAD','DUELO') NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `dias_habiles` tinyint(3) unsigned NOT NULL,
  `motivo` varchar(500) DEFAULT NULL,
  `estado` enum('PENDIENTE','APROBADO','RECHAZADO','CANCELADO') NOT NULL DEFAULT 'PENDIENTE',
  `aprobado_por` int(10) unsigned DEFAULT NULL,
  `fecha_aprobacion` datetime DEFAULT NULL,
  `observaciones` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_solicitud`),
  KEY `idx_ausencia_empleado` (`id_empleado`),
  KEY `idx_ausencia_fechas` (`fecha_inicio`,`fecha_fin`),
  KEY `idx_ausencia_estado` (`estado`),
  CONSTRAINT `fk_ausencia_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_bodega`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_bodega` (
  `id_stock` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_producto` int(10) unsigned NOT NULL,
  `id_bodega` smallint(5) unsigned NOT NULL,
  `cantidad_actual` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `costo_promedio` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_stock`),
  UNIQUE KEY `uq_stock_prod_bodega` (`id_producto`,`id_bodega`),
  KEY `idx_stock_bodega` (`id_bodega`),
  CONSTRAINT `fk_stock_bodega` FOREIGN KEY (`id_bodega`) REFERENCES `bodega` (`id_bodega`),
  CONSTRAINT `fk_stock_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Saldo actual de inventario por producto y bodega';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sucursal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sucursal` (
  `id_sucursal` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_pais` tinyint(3) unsigned NOT NULL,
  `id_division` smallint(5) unsigned DEFAULT NULL,
  `id_municipio` smallint(5) unsigned DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `direccion` varchar(300) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `es_casa_matriz` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_sucursal`),
  KEY `idx_sucursal_empresa` (`id_empresa`),
  KEY `idx_sucursal_pais` (`id_pais`),
  KEY `fk_sucursal_division` (`id_division`),
  KEY `idx_sucursal_municipio` (`id_municipio`),
  CONSTRAINT `fk_sucursal_division` FOREIGN KEY (`id_division`) REFERENCES `division_geografica` (`id_division`),
  CONSTRAINT `fk_sucursal_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sucursal_pais` FOREIGN KEY (`id_pais`) REFERENCES `pais` (`id_pais`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Oficinas por país: GT, SV, HN, NI, CR';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket` (
  `id_ticket` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_cliente` int(10) unsigned NOT NULL,
  `id_contrato` int(10) unsigned DEFAULT NULL,
  `id_categoria` smallint(5) unsigned DEFAULT NULL,
  `id_asignado_a` int(10) unsigned DEFAULT NULL,
  `id_sla` smallint(5) unsigned DEFAULT NULL,
  `numero_ticket` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'TICK-2025-00001',
  `asunto` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `canal_origen` enum('EMAIL','TELEFONO','WEB','WHATSAPP','PRESENCIAL','APP') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EMAIL',
  `prioridad` enum('BAJA','MEDIA','ALTA','CRITICA') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MEDIA',
  `tipo` enum('INCIDENTE','SOLICITUD','QUEJA','CONSULTA','MEJORA') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INCIDENTE',
  `sla_primera_respuesta_hrs` tinyint(3) unsigned DEFAULT NULL,
  `sla_resolucion_hrs` smallint(5) unsigned DEFAULT NULL,
  `fecha_apertura` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_limite_respuesta` datetime DEFAULT NULL COMMENT 'Calculado al crear según SLA',
  `fecha_limite_resolucion` datetime DEFAULT NULL COMMENT 'Calculado al crear según SLA',
  `fecha_primera_respuesta` datetime DEFAULT NULL,
  `fecha_resolucion` datetime DEFAULT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `estado` enum('ABIERTO','EN_PROGRESO','PENDIENTE_CLIENTE','RESUELTO','CERRADO','REABIERTO') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ABIERTO',
  `calificacion_cliente` tinyint(3) unsigned DEFAULT NULL,
  `comentario_calificacion` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_ticket`),
  UNIQUE KEY `uq_ticket_numero` (`id_empresa`,`numero_ticket`),
  KEY `idx_tick_cliente` (`id_cliente`),
  KEY `idx_tick_empresa` (`id_empresa`),
  KEY `idx_tick_asignado` (`id_asignado_a`),
  KEY `idx_tick_estado` (`estado`),
  KEY `idx_tick_prioridad` (`prioridad`),
  KEY `idx_tick_apertura` (`fecha_apertura`),
  KEY `idx_tick_limite_res` (`fecha_limite_resolucion`),
  KEY `fk_tick_contrato` (`id_contrato`),
  KEY `fk_tick_categoria` (`id_categoria`),
  KEY `fk_tick_sla` (`id_sla`),
  KEY `idx_ticket_sla_activo` (`id_empresa`,`estado`,`fecha_limite_resolucion`),
  CONSTRAINT `fk_tick_asignado` FOREIGN KEY (`id_asignado_a`) REFERENCES `empleado` (`id_empleado`) ON DELETE SET NULL,
  CONSTRAINT `fk_tick_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria_ticket` (`id_categoria`) ON DELETE SET NULL,
  CONSTRAINT `fk_tick_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`),
  CONSTRAINT `fk_tick_contrato` FOREIGN KEY (`id_contrato`) REFERENCES `contrato_servicio` (`id_contrato`) ON DELETE SET NULL,
  CONSTRAINT `fk_tick_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tick_sla` FOREIGN KEY (`id_sla`) REFERENCES `sla_config` (`id_sla`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_adjunto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_adjunto` (
  `id_adjunto` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_ticket` int(10) unsigned NOT NULL,
  `id_comentario` int(10) unsigned DEFAULT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `url_archivo` varchar(500) NOT NULL,
  `tipo_mime` varchar(100) DEFAULT NULL,
  `tamano_bytes` int(10) unsigned DEFAULT NULL,
  `subido_por` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_adjunto`),
  KEY `idx_adj_ticket` (`id_ticket`),
  KEY `fk_adj_comentario` (`id_comentario`),
  CONSTRAINT `fk_adj_comentario` FOREIGN KEY (`id_comentario`) REFERENCES `ticket_comentario` (`id_comentario`) ON DELETE SET NULL,
  CONSTRAINT `fk_adj_ticket` FOREIGN KEY (`id_ticket`) REFERENCES `ticket` (`id_ticket`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_comentario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_comentario` (
  `id_comentario` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_ticket` int(10) unsigned NOT NULL,
  `id_autor` int(10) unsigned DEFAULT NULL COMMENT 'Empleado de Rolsa',
  `id_usuario` int(10) unsigned DEFAULT NULL,
  `es_nota_interna` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = solo visible internamente',
  `contenido` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_comentario`),
  KEY `idx_com_ticket` (`id_ticket`),
  KEY `fk_com_autor` (`id_autor`),
  KEY `idx_comentario_usuario` (`id_usuario`),
  CONSTRAINT `fk_com_autor` FOREIGN KEY (`id_autor`) REFERENCES `empleado` (`id_empleado`) ON DELETE SET NULL,
  CONSTRAINT `fk_com_ticket` FOREIGN KEY (`id_ticket`) REFERENCES `ticket` (`id_ticket`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipo_cambio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_cambio` (
  `id_tipo_cambio` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `moneda_origen` char(3) NOT NULL,
  `moneda_destino` char(3) NOT NULL,
  `fecha` date NOT NULL,
  `tasa` decimal(15,6) NOT NULL,
  `fuente` varchar(100) DEFAULT NULL COMMENT 'Banco Central, Banguat, etc.',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_tipo_cambio`),
  UNIQUE KEY `uq_tipo_cambio_dia` (`moneda_origen`,`moneda_destino`,`fecha`),
  KEY `idx_tc_fecha` (`fecha`),
  KEY `fk_tc_destino` (`moneda_destino`),
  CONSTRAINT `fk_tc_destino` FOREIGN KEY (`moneda_destino`) REFERENCES `moneda` (`codigo`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tc_origen` FOREIGN KEY (`moneda_origen`) REFERENCES `moneda` (`codigo`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de tasas de cambio por día';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipo_servicio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_servicio` (
  `id_tipo_servicio` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_linea` tinyint(3) unsigned NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `unidad_medida` varchar(50) NOT NULL DEFAULT 'MES' COMMENT 'MES, HORA, EVENTO, PERSONA, M2',
  `precio_base` decimal(15,4) DEFAULT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'GTQ',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `id_cuenta_ingreso` smallint(5) unsigned DEFAULT NULL,
  `id_centro_default` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_tipo_servicio`),
  KEY `idx_tservicio_linea` (`id_linea`),
  KEY `fk_tservicio_moneda` (`moneda`),
  KEY `fk_tiposvc_centro` (`id_centro_default`),
  KEY `idx_tiposvc_contabilidad` (`id_cuenta_ingreso`,`id_centro_default`),
  CONSTRAINT `fk_tiposvc_centro` FOREIGN KEY (`id_centro_default`) REFERENCES `centro_costo` (`id_centro`) ON DELETE SET NULL,
  CONSTRAINT `fk_tiposvc_cuenta` FOREIGN KEY (`id_cuenta_ingreso`) REFERENCES `cuenta_contable` (`id_cuenta`) ON DELETE SET NULL,
  CONSTRAINT `fk_tservicio_linea` FOREIGN KEY (`id_linea`) REFERENCES `linea_negocio` (`id_linea`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tservicio_moneda` FOREIGN KEY (`moneda`) REFERENCES `moneda` (`codigo`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `id_usuario` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` tinyint(3) unsigned NOT NULL,
  `id_sucursal` smallint(5) unsigned DEFAULT NULL,
  `username` varchar(60) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'bcrypt hash, nunca texto plano',
  `password_cambiado_at` datetime DEFAULT NULL,
  `debe_cambiar_password` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'El administrador puso la contraseña: debe cambiarla al entrar',
  `remember_token` varchar(100) DEFAULT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `nombre_completo` varchar(200) NOT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `ultimo_login` datetime DEFAULT NULL,
  `intentos_fallidos` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `token_reset` varchar(255) DEFAULT NULL,
  `token_reset_exp` datetime DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uq_usuario_username` (`username`),
  UNIQUE KEY `uq_usuario_email` (`email`),
  KEY `idx_usuario_empresa` (`id_empresa`),
  KEY `idx_usuario_activo` (`activo`,`deleted_at`),
  KEY `fk_usuario_sucursal` (`id_sucursal`),
  CONSTRAINT `fk_usuario_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_usuario_sucursal` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursal` (`id_sucursal`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cuentas de acceso al sistema ERP+CRM';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuario_permiso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_permiso` (
  `id_usuario` int(10) unsigned NOT NULL,
  `id_permiso` smallint(5) unsigned NOT NULL,
  `asignado_por` int(10) unsigned DEFAULT NULL,
  `asignado_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`,`id_permiso`),
  KEY `usuario_permiso_id_permiso_index` (`id_permiso`),
  CONSTRAINT `usuario_permiso_id_permiso_foreign` FOREIGN KEY (`id_permiso`) REFERENCES `permiso` (`id_permiso`) ON DELETE CASCADE,
  CONSTRAINT `usuario_permiso_id_usuario_foreign` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuario_rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_rol` (
  `id_usuario` int(10) unsigned NOT NULL,
  `id_rol` smallint(5) unsigned NOT NULL,
  `fecha_asignacion` date NOT NULL DEFAULT curdate(),
  `asignado_por` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_usuario`,`id_rol`),
  KEY `fk_ur_rol` (`id_rol`),
  CONSTRAINT `fk_ur_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON DELETE CASCADE,
  CONSTRAINT `fk_ur_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `v_cuentas_por_cobrar`;
/*!50001 DROP VIEW IF EXISTS `v_cuentas_por_cobrar`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `v_cuentas_por_cobrar` AS SELECT
 NULL AS `id_factura`,
 NULL AS `empresa_rolsa`,
 NULL AS `cliente`,
 NULL AS `numero_completo`,
 NULL AS `fecha_emision`,
 NULL AS `fecha_vencimiento`,
 NULL AS `dias_vencida`,
 NULL AS `total`,
 NULL AS `total_pagado`,
 NULL AS `saldo_pendiente`,
 NULL AS `moneda`,
 NULL AS `estado`,
 NULL AS `antigüedad` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_empleados_activos`;
/*!50001 DROP VIEW IF EXISTS `v_empleados_activos`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `v_empleados_activos` AS SELECT
 NULL AS `id_empleado`,
 NULL AS `empresa`,
 NULL AS `pais`,
 NULL AS `codigo_empleado`,
 NULL AS `nombre_completo`,
 NULL AS `cargo`,
 NULL AS `departamento`,
 NULL AS `email_corporativo`,
 NULL AS `telefono_personal`,
 NULL AS `fecha_ingreso`,
 NULL AS `tipo_contrato`,
 NULL AS `modalidad_trabajo`,
 NULL AS `meses_trabajados` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_pipeline_ventas`;
/*!50001 DROP VIEW IF EXISTS `v_pipeline_ventas`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `v_pipeline_ventas` AS SELECT
 NULL AS `id_oportunidad`,
 NULL AS `empresa_rolsa`,
 NULL AS `cliente_o_prospecto`,
 NULL AS `oportunidad`,
 NULL AS `etapa`,
 NULL AS `orden_etapa`,
 NULL AS `vendedor`,
 NULL AS `valor_estimado`,
 NULL AS `probabilidad`,
 NULL AS `valor_ponderado`,
 NULL AS `moneda`,
 NULL AS `fecha_cierre_estimada`,
 NULL AS `dias_para_cierre` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_tickets_sla`;
/*!50001 DROP VIEW IF EXISTS `v_tickets_sla`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `v_tickets_sla` AS SELECT
 NULL AS `id_ticket`,
 NULL AS `numero_ticket`,
 NULL AS `empresa_rolsa`,
 NULL AS `cliente`,
 NULL AS `asunto`,
 NULL AS `prioridad`,
 NULL AS `estado`,
 NULL AS `asignado_a`,
 NULL AS `fecha_apertura`,
 NULL AS `fecha_limite_resolucion`,
 NULL AS `minutos_restantes_sla`,
 NULL AS `estado_sla` */;
SET character_set_client = @saved_cs_client;
/*!50001 DROP VIEW IF EXISTS `v_cuentas_por_cobrar`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_uca1400_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `v_cuentas_por_cobrar` AS select `f`.`id_factura` AS `id_factura`,`emp`.`nombre_comercial` AS `empresa_rolsa`,`cl`.`razon_social` AS `cliente`,`f`.`numero_completo` AS `numero_completo`,`f`.`fecha_emision` AS `fecha_emision`,`f`.`fecha_vencimiento` AS `fecha_vencimiento`,to_days(curdate()) - to_days(`f`.`fecha_vencimiento`) AS `dias_vencida`,`f`.`total` AS `total`,`f`.`total_pagado` AS `total_pagado`,`f`.`saldo_pendiente` AS `saldo_pendiente`,`f`.`moneda` AS `moneda`,`f`.`estado` AS `estado`,case when to_days(curdate()) - to_days(`f`.`fecha_vencimiento`) <= 0 then 'VIGENTE' when to_days(curdate()) - to_days(`f`.`fecha_vencimiento`) between 1 and 30 then '1-30 DIAS' when to_days(curdate()) - to_days(`f`.`fecha_vencimiento`) between 31 and 60 then '31-60 DIAS' when to_days(curdate()) - to_days(`f`.`fecha_vencimiento`) between 61 and 90 then '61-90 DIAS' else 'MAS DE 90 DIAS' end AS `antigüedad` from ((`factura` `f` join `empresa` `emp` on(`f`.`id_empresa` = `emp`.`id_empresa`)) join `cliente` `cl` on(`f`.`id_cliente` = `cl`.`id_cliente`)) where `f`.`estado` in ('EMITIDA','ENVIADA','PARCIAL','VENCIDA') and `f`.`saldo_pendiente` > 0 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_empleados_activos`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_uca1400_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `v_empleados_activos` AS select `e`.`id_empleado` AS `id_empleado`,`emp`.`nombre_comercial` AS `empresa`,`p`.`nombre` AS `pais`,`e`.`codigo_empleado` AS `codigo_empleado`,concat(`e`.`primer_nombre`,' ',`e`.`primer_apellido`,' ',ifnull(`e`.`segundo_apellido`,'')) AS `nombre_completo`,`c`.`nombre` AS `cargo`,`d`.`nombre` AS `departamento`,`e`.`email_corporativo` AS `email_corporativo`,`e`.`telefono_personal` AS `telefono_personal`,`e`.`fecha_ingreso` AS `fecha_ingreso`,`e`.`tipo_contrato` AS `tipo_contrato`,`e`.`modalidad_trabajo` AS `modalidad_trabajo`,timestampdiff(MONTH,`e`.`fecha_ingreso`,curdate()) AS `meses_trabajados` from ((((`empleado` `e` join `empresa` `emp` on(`e`.`id_empresa` = `emp`.`id_empresa`)) join `pais` `p` on(`emp`.`id_pais` = `p`.`id_pais`)) left join `cargo` `c` on(`e`.`id_cargo` = `c`.`id_cargo`)) left join `departamento_org` `d` on(`e`.`id_depto_org` = `d`.`id_depto_org`)) where `e`.`estado` = 'ACTIVO' and `e`.`deleted_at` is null */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_pipeline_ventas`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_uca1400_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `v_pipeline_ventas` AS select `o`.`id_oportunidad` AS `id_oportunidad`,`emp`.`nombre_comercial` AS `empresa_rolsa`,ifnull(`cl`.`razon_social`,`pr`.`nombre_empresa`) AS `cliente_o_prospecto`,`o`.`nombre` AS `oportunidad`,`ef`.`nombre` AS `etapa`,`ef`.`orden` AS `orden_etapa`,concat(`e`.`primer_nombre`,' ',`e`.`primer_apellido`) AS `vendedor`,`o`.`valor_estimado` AS `valor_estimado`,`o`.`probabilidad` AS `probabilidad`,`o`.`valor_ponderado` AS `valor_ponderado`,`o`.`moneda` AS `moneda`,`o`.`fecha_cierre_estimada` AS `fecha_cierre_estimada`,to_days(`o`.`fecha_cierre_estimada`) - to_days(curdate()) AS `dias_para_cierre` from (((((`oportunidad` `o` join `empresa` `emp` on(`o`.`id_empresa` = `emp`.`id_empresa`)) join `etapa_funnel` `ef` on(`o`.`id_etapa` = `ef`.`id_etapa`)) join `empleado` `e` on(`o`.`id_responsable` = `e`.`id_empleado`)) left join `cliente` `cl` on(`o`.`id_cliente` = `cl`.`id_cliente`)) left join `prospecto` `pr` on(`o`.`id_prospecto` = `pr`.`id_prospecto`)) where `ef`.`es_ganada` = 0 and `ef`.`es_perdida` = 0 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_tickets_sla`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_uca1400_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `v_tickets_sla` AS select `t`.`id_ticket` AS `id_ticket`,`t`.`numero_ticket` AS `numero_ticket`,`emp`.`nombre_comercial` AS `empresa_rolsa`,`cl`.`razon_social` AS `cliente`,`t`.`asunto` AS `asunto`,`t`.`prioridad` AS `prioridad`,`t`.`estado` AS `estado`,concat(`e`.`primer_nombre`,' ',`e`.`primer_apellido`) AS `asignado_a`,`t`.`fecha_apertura` AS `fecha_apertura`,`t`.`fecha_limite_resolucion` AS `fecha_limite_resolucion`,timestampdiff(MINUTE,current_timestamp(),`t`.`fecha_limite_resolucion`) AS `minutos_restantes_sla`,case when `t`.`fecha_resolucion` is not null then 'RESUELTO' when `t`.`fecha_limite_resolucion` < current_timestamp() then 'SLA_VENCIDO' when timestampdiff(HOUR,current_timestamp(),`t`.`fecha_limite_resolucion`) <= 2 then 'SLA_CRITICO' else 'EN_TIEMPO' end AS `estado_sla` from (((`ticket` `t` join `empresa` `emp` on(`t`.`id_empresa` = `emp`.`id_empresa`)) join `cliente` `cl` on(`t`.`id_cliente` = `cl`.`id_cliente`)) left join `empleado` `e` on(`t`.`id_asignado_a` = `e`.`id_empleado`)) where `t`.`estado` <> 'CERRADO' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

/*M!999999\- enable the sandbox mode */ 
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2026_09_29_000001_seguridad_cuentas',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_09_30_000001_modulos_acciones_permisos',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_09_30_000002_acciones_ampliadas',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_09_30_000003_kardex_recepciones_anteriores',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_09_30_000004_cobros_trazables',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_09_30_000005_quitar_sanctum_y_spatie',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_09_30_000006_modulo_kardex',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_09_30_000007_modulos_catalogos_comerciales',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_09_30_000008_permiso_organizacion',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_09_30_000009_tickets_por_usuario',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_09_30_000010_permiso_aprobar_ausencias',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_09_30_000011_permiso_editar_campanas',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_09_30_000012_modulo_bitacora_cambios',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_09_30_000013_evento_desbloqueo_accesos',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_09_30_000014_politica_contrasenas',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_10_03_000001_personal_rotativo',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_10_03_000002_modulo_prestaciones',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_11_03_000001_indices_bitacora_y_referencias',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_11_04_000001_ejecucion_presupuesto_por_documento',20);
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
