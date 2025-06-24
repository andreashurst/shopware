/**
 * AndreasHurst Mockup Plugin - Cookie Banner Hider
 * Automatically hides common cookie consent banners for cleaner mockup presentations
 */

(function() {
    'use strict';
    
    // Common cookie banner selectors
    const cookieBannerSelectors = [
        // Generic cookie banners
        '.cookie-banner',
        '.cookie-consent',
        '.cookie-notice',
        '.cookie-bar',
        '.cookie-popup',
        '.gdpr-banner',
        '.gdpr-consent',
        '.privacy-banner',
        '.consent-banner',
        '.consent-popup',
        
        // Shopware specific
        '.cms-block-cookie-consent',
        '.cookie-consent-manager',
        '.cookie-permission-container',
        '.offcanvas-cookie',
        
        // Popular cookie consent tools
        '.cc-banner', // Cookie Consent
        '.cookiebot-banner',
        '.cookieFirst-banner',
        '.onetrust-banner-sdk',
        '.ot-sdk-container',
        '.cmp-banner',
        '.didomi-notice',
        '.usercentrics-root',
        '.sp_message_container',
        
        // By ID
        '#cookie-banner',
        '#cookie-consent',
        '#cookie-notice',
        '#gdpr-banner',
        '#cookieConsent',
        '#CookieBoxContainer',
        '#cookieChoiceInfo',
        
        // By class patterns
        '[class*="cookie"]',
        '[class*="gdpr"]',
        '[class*="consent"]',
        '[id*="cookie"]',
        '[id*="gdpr"]',
        '[id*="consent"]'
    ];
    
    // Function to hide cookie banners
    function hideCookieBanners() {
        cookieBannerSelectors.forEach(function(selector) {
            try {
                const elements = document.querySelectorAll(selector);
                elements.forEach(function(element) {
                    // Check if element looks like a cookie banner
                    const text = element.textContent || element.innerText || '';
                    const lowerText = text.toLowerCase();
                    
                    // Keywords that indicate a cookie banner
                    const cookieKeywords = [
                        'cookie', 'cookies', 'gdpr', 'privacy', 'consent',
                        'datenschutz', 'einverständnis', 'zustimmung',
                        'tracking', 'analytics', 'marketing'
                    ];
                    
                    const isCookieBanner = cookieKeywords.some(keyword => 
                        lowerText.includes(keyword)
                    );
                    
                    // Hide if it's likely a cookie banner
                    if (isCookieBanner || selector.includes('cookie') || selector.includes('gdpr') || selector.includes('consent')) {
                        element.style.display = 'none !important';
                        element.style.visibility = 'hidden !important';
                        element.style.opacity = '0 !important';
                        element.style.position = 'absolute !important';
                        element.style.left = '-9999px !important';
                        element.style.top = '-9999px !important';
                        element.style.zIndex = '-1 !important';
                        
                        // Also try to remove it completely
                        try {
                            element.remove();
                        } catch (e) {
                            // Ignore removal errors
                        }
                    }
                });
            } catch (e) {
                // Ignore selector errors
            }
        });
    }
    
    // Function to add CSS to hide cookie banners
    function addCookieHiderCSS() {
        const style = document.createElement('style');
        style.textContent = `
            /* Hide common cookie banner patterns */
            .cookie-banner,
            .cookie-consent,
            .cookie-notice,
            .cookie-bar,
            .cookie-popup,
            .gdpr-banner,
            .gdpr-consent,
            .privacy-banner,
            .consent-banner,
            .consent-popup,
            .cms-block-cookie-consent,
            .cookie-consent-manager,
            .cookie-permission-container,
            .offcanvas-cookie,
            .cc-banner,
            .cookiebot-banner,
            .cookieFirst-banner,
            .onetrust-banner-sdk,
            .ot-sdk-container,
            .cmp-banner,
            .didomi-notice,
            .usercentrics-root,
            .sp_message_container,
            #cookie-banner,
            #cookie-consent,
            #cookie-notice,
            #gdpr-banner,
            #cookieConsent,
            #CookieBoxContainer,
            #cookieChoiceInfo {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                position: absolute !important;
                left: -9999px !important;
                top: -9999px !important;
                z-index: -1 !important;
                width: 0 !important;
                height: 0 !important;
                overflow: hidden !important;
            }
            
            /* Remove any backdrop/overlay that might remain */
            .cookie-backdrop,
            .consent-backdrop,
            .gdpr-backdrop,
            .modal-backdrop.show {
                display: none !important;
            }
            
            /* Ensure body scrolling is not blocked */
            body.modal-open,
            body.cookie-consent-open,
            body.gdpr-open {
                overflow: auto !important;
                padding-right: 0 !important;
            }
        `;
        
        document.head.appendChild(style);
    }
    
    // Run immediately
    addCookieHiderCSS();
    hideCookieBanners();
    
    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            hideCookieBanners();
        });
    }
    
    // Run when page is fully loaded
    window.addEventListener('load', function() {
        hideCookieBanners();
    });
    
    // Monitor for dynamically added cookie banners
    if (window.MutationObserver) {
        const observer = new MutationObserver(function(mutations) {
            let shouldCheck = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // Check if any added nodes might be cookie banners
                    for (let node of mutation.addedNodes) {
                        if (node.nodeType === 1) { // Element node
                            const text = node.textContent || '';
                            if (text.toLowerCase().includes('cookie') || 
                                text.toLowerCase().includes('gdpr') || 
                                text.toLowerCase().includes('consent')) {
                                shouldCheck = true;
                                break;
                            }
                        }
                    }
                }
            });
            
            if (shouldCheck) {
                setTimeout(hideCookieBanners, 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Also run periodically to catch any delayed cookie banners
    setInterval(hideCookieBanners, 2000);
    
})();