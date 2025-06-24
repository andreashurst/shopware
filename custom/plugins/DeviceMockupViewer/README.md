# DeviceMockupViewer Plugin

A modern Shopware 6 plugin for displaying websites in realistic device mockups with clean CSS architecture and comprehensive device support.

## 📱 Features

- **46+ Device Mockups**: From iPhone 4 to iPhone 15 Pro Max, Samsung Galaxy, Google Pixel, iPads, MacBooks, and more
- **Query Parameter Based**: Works on any page with simple `?mockup=device` parameter
- **Multi-Device Layouts**: Responsive, quad, trible, five, six, and twelve device layouts
- **Modern CSS Architecture**: Modular CSS with helper classes and reusable components
- **Responsive Design**: Automatic scaling for different screen sizes
- **Cookie Banner Hiding**: Automatic removal of consent banners for clean presentations
- **Clean URLs**: Iframe sources automatically cleaned of mockup parameters

## 🚀 Installation

1. **Copy Plugin Files**
   ```bash
   # Copy plugin to custom/plugins directory
   cp -r DeviceMockupViewer /var/www/html/custom/plugins/
   ```

2. **Install Plugin**
   ```bash
   # Install and activate via CLI
   bin/console plugin:install --activate DeviceMockupViewer
   
   # Install CSS assets
   bin/console assets:install
   
   # Clear cache
   bin/console cache:clear
   ```

3. **Alternative: Admin Installation**
   - Go to Shopware Admin → Extensions → My Extensions
   - Click "Install" on DeviceMockupViewer
   - Activate the plugin

## 📖 Usage

### Basic Usage

Add the `mockup` parameter to any URL in your Shopware store:

```
https://your-domain.com/?mockup=iphone15pro
https://your-domain.com/product/example?mockup=galaxys24
https://your-domain.com/category/electronics?mockup=macbookpro16
```

### Single Device Mockups

#### 📱 iPhone Models
```
?mockup=iphone4          # iPhone 4
?mockup=iphone5          # iPhone 5
?mockup=iphone6          # iPhone 6
?mockup=iphone6plus      # iPhone 6 Plus
?mockup=iphone11pro      # iPhone 11 Pro
?mockup=iphone12         # iPhone 12
?mockup=iphone12max      # iPhone 12 Max
?mockup=iphone13         # iPhone 13
?mockup=iphone13mini     # iPhone 13 Mini
?mockup=iphone13pro      # iPhone 13 Pro
?mockup=iphone13promax   # iPhone 13 Pro Max
?mockup=iphone14         # iPhone 14
?mockup=iphone14plus     # iPhone 14 Plus
?mockup=iphone14pro      # iPhone 14 Pro (with Dynamic Island)
?mockup=iphone14promax   # iPhone 14 Pro Max (with Dynamic Island)
?mockup=iphone15         # iPhone 15
?mockup=iphone15plus     # iPhone 15 Plus
?mockup=iphone15pro      # iPhone 15 Pro (with Dynamic Island)
?mockup=iphone15promax   # iPhone 15 Pro Max (with Dynamic Island)
```

#### 🤖 Android Devices
```
?mockup=galaxys21        # Samsung Galaxy S21
?mockup=galaxys22        # Samsung Galaxy S22
?mockup=galaxys23        # Samsung Galaxy S23
?mockup=galaxys24        # Samsung Galaxy S24
?mockup=galaxynote20     # Samsung Galaxy Note 20 (with S-Pen)
?mockup=galaxyfold       # Samsung Galaxy Fold (dual screens)
?mockup=pixel6           # Google Pixel 6
?mockup=pixel7           # Google Pixel 7
?mockup=pixel8           # Google Pixel 8
?mockup=pixel8pro        # Google Pixel 8 Pro (with camera bar)
```

#### 📟 Tablets
```
?mockup=ipad             # iPad (classic)
?mockup=ipadmini         # iPad Mini
?mockup=ipadair          # iPad Air
?mockup=ipadpro11        # iPad Pro 11"
?mockup=ipadpro13        # iPad Pro 13"
?mockup=surfacepro       # Microsoft Surface Pro
```

#### 💻 Laptops & Desktops
```
?mockup=macbookair       # MacBook Air
?mockup=macbookpro14     # MacBook Pro 14"
?mockup=macbookpro16     # MacBook Pro 16" (with notch)
?mockup=surfacelaptop    # Microsoft Surface Laptop
?mockup=surfacestudio    # Microsoft Surface Studio
?mockup=laptop           # Generic Laptop
?mockup=desktop          # Desktop Computer
?mockup=ultrawide        # Ultrawide Monitor
?mockup=monitor4k        # 4K Monitor
```

#### 🎮 Gaming Devices
```
?mockup=steamdeck        # Steam Deck (with controls)
?mockup=nintendoswitch   # Nintendo Switch (with Joy-Cons)
```

#### 📺 Smart TVs
```
?mockup=smarttv55        # 55" Smart TV
?mockup=smarttv65        # 65" Smart TV (with stand)
?mockup=appletvscreen    # Apple TV Screen
```

### Multi-Device Layouts

#### Responsive Layout (Desktop + Tablet + Phone)
```
?mockup=responsive
```

#### Grid Layouts
```
?mockup=quad             # 2x2 grid (4 devices)
?mockup=trible           # 3 devices (Desktop + Tablet + Phone)
?mockup=five             # 5 devices in cross pattern
?mockup=six              # 2x3 grid (6 devices)
?mockup=twelve           # 3x4 grid (12 devices)
```

## 🏗️ Technical Architecture

### Plugin Structure
```
DeviceMockupViewer/
├── composer.json                           # Plugin configuration
├── src/
│   ├── DeviceMockupViewer.php             # Main plugin class
│   ├── Resources/
│   │   ├── config/
│   │   │   └── services.xml               # Service configuration
│   │   ├── public/
│   │   │   └── css/                       # CSS assets
│   │   │       ├── base.css               # Base styles & variables
│   │   │       ├── layouts.css            # Multi-device layouts
│   │   │       └── devices/               # Device-specific styles
│   │   │           ├── iphone.css         # iPhone styles
│   │   │           ├── android.css        # Android styles
│   │   │           ├── tablet.css         # Tablet styles
│   │   │           ├── laptop.css         # Laptop/Desktop styles
│   │   │           ├── gaming.css         # Gaming device styles
│   │   │           └── tv.css             # TV styles
│   │   └── views/
│   │       └── storefront/
│   │           ├── layout/
│   │           │   └── base.html.twig     # Base template
│   │           ├── device/                # Single device templates
│   │           │   ├── iphone15pro.html.twig
│   │           │   ├── galaxys24.html.twig
│   │           │   └── ...                # (46+ device templates)
│   │           └── layout/                # Multi-device templates
│   │               ├── responsive.html.twig
│   │               ├── quad.html.twig
│   │               └── ...
│   └── Subscriber/
│       └── MockupSubscriber.php           # Event subscriber
└── README.md                              # This file
```

### CSS Architecture

#### CSS Variables (base.css)
```css
:root {
    /* Device Colors */
    --device-dark: linear-gradient(145deg, #2c2c2e, #1c1c1e);
    --device-darker: linear-gradient(145deg, #2a2a2a, #1a1a1a);
    --device-border: #4a4a4c;
    
    /* Shadows */
    --shadow-device: 0 25px 50px rgba(0, 0, 0, 0.5);
    --shadow-light: 0 20px 40px rgba(0, 0, 0, 0.4);
    
    /* Border Radius */
    --border-radius-large: 50px;
    --border-radius-medium: 25px;
    --border-radius-small: 15px;
}
```

#### Helper Classes
```css
/* Device base styles */
.device-base          /* Standard device styling */
.device-base-thin     /* Thin border variant */
.screen-base          /* Screen container */

/* Button helpers */
.button-side          /* Base button styling */
.button-left          /* Left side button */
.button-right         /* Right side button */
.button-left-thin     /* Thin left button */
.button-right-thin    /* Thin right button */

/* Camera elements */
.dynamic-island       /* iPhone 14+ Dynamic Island */
.camera-punch         /* Android camera hole */
.camera-bar           /* Pixel camera bar */
.camera-lens          /* Individual camera lens */

/* Responsive scaling */
.scale-1400          /* Scale at 1400px breakpoint */
.scale-1200          /* Scale at 1200px breakpoint */
.scale-900           /* Scale at 900px breakpoint */
.scale-600           /* Scale at 600px breakpoint */
.scale-480           /* Scale at 480px breakpoint */
.scale-360           /* Scale at 360px breakpoint */
```

### Event Subscriber

The `MockupSubscriber` handles all mockup functionality:

```php
class MockupSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // 1. Allow iframe embedding
        // 2. Detect mockup parameter
        // 3. Clean URLs for iframes
        // 4. Render appropriate template
    }
}
```

## 🎨 Device Features

### iPhone Models
- **Classic Models**: Home buttons, Touch ID
- **Modern Models**: Face ID, notches
- **Pro Models**: Dynamic Island (iPhone 14 Pro+)
- **Realistic Buttons**: Volume, power, silent switch
- **Proper Scaling**: Responsive design

### Android Devices
- **Samsung Galaxy**: Camera punch holes, S-Pen slot (Note)
- **Google Pixel**: Distinctive camera bars
- **Galaxy Fold**: Dual-screen foldable design
- **Modern Design**: Curved edges, realistic proportions

### Tablets
- **iPads**: Home buttons (older), Face ID (newer)
- **Surface Pro**: Windows design, kickstand
- **Proper Ratios**: Accurate screen proportions

### Laptops
- **MacBooks**: 3D perspective, notches (newer models)
- **Surface Devices**: Windows aesthetic
- **Trackpads**: Realistic positioning
- **Perspective**: 3D laptop view

### Gaming Devices
- **Steam Deck**: Full control layout, trackpads
- **Nintendo Switch**: Joy-Con controllers, action buttons
- **Realistic Controls**: Analog sticks, D-pads

### Smart TVs
- **Modern Design**: Slim bezels, stands
- **Brand Elements**: Realistic TV aesthetics
- **Large Screens**: Proper scaling for TV viewing

## 🔧 Development

### Adding New Devices

1. **Create Template**
   ```twig
   {% extends "@DeviceMockupViewer/storefront/layout/base.html.twig" %}
   
   {% block title %}New Device Mockup{% endblock %}
   
   {% block stylesheets %}
       {{ parent() }}
       <link rel="stylesheet" href="{{ asset('bundles/devicemockupviewer/css/devices/category.css') }}">
   {% endblock %}
   
   {% block content %}
   <div class="device-container">
       <div class="new-device device-base scale-480 scale-360">
           <div class="screen screen-base">
               <iframe src="{{ cleanUrl }}" title="New Device Mockup"></iframe>
           </div>
       </div>
   </div>
   {% endblock %}
   ```

2. **Add CSS Styles**
   ```css
   .new-device {
       width: 400px;
       height: 800px;
       background: var(--device-dark);
       border-radius: var(--border-radius-medium);
       padding: 8px;
   }
   ```

3. **Register in Subscriber**
   ```php
   private function getTemplateForMockupType(string $mockupType): ?string
   {
       $deviceTemplates = [
           // ... existing devices
           'newdevice' => '@DeviceMockupViewer/storefront/device/newdevice.html.twig',
       ];
       
       return $deviceTemplates[$mockupType] ?? null;
   }
   ```

### Customizing Styles

Modify CSS variables in `base.css`:

```css
:root {
    --device-dark: your-custom-gradient;
    --bg-gradient: your-background;
    --shadow-device: your-shadow;
}
```

## 🔒 Security

- **X-Frame-Options**: Automatically set to `SAMEORIGIN`
- **URL Cleaning**: Mockup parameters removed from iframe sources
- **Input Validation**: Template mapping prevents arbitrary file access
- **No External Dependencies**: Self-contained plugin

## 📊 Performance

- **External CSS**: Cacheable stylesheets
- **Minimal JavaScript**: Only cookie banner hiding
- **Optimized Templates**: Clean HTML structure
- **Responsive Images**: Proper scaling at all sizes

## 🤝 Browser Support

- **Modern Browsers**: Chrome, Firefox, Safari, Edge
- **Responsive Design**: Mobile, tablet, desktop
- **CSS Grid/Flexbox**: Modern layout support
- **HTML5**: Semantic markup

## 🆕 Changelog

### Version 1.0.0
- Initial release with 46+ device mockups
- Modern CSS architecture with helper classes
- Event subscriber-based functionality  
- Query parameter support on any page
- Automatic cookie banner hiding
- Clean URL generation for iframes
- Responsive scaling system
- Multi-device layout support

## 📝 License

GPL-2.0-or-later

## 👥 Credits

- **Plugin Development**: Claude Code
- **Device Designs**: Inspired by real device specifications
- **CSS Architecture**: Modern helper-based approach
- **Shopware Integration**: Event subscriber pattern

## 🐛 Troubleshooting

### CSS Not Loading
```bash
# Reinstall assets
bin/console assets:install --force
bin/console cache:clear
```

### Templates Not Found
```bash
# Refresh plugin list
bin/console plugin:refresh
bin/console plugin:install --activate DeviceMockupViewer
```

### Iframe Not Displaying
- Check X-Frame-Options headers
- Verify clean URL generation
- Ensure HTTPS consistency

## 📞 Support

For issues and feature requests:
- Check existing device support
- Verify CSS assets are installed
- Clear cache after changes
- Test on different browsers

---

**DeviceMockupViewer** - Professional device mockups for Shopware 6 🚀