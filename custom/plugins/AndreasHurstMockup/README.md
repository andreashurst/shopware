# AndreasHurst Mockup Plugin

Ein Shopware 6 Plugin zur Darstellung von Webseiten in verschiedenen Device-Mockups.

## Beschreibung

Das AndreasHurst Mockup Plugin ermöglicht es, Webseiten in verschiedenen Device-Mockups anzuzeigen. Perfekt für Präsentationen, um zu zeigen, wie eine Website auf verschiedenen Geräten aussieht.

## Features

- **Responsive Mockups**: Zeigt Websites in verschiedenen Device-Frames an
- **Multiple Layouts**: Unterstützt verschiedene Mockup-Layouts:
  - Responsive (Standard)
  - Triple Layout (3 Devices)
  - Quad Layout (4 Devices)
  - Five Layout (5 Devices)
  - Six Layout (6 Devices)
  - Twelve Layout (12 Devices)
- **Device Varianten**: Unterstützt verschiedene Geräte-Mockups:
  - Desktop
  - Laptop
  - Tablet (Portrait & Landscape)
  - Mobile Devices
  - iPhone Modelle (4, 5, 6, 6 Plus, 11 Pro, 12, 12 Max)
  - Ultrawide Displays

## Installation

1. Plugin in den `custom/plugins/` Ordner kopieren
2. Plugin über die Shopware Administration oder CLI installieren:
   ```bash
   bin/console plugin:install --activate AndreasHurstMockup
   ```
3. Cache leeren:
   ```bash
   bin/console cache:clear
   ```

## Verwendung

### Grundlegende Nutzung
Das Plugin funktioniert über Query-Parameter auf **jeder beliebigen Seite** Ihrer Shopware-Installation. Fügen Sie einfach den `mockup` Parameter zu einer URL hinzu:

```
https://ihre-domain.de/?mockup=quad
https://ihre-domain.de/account?mockup=responsive
https://ihre-domain.de/search?mockup=six
```

### Parameter

#### mockup (Layout-Typ)
Bestimmt das Layout der Mockup-Anzeige:
- `responsive` (Standard) - Responsive Layout
- `trible` - 3er Layout
- `quad` - 4er Layout
- `five` - 5er Layout
- `six` - 6er Layout
- `twelve` - 12er Layout

**Wichtig**: Das Plugin funktioniert auf **jeder URL** - Sie müssen nicht zu einer speziellen Mockup-Route navigieren.

### Beispiele

#### Startseite mit Quad-Layout
```
https://ihre-domain.de/?mockup=quad
```

#### Produktseite mit Responsive-Layout
```
https://ihre-domain.de/product/beispiel-produkt?mockup=responsive
```

#### Kategorieseite mit Six-Layout
```
https://ihre-domain.de/kategorie/electronics?mockup=six
```

#### Bestehende Parameter bleiben erhalten
```
https://ihre-domain.de/search?search=laptop&mockup=twelve
```

### Funktionsweise

1. **Automatische Erkennung**: Das Plugin erkennt automatisch den `mockup` Parameter in der URL
2. **Iframe-Embedding**: Die ursprüngliche Seite wird in Device-Mockups als iframes eingebettet
3. **Saubere URLs**: Die iframes laden die ursprüngliche Seite ohne den `mockup` Parameter
4. **X-Frame-Options**: Automatische Anpassung der Header für iframe-Kompatibilität

## Technische Details

### Systemanforderungen
- Shopware 6.7.0 oder höher
- PHP 8.0 oder höher

### Dateistruktur
```
src/
├── AndreasHurstMockup.php          # Haupt-Plugin-Klasse
├── Subscriber/
│   └── MockupSubscriber.php       # Event Subscriber für Query-Parameter-Erkennung
└── Resources/
    ├── Css/                        # CSS-Dateien für verschiedene Mockups
    ├── Images/                     # Device-Mockup Bilder
    ├── JavaScript/                 # JavaScript-Dateien
    ├── config/
    │   └── services.xml           # Service-Konfiguration
    └── views/
        └── storefront/
            ├── layout/
            │   └── mockup-base.html.twig
            └── page/
                └── mockup/        # Templates für verschiedene Layouts
```

### Architektur
Das Plugin nutzt einen **Event Subscriber** (`MockupSubscriber`), der auf `KernelEvents::RESPONSE` reagiert und:
1. Alle GET-Requests mit `mockup` Parameter abfängt
2. Die X-Frame-Options Header für iframe-Kompatibilität anpasst
3. Den `mockup` Parameter aus den iframe-URLs entfernt
4. Das entsprechende Mockup-Template rendert

## CSS-Anpassungen

Das Plugin bietet verschiedene CSS-Dateien für:
- **Device-spezifische Stile**: `setup.{device}.css`
- **Layout-spezifische Stile**: `mockup.{layout}.css`
- **Responsive Skalierung**: `all.scale.{size}.css`
- **Scroll-Verhalten**: `scroll.css`, `scroll.laptops.css`

## Anpassung

### Templates erweitern
Die Twig-Templates können über das Theme-System erweitert werden:
```twig
{# templates/storefront/page/mockup/custom.html.twig #}
{% extends "@AndreasHurstMockup/storefront/page/mockup/responsive.html.twig" %}

{% block mockup_content %}
    {# Ihre Anpassungen #}
{% endblock %}
```

### Neue Mockup-Typen hinzufügen
1. Neues Template in `src/Resources/views/storefront/page/mockup/` erstellen
2. CSS-Datei in `src/Resources/Css/` hinzufügen
3. `MockupSubscriber::getTemplateForMockupType()` erweitern

## Support

Für Support und Fragen wenden Sie sich an:
- **Website**: https://www.andreashurst.com
- **Version**: 1.0.0
- **Lizenz**: GPL-2.0-or-later

## Changelog

### Version 1.0.0
- Initiale Veröffentlichung
- Event Subscriber-basierte Architektur
- Query-Parameter-Funktionalität auf jeder Seite
- Automatische X-Frame-Options Anpassung
- URL-Bereinigung für iframe-Embedding
- Unterstützung für verschiedene Device-Mockups
- Multiple Layout-Optionen (responsive, trible, quad, five, six, twelve)
- Responsive Design