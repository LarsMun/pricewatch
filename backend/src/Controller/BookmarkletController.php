<?php

namespace App\Controller;

use App\Scraper\HttpEngine;
use App\Scraper\PriceExtractor;
use App\Service\UrlValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class BookmarkletController extends AbstractController
{
    public function __construct(
        private HttpEngine $httpEngine,
        private PriceExtractor $priceExtractor,
        private UrlValidator $urlValidator,
        private RateLimiterFactory $validateEndpointLimiter,
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%env(FRONTEND_URL)%')]
        private string $frontendUrl,
    ) {}

    #[Route('/watches/validate', name: 'api_watches_validate', methods: ['POST'])]
    public function validate(Request $request): JsonResponse
    {
        // Rate limit check per IP
        $limiter = $this->validateEndpointLimiter->create($request->getClientIp() ?? 'unknown');
        if (!$limiter->consume()->isAccepted()) {
            return $this->json([
                'error' => 'Te veel verzoeken. Wacht even voordat je het opnieuw probeert.'
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $url = $data['url'] ?? null;
        $selector = $data['selector'] ?? null;

        if (!$url || !$selector) {
            return $this->json([
                'error' => 'url en selector zijn verplicht',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->json([
                'success' => false,
                'error' => 'Ongeldige URL',
            ]);
        }

        // SSRF protection
        try {
            $this->urlValidator->validate($url);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }

        $scrapeResult = $this->httpEngine->fetch($url);

        if (!$scrapeResult->success) {
            return $this->json([
                'success' => false,
                'error' => 'Kon pagina niet ophalen: ' . $scrapeResult->error,
            ]);
        }

        $extractResult = $this->priceExtractor->extract($scrapeResult->html, $selector);

        if (!$extractResult->success) {
            return $this->json([
                'success' => false,
                'error' => $extractResult->error,
            ]);
        }

        return $this->json([
            'success' => true,
            'price' => $extractResult->price,
            'rawText' => $extractResult->rawText,
            'domain' => parse_url($url, PHP_URL_HOST),
        ]);
    }

    #[Route('/bookmarklet.js', name: 'api_bookmarklet_js', methods: ['GET'])]
    public function bookmarkletJs(Request $request): Response
    {
        $js = $this->generateBookmarkletCode();

        return new Response($js, 200, [
            'Content-Type' => 'application/javascript',
            'Cache-Control' => 'no-cache',
        ]);
    }

    private function generateBookmarkletCode(): string
    {
        $frontendUrl = json_encode($this->frontendUrl);

        return "(function() {
    try {
        if (window.__prijswacht_active) {
            alert('PrijsWacht is al actief op deze pagina');
            return;
        }
        window.__prijswacht_active = true;

        var FRONTEND_URL = {$frontendUrl};

        // Check for JSON-LD product data first
        function findJsonLdPrice() {
            var scripts = document.querySelectorAll('script[type=\"application/ld+json\"]');
            for (var i = 0; i < scripts.length; i++) {
                try {
                    var data = JSON.parse(scripts[i].textContent);
                    if (data.offers && data.offers.price) {
                        return { price: data.offers.price, selector: 'jsonld:offers.price', name: data.name };
                    }
                    if (data['@graph']) {
                        for (var j = 0; j < data['@graph'].length; j++) {
                            var item = data['@graph'][j];
                            if (item.offers && item.offers.price) {
                                return { price: item.offers.price, selector: 'jsonld:offers.price', name: item.name };
                            }
                        }
                    }
                } catch(e) {}
            }
            return null;
        }

        var jsonLdData = findJsonLdPrice();

        var style = document.createElement('style');
        style.id = 'prijswacht-style';
        style.textContent = '#prijswacht-dialog { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); font-family: -apple-system, BlinkMacSystemFont, sans-serif; min-width: 340px; max-width: 90vw; z-index: 2147483647; } #prijswacht-dialog h2 { margin: 0 0 10px 0; font-size: 18px; color: #111; } #prijswacht-dialog p { margin: 0 0 15px 0; color: #666; font-size: 14px; } .prijswacht-btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; } .prijswacht-btn-primary { background: #2563eb; color: white; } .prijswacht-btn-success { background: #10b981; color: white; } .prijswacht-btn-secondary { background: #f3f4f6; color: #374151; } .prijswacht-hover { outline: 3px solid #2563eb !important; outline-offset: 2px; cursor: crosshair !important; background-color: rgba(37, 99, 235, 0.1) !important; } .prijswacht-selected { outline: 3px solid #10b981 !important; outline-offset: 2px; background-color: rgba(16, 185, 129, 0.1) !important; } .prijswacht-price { font-size: 28px; font-weight: bold; color: #10b981; margin: 15px 0; }';
        document.head.appendChild(style);

        var dialog = document.createElement('div');
        dialog.id = 'prijswacht-dialog';
        document.body.appendChild(dialog);

        var lastHovered = null;
        var selectedElement = null;

        function cleanup() {
            window.__prijswacht_active = false;
            document.removeEventListener('mouseover', onMouseOver, true);
            document.removeEventListener('mouseout', onMouseOut, true);
            document.removeEventListener('click', onClick, true);
            if (lastHovered) lastHovered.classList.remove('prijswacht-hover');
            if (selectedElement) selectedElement.classList.remove('prijswacht-selected');
            var d = document.getElementById('prijswacht-dialog');
            if (d) d.remove();
            var s = document.getElementById('prijswacht-style');
            if (s) s.remove();
        }

        function redirect(selector, rawText) {
            var params = new URLSearchParams({
                url: window.location.href,
                selector: selector,
                rawText: rawText,
                title: document.title
            });
            window.location.href = FRONTEND_URL + '/add-watch?' + params.toString();
        }

        // If JSON-LD found, show that option first
        if (jsonLdData) {
            dialog.innerHTML = '<h2>Prijs automatisch gevonden!</h2>' +
                '<p>Deze site heeft gestructureerde data (JSON-LD). Dit werkt het beste.</p>' +
                '<div class=\"prijswacht-price\">€ ' + jsonLdData.price + '</div>' +
                '<div style=\"display:flex;gap:10px;margin-top:15px;\">' +
                '<button class=\"prijswacht-btn prijswacht-btn-success\" id=\"prijswacht-use-jsonld\" style=\"flex:1;\">Gebruiken (aanbevolen)</button>' +
                '<button class=\"prijswacht-btn prijswacht-btn-secondary\" id=\"prijswacht-manual\">Handmatig selecteren</button>' +
                '</div>';

            document.getElementById('prijswacht-use-jsonld').onclick = function() {
                redirect(jsonLdData.selector, jsonLdData.price.toString());
            };

            document.getElementById('prijswacht-manual').onclick = function() {
                showManualSelection();
            };

            console.log('PrijsWacht: JSON-LD prijs gevonden:', jsonLdData.price);
            return;
        }

        function showManualSelection() {
            dialog.innerHTML = '<h2>PrijsWacht</h2><p>Klik op het prijselement dat je wilt volgen</p><button class=\"prijswacht-btn prijswacht-btn-secondary\" id=\"prijswacht-cancel\">Annuleren</button>';
            document.getElementById('prijswacht-cancel').onclick = cleanup;
            document.addEventListener('mouseover', onMouseOver, true);
            document.addEventListener('mouseout', onMouseOut, true);
            document.addEventListener('click', onClick, true);
        }

        function isDialog(el) {
            return el && (el.id === 'prijswacht-dialog' || el.closest('#prijswacht-dialog'));
        }

        function onMouseOver(e) {
            if (isDialog(e.target)) return;
            if (lastHovered && lastHovered !== e.target) lastHovered.classList.remove('prijswacht-hover');
            e.target.classList.add('prijswacht-hover');
            lastHovered = e.target;
        }

        function onMouseOut(e) {
            if (isDialog(e.target)) return;
            e.target.classList.remove('prijswacht-hover');
        }

        function genSelector(el) {
            if (el.id) return '#' + el.id;
            if (el.className && typeof el.className === 'string') {
                var cls = el.className.trim().split(/\\s+/).filter(function(c) { return c && c.length > 2 && !c.startsWith('prijswacht'); }).slice(0, 2);
                if (cls.length) {
                    var sel = '.' + cls.join('.');
                    try { if (document.querySelectorAll(sel).length <= 5) return sel; } catch(e) {}
                }
            }
            var path = [], cur = el;
            while (cur && cur !== document.body && path.length < 4) {
                var tag = cur.tagName.toLowerCase();
                if (cur.id) { path.unshift('#' + cur.id); break; }
                var parent = cur.parentElement;
                if (parent) {
                    var sibs = Array.from(parent.children).filter(function(c) { return c.tagName === cur.tagName; });
                    if (sibs.length > 1) tag += ':nth-of-type(' + (sibs.indexOf(cur) + 1) + ')';
                }
                path.unshift(tag);
                cur = parent;
            }
            return path.join(' > ');
        }

        function onClick(e) {
            if (isDialog(e.target)) return;
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            document.removeEventListener('mouseover', onMouseOver, true);
            document.removeEventListener('mouseout', onMouseOut, true);
            document.removeEventListener('click', onClick, true);

            var el = e.target;
            if (lastHovered) lastHovered.classList.remove('prijswacht-hover');
            el.classList.add('prijswacht-selected');
            selectedElement = el;

            var selector = genSelector(el);
            var rawText = el.textContent.trim().substring(0, 100);

            dialog.innerHTML = '<h2>Element geselecteerd</h2>' +
                '<div class=\"prijswacht-price\">' + rawText + '</div>' +
                '<p style=\"font-size:11px;color:#999;word-break:break-all;\">Selector: ' + selector + '</p>' +
                '<p style=\"font-size:12px;color:#f59e0b;margin:10px 0;\">Let op: Dit werkt alleen als de prijs in de HTML staat (niet via JavaScript geladen)</p>' +
                '<div style=\"display:flex;gap:10px;margin-top:15px;\">' +
                '<button class=\"prijswacht-btn prijswacht-btn-primary\" id=\"prijswacht-confirm\" style=\"flex:1;\">Toevoegen</button>' +
                '<button class=\"prijswacht-btn prijswacht-btn-secondary\" id=\"prijswacht-retry\">Opnieuw</button>' +
                '</div>';

            document.getElementById('prijswacht-confirm').onclick = function() {
                redirect(selector, rawText);
            };

            document.getElementById('prijswacht-retry').onclick = function() {
                if (selectedElement) { selectedElement.classList.remove('prijswacht-selected'); selectedElement = null; }
                showManualSelection();
            };

            return false;
        }

        // No JSON-LD found, start manual selection
        showManualSelection();
        console.log('PrijsWacht: Geen JSON-LD gevonden, handmatige selectie gestart');

    } catch(err) {
        alert('PrijsWacht fout: ' + err.message);
        console.error('PrijsWacht error:', err);
    }
})();";
    }
}
