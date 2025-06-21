# Changelog

Todas las modificaciones importantes de este plugin se registran en este archivo.

## [2.0.0] – 2024-06-01
### Added
- Estrutura modular PSR-4 completa  
- Soporte para múltiples tests psicológicos: BigFive, GEQ, Monitor Tests  
- Integración con WhatsApp vía servicio Node.js (Baileys)  
- UI/Admin modernizada con tema oscuro tech-style  
- Shortcodes nuevos: `[cpp_user_panel]`, `[cpp_results_panel]`, `[cpp_result id=""]`  
- REST API endpoints:  
  - `GET /wp-json/cpp/v1/tests`  
  - `POST /wp-json/cpp/v1/tests/{type}/submit`  
  - `GET /wp-json/cpp/v1/results/{id}`  
  - `GET /wp-json/cpp/v1/users/me/tests`  
- Sistema de calculadoras desacoplado con `CalculatorRegistry`  
- Validadores: `RequiredFieldsValidator`, `RangeValidator`, `ConditionalValidator`  
- Renderers de formularios: `FormRenderer`, `TableFormRenderer`, `SectionedFormRenderer`, `ModalFormRenderer`  
- Test types: BigFive, TeamCohesion, AngerRumination, AttendingEmotions, PHQ-SADS, WHO-5, Suggestibility  

### Changed
- Refactor global de funciones a clases según PSR-4  
- Separación de lógica en `Core`, `Tests`, `Users`, `UI`, `API`, `WhatsApp`, `Email`  
- Migración de scripts y estilos a `UI/AssetManager`  
- Autoloader Composer configurado  

### Fixed
- Corrección en recolección de respuestas condicionales de pánico (PHQ-SADS)  
- Validaciones CSRF con nonces para todos los formularios  
- Ajustes de accesibilidad y usabilidad en UI/Admin  

## [1.5.0] – 2023-11-15
### Added
- Nuevo test “Sugestionabilidad” (MISS)  
- Integración de “WHO-5 Wellbeing”  
- Mejoras en estilos CSS de formularios  

### Changed
- Refactor parcial de `monitor_test_form_shortcode()`  
- Actualización de dependencias de frontend  

### Fixed
- Bugs menores en shortcode de resultados  

## [1.4.0] – 2023-08-10
### Added
- Test de “Atención a las Emociones” (IPIP)  
- Sistema de subescalas y rangos dinámicos  

### Fixed
- Issue con guardado de metadatos en CPT `resultado_monitor`  
- Ajustes de compatibilidad con WordPress 6.0  

## [1.3.0] – 2023-05-05
### Added
- Soporte para test de Cohesión de Equipo (GEQ)  
- Generación de gráficos gauge en página de resultados  

### Changed
- Estructura de carpetas para templates y assets  

## [1.2.0] – 2023-02-20
### Added
- Test de “BigFive” (B5-AI)  
- Primer prototipo de REST API  

### Fixed
- Validación de campos de PHQ-9 y GAD-7  

## [1.1.0] – 2022-12-01
### Added
- Shortcode `[monitor_test_form]`  
- CPT `monitor_test_results`  

## [1.0.0] – 2022-09-10
### Added
- Versión inicial con shortcode de Monitor Test  
- Escalas de Rumiación de la Ira (ARS)  
- PHQ-SADS básico  
- Diseño responsivo básico  
