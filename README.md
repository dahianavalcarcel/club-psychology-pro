# Club Psychology Pro

[![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/tu-usuario/club-psychology-pro)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)](LICENSE)
[![PSR-4](https://img.shields.io/badge/PSR--4-Autoloading-orange.svg)](https://www.php-fig.org/psr/psr-4/)

> 🧠 **Sistema profesional de evaluaciones psicológicas para WordPress con arquitectura modular y soporte para WhatsApp**

## 📋 Descripción

Club Psychology Pro es un plugin de WordPress diseñado para profesionales de la psicología que necesitan administrar tests psicológicos de manera eficiente. Cuenta con una arquitectura modular PSR-4, soporte para múltiples tipos de evaluaciones y integración con WhatsApp para notificaciones automáticas.

### 🎯 Características Principales

- **🧪 Tests Psicológicos Completos**: BigFive, Cohesión de Equipo, Monitor Tests especializados
- **📊 Análisis Avanzados**: Cálculos automáticos con interpretaciones profesionales
- **👥 Gestión de Usuarios**: Sistema de suscripciones con límites personalizados
- **📱 Integración WhatsApp**: Notificaciones automáticas vía Baileys
- **🎨 UI Moderna**: Interfaz oscura tech-style responsive
- **🌐 Multiidioma**: Sistema i18n completo
- **🔒 Seguridad**: Validaciones robustas y sanitización de datos

## 🏗️ Arquitectura

### Estructura PSR-4

```
club-psychology-pro/
├── 📄 club-psychology-pro.php    # Plugin principal
├── 📄 composer.json              # Autoloader PSR-4
├── 📁 src/                       # Clases organizadas por namespace
│   ├── Core/                     # Sistema principal
│   ├── Tests/                    # Gestión de evaluaciones
│   ├── Users/                    # Manejo de usuarios
│   ├── Email/                    # Sistema de correos
│   ├── WhatsApp/                 # Integración WhatsApp
│   ├── UI/                       # Interfaces de usuario
│   └── API/                      # Endpoints REST
├── 📁 whatsapp-service/          # Servicio Node.js
├── 📁 templates/                 # Templates PHP
├── 📁 assets/                    # CSS/JS compilados
└── 📁 languages/                 # Archivos de traducción
```

### 🧩 Componentes Principales

| Componente | Descripción | Responsabilidades |
|------------|-------------|-------------------|
| **TestManager** | Gestor central de tests | Registro, validación, procesamiento |
| **CalculatorRegistry** | Registro de calculadoras | Cálculos específicos por tipo de test |
| **UserManager** | Gestión de usuarios | Suscripciones, permisos, límites |
| **WhatsAppManager** | Integración WhatsApp | Notificaciones automáticas |
| **DashboardManager** | Interfaz de usuario | Paneles, shortcodes, componentes |

## 🧪 Tests Disponibles

### 🎭 Tests de Personalidad

| Test | Descripción | Factores | Tiempo |
|------|-------------|----------|---------|
| **BigFive (B5-AI)** | Evaluación completa de personalidad | 5 dominios + 30 facetas | 15-20 min |
| **Cohesión de Equipo (GEQ)** | Dinámica grupal y cohesión | 4 dimensiones | 10-15 min |

### 🔍 Monitor Tests

| Test | Código | Descripción | Ítems | Tiempo |
|------|--------|-------------|-------|---------|
| **Rumiación de la Ira** | ARS | Tendencia a enfocarse en pensamientos de ira | 19 | 5-8 min |
| **Ansiedad y Depresión** | PHQ-SADS | Screening integral de salud mental | 32 | 10-15 min |
| **Sugestionabilidad** | MISS | Susceptibilidad a la influencia externa | 21 | 8-12 min |
| **Bienestar** | WHO-5 | Índice de bienestar de la OMS | 5 | 2-3 min |
| **Atención Emocional** | IPIP | Conciencia de estados emocionales internos | 10 | 5-7 min |

### 📊 Subsecciones PHQ-SADS

- **PHQ-15**: Síntomas somáticos (15 ítems)
- **GAD-7**: Trastorno de ansiedad generalizada (7 ítems) 
- **PHQ-9**: Episodio depresivo mayor (9 ítems)
- **Pánico**: Evaluación de ataques de pánico (4 ítems)
- **Funcionalidad**: Impacto en actividades diarias (1 ítem)

## 🚀 Instalación

### Requisitos del Sistema

- **WordPress**: 6.0 o superior
- **PHP**: 8.0 o superior
- **MySQL**: 5.7 o superior
- **Node.js**: 16+ (para WhatsApp service)
- **Composer**: Para dependencias PHP

### 📦 Instalación Estándar

1. **Descargar el plugin**
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/tu-usuario/club-psychology-pro.git
   ```

2. **Instalar dependencias PHP**
   ```bash
   cd club-psychology-pro/
   composer install --no-dev
   ```

3. **Configurar assets (opcional)**
   ```bash
   cd assets/
   npm install
   npm run build
   ```

4. **Activar en WordPress**
   - Ir a `Plugins > Plugins Instalados`
   - Activar "Club Psychology Pro"

### 🔧 Configuración Avanzada

#### WhatsApp Service (Opcional)

```bash
cd whatsapp-service/
chmod +x install.sh
./install.sh
```

#### Variables de Entorno

Crear archivo `.env` en la raíz:

```env
# Database
DB_HOST=localhost
DB_NAME=wordpress_db
DB_USER=wp_user
DB_PASS=secure_password

# WhatsApp
WHATSAPP_ENABLED=true
WHATSAPP_SERVICE_URL=http://localhost:3000

# Email
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=tu-email@gmail.com
SMTP_PASS=app_password

# API Keys
OPENAI_API_KEY=tu_api_key_opcional
```

## 🎮 Uso

### 👤 Para Usuarios

#### 1. **Solicitar un Test**

```php
// Shortcode en cualquier página/post
[cpp_user_panel]
```

**Funcionalidades:**
- ✅ Crear solicitudes de test
- ✅ Ver historial de evaluaciones
- ✅ Acceder a resultados detallados
- ✅ Filtrar por tipo, fecha, estado

#### 2. **Realizar un Test**

Los usuarios reciben un enlace por email y pueden:
- Completar el test en su propio tiempo
- Guardar progreso automáticamente
- Ver indicadores de progreso
- Recibir resultados inmediatos

#### 3. **Ver Resultados**

```php
// Shortcode para mostrar resultado específico
[cpp_result id="123"]

// Panel general de resultados
[cpp_results_panel]
```

### 👨‍💼 Para Administradores

#### 1. **Dashboard Administrativo**

Acceso desde `WordPress Admin > Psychology Pro`

**Módulos disponibles:**
- 📊 **Dashboard General**: Estadísticas y resumen
- 🧪 **Gestión de Tests**: Crear, editar, configurar
- 👥 **Usuarios**: Gestionar suscripciones y límites
- 📧 **Comunicaciones**: Templates de email y WhatsApp
- ⚙️ **Configuraciones**: Ajustes generales del sistema

#### 2. **Crear Tests Personalizados**

```php
// Registrar nuevo tipo de test
add_action('cpp_register_test_types', function($registry) {
    $registry->register('mi_test_custom', new MiTestCustomizado());
});
```

#### 3. **Configurar Límites por Suscripción**

```php
// En functions.php del tema
add_filter('cpp_subscription_limits', function($limits) {
    $limits['plan_premium'] = [
        'tests_per_month' => 50,
        'concurrent_tests' => 10,
        'whatsapp_enabled' => true
    ];
    return $limits;
});
```

## 🔌 API REST

### Endpoints Principales

| Método | Endpoint | Descripción | Autenticación |
|--------|----------|-------------|---------------|
| `GET` | `/wp-json/cpp/v1/tests` | Listar tests disponibles | No |
| `POST` | `/wp-json/cpp/v1/tests/{type}/submit` | Enviar respuestas | Requerida |
| `GET` | `/wp-json/cpp/v1/results/{id}` | Obtener resultado | Requerida |
| `GET` | `/wp-json/cpp/v1/users/me/tests` | Tests del usuario actual | Requerida |

### 📝 Ejemplo de Uso

```javascript
// Enviar respuestas de un test
const response = await fetch('/wp-json/cpp/v1/tests/anger_rumination/submit', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        test_id: 123,
        responses: {
            'ARS1': 3,
            'ARS2': 2,
            // ... más respuestas
        }
    })
});

const result = await response.json();
console.log('Resultado:', result);
```

## 🎨 Personalización

### 🎭 Temas y Estilos

#### Sobrescribir Templates

```php
// En tu tema: /club-psychology-pro/templates/tests/mi-test.php
<div class="mi-test-personalizado">
    <?php echo $test_content; ?>
</div>
```

#### CSS Personalizado

```scss
// Personalizar variables CSS
:root {
    --cpp-primary-color: #your-brand-color;
    --cpp-secondary-color: #your-secondary-color;
    --cpp-font-family: 'Your Font', sans-serif;
}

// Sobrescribir componentes específicos
.cpp-test-card {
    border-radius: 15px;
    box-shadow: your-custom-shadow;
}
```

### 🔧 Hooks y Filtros

#### Actions (Acciones)

```php
// Después de completar un test
add_action('cpp_test_completed', function($test_id, $result_id, $user_id) {
    // Tu lógica personalizada
    wp_mail($admin_email, "Test completado", "Usuario {$user_id} completó test {$test_id}");
});

// Antes de enviar email
add_action('cpp_before_send_email', function($email_data) {
    // Modificar datos del email
    error_log("Enviando email a: " . $email_data['to']);
});
```

#### Filters (Filtros)

```php
// Modificar configuración de test
add_filter('cpp_test_config', function($config, $test_type) {
    if ($test_type === 'anger_rumination') {
        $config['time_limit'] = 1800; // 30 minutos
    }
    return $config;
}, 10, 2);

// Personalizar interpretación de resultados
add_filter('cpp_result_interpretation', function($interpretation, $result_data) {
    // Tu lógica de interpretación personalizada
    return $interpretation;
}, 10, 2);
```

### 📧 Templates de Email

```php
// Crear template personalizado en /templates/email/mi-template.php
<div style="font-family: Arial, sans-serif; max-width: 600px;">
    <h2>¡Hola <?php echo $user_name; ?>!</h2>
    <p>Tu test <strong><?php echo $test_name; ?></strong> está listo.</p>
    <a href="<?php echo $test_url; ?>" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none;">
        Realizar Test
    </a>
</div>
```

## 🔒 Seguridad

### 🛡️ Medidas Implementadas

- **Sanitización de datos**: Todos los inputs son sanitizados
- **Validación robusta**: Múltiples capas de validación
- **Nonces de WordPress**: Protección CSRF
- **Capacidades y roles**: Sistema de permisos granular
- **Rate limiting**: Prevención de abuso de API
- **Escape de output**: Prevención XSS

### 🔐 Configuración de Seguridad

```php
// En wp-config.php
define('CPP_SECURITY_LEVEL', 'high');
define('CPP_MAX_REQUESTS_PER_MINUTE', 60);
define('CPP_ENABLE_LOGGING', true);
```

## 🌐 Internacionalización

### 🗣️ Idiomas Soportados

- 🇺🇸 **English** (en_US) - Completo
- 🇪🇸 **Español** (es_ES) - Completo  
- 🇫🇷 **Français** (fr_FR) - En desarrollo
- 🇩🇪 **Deutsch** (de_DE) - Planeado

### 📝 Contribuir con Traducciones

1. **Duplicar archivo base**
   ```bash
   cp languages/club-psychology-pro.pot languages/club-psychology-pro-{locale}.po
   ```

2. **Traducir con Poedit**
   - Abrir archivo `.po` en [Poedit](https://poedit.net/)
   - Traducir strings
   - Guardar (genera automáticamente el `.mo`)

3. **Enviar Pull Request**

## 🧪 Testing

### 🔬 Ejecutar Tests

```bash
# Tests unitarios
composer test

# Tests de integración
composer test:integration

# Tests con coverage
composer test:coverage

# Tests específicos
./vendor/bin/phpunit tests/Unit/Tests/CalculatorTest.php
```

### 📊 Coverage Actual

- **Unit Tests**: 87% coverage
- **Integration Tests**: 73% coverage
- **Feature Tests**: 65% coverage

### 🎯 Escribir Tests

```php
// tests/Unit/CalculatorTest.php
class AngerRuminationCalculatorTest extends TestCase {
    public function test_calculates_total_score_correctly() {
        $calculator = new AngerRuminationCalculator();
        $responses = ['ARS1' => 3, 'ARS2' => 2, /* ... */];
        
        $result = $calculator->calculate($responses);
        
        $this->assertEquals(45, $result['total_score']);
        $this->assertEquals('Moderate', $result['level']);
    }
}
```

## 🤝 Contribuir

### 🛠️ Configuración de Desarrollo

1. **Fork del repositorio**
2. **Clonar tu fork**
   ```bash
   git clone https://github.com/tu-usuario/club-psychology-pro.git
   cd club-psychology-pro
   ```

3. **Instalar dependencias**
   ```bash
   composer install
   cd assets && npm install
   ```

4. **Configurar pre-commit hooks**
   ```bash
   composer install-hooks
   ```

### 📋 Estándares de Código

- **PSR-4** para autoloading
- **PSR-12** para estilo de código
- **WordPress Coding Standards**
- **PHPDoc** para documentación
- **Semantic Versioning** para releases

### 🔄 Workflow

1. **Crear branch feature**
   ```bash
   git checkout -b feature/nueva-funcionalidad
   ```

2. **Desarrollar con TDD**
   ```bash
   # Escribir test primero
   # Implementar funcionalidad
   # Refactorizar
   ```

3. **Commit semántico**
   ```bash
   git commit -m "feat: agregar test de personalidad MBTI"
   git commit -m "fix: corregir cálculo en PHQ-9"
   git commit -m "docs: actualizar README con ejemplos"
   ```

4. **Push y Pull Request**

## 📚 Documentación Adicional

### 📖 Guías Detalladas

- 🚀 [**Guía de Instalación**](docs/installation.md)
- ⚙️ [**Configuración Avanzada**](docs/configuration.md)
- 🧪 [**Desarrollo de Tests**](docs/test-development.md)
- 🎨 [**Personalización de UI**](docs/ui-customization.md)
- 📡 [**Referencia de API**](docs/api-reference.md)
- 🔧 [**Troubleshooting**](docs/troubleshooting.md)

### 🎓 Tutoriales

- [Crear tu primer test personalizado](docs/tutorials/custom-test.md)
- [Integrar con sistemas externos](docs/tutorials/external-integration.md)
- [Configurar WhatsApp Notifications](docs/tutorials/whatsapp-setup.md)

## 🆘 Soporte

### 🐛 Reportar Bugs

1. **Verificar issues existentes**
2. **Crear nuevo issue** con:
   - Descripción detallada
   - Pasos para reproducir
   - Versiones (WordPress, PHP, Plugin)
   - Screenshots si aplica

### 💬 Comunidad

- 💬 **Discord**: [Club Psychology Pro](https://discord.gg/psychology-pro)
- 📧 **Email**: support@club-psychology-pro.com
- 📱 **WhatsApp**: +1234567890 (solo soporte premium)

### 🎯 Roadmap

#### v2.1.0 (Q2 2024)
- ✅ Tests MBTI y Enneagram
- ✅ Dashboard analytics avanzado
- ✅ Export PDF de resultados
- ✅ API GraphQL

#### v2.2.0 (Q3 2024)
- 📱 App móvil companion
- 🤖 Interpretaciones con IA
- 📊 Dashboards interactivos
- 🔗 Integración con Zoom/Teams

## 📄 Licencia

Este proyecto está licenciado bajo [GPL-2.0+](LICENSE) - ver el archivo LICENSE para detalles.

### 🏢 Uso Comercial

Para uso comercial o licencias enterprise, contactar:
- **Email**: licensing@club-psychology-pro.com
- **Website**: [www.club-psychology-pro.com](https://club-psychology-pro.com)

## 🙏 Reconocimientos

### 🎓 Tests Psicológicos

- **BigFive**: International Personality Item Pool (IPIP)
- **PHQ-SADS**: Pfizer Inc. y Dr. Robert L. Spitzer
- **WHO-5**: Organización Mundial de la Salud
- **GEQ**: Albert Carron & Lawrence Brawley

### 🛠️ Tecnologías

- **WordPress**: CMS base
- **Baileys**: WhatsApp Web API
- **Chart.js**: Visualizaciones
- **Composer**: Dependency management
- **Webpack**: Asset bundling

### 👥 Contribuidores

<a href="https://github.com/tu-usuario/club-psychology-pro/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=tu-usuario/club-psychology-pro" />
</a>

---

<p align="center">
  <sub>Construido con ❤️ </sub><br>
  <sub>© 2024 Club Psychology Pro BizID. Todos los derechos reservados.</sub>
</p>