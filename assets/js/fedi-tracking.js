/*
 * FediPet - eventos de Meta Pixel (ID 29515692338070174)
 * Requiere que el código base del Pixel esté en el <head> (fbq).
 *
 * index.html:   ViewContent (sección de modelos), InitiateCheckout (clic en COMPRAR),
 *               Contact (clic en WhatsApp). Los botones "Pagar por transferencia" llevan
 *               data-fedi-id / data-fedi-name / data-fedi-value y además mandan
 *               InitiateCheckout (content_category: transferencia), así la campaña
 *               aprende también de quien elige pagar por transferencia.
 * gracias.html: Purchase (llamado desde la propia página con FediTracking.trackPurchase)
 */
(function () {
    var CURRENCY = 'ARS';
    var CHECKOUT_KEY = 'fedi_checkout';
    var CHECKOUT_MAX_AGE_MS = 48 * 60 * 60 * 1000; // 48 horas

    // Código del link de Mercado Pago -> modelo y precio
    var PRODUCTS = {
        '24T6uJi': { id: 'fedi-smart-mini-4l', name: 'Fedi Smart Mini 4L', value: 104100 },
        '1uMtTgG': { id: 'fedi-cam-3l', name: 'Fedi Cam 3L', value: 174900 },
        '2JfFYvK': { id: 'fedi-cam-mini-4l', name: 'Fedi Cam Mini 4L', value: 191600 },
        '2P1U62h': { id: 'super-fedi-vision-6l', name: 'Super Fedi Vision 6L', value: 199900 }
    };

    function track(eventName, params, eventId) {
        if (typeof window.fbq !== 'function') return;
        if (eventId) {
            window.fbq('track', eventName, params || {}, { eventID: eventId });
        } else {
            window.fbq('track', eventName, params || {});
        }
    }

    function storageGet(key) {
        try { return JSON.parse(window.localStorage.getItem(key)); } catch (e) { return null; }
    }

    function storageSet(key, value) {
        try { window.localStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* sin storage */ }
    }

    function findLink(el) {
        while (el && el !== document) {
            if (el.tagName === 'A' && el.href) return el;
            el = el.parentElement;
        }
        return null;
    }

    function productFromHref(href) {
        var match = /mpago\.la\/([A-Za-z0-9]+)/.exec(href || '');
        return match ? PRODUCTS[match[1]] || null : null;
    }

    // Clics en COMPRAR y WhatsApp (fase de captura: corre antes del modal de redirección)
    document.addEventListener('click', function (e) {
        var link = findLink(e.target);
        if (!link) return;
        var href = link.href;

        if (href.indexOf('mpago.la') !== -1) {
            var product = productFromHref(href);
            var params = { content_type: 'product', currency: CURRENCY, num_items: 1 };
            if (product) {
                params.content_ids = [product.id];
                params.content_name = product.name;
                params.value = product.value;
            }
            track('InitiateCheckout', params);
            storageSet(CHECKOUT_KEY, {
                id: product ? product.id : null,
                name: product ? product.name : null,
                value: product ? product.value : null,
                ts: Date.now()
            });
        } else if (href.indexOf('wa.me') !== -1 || href.indexOf('api.whatsapp.com') !== -1) {
            var tId = link.getAttribute('data-fedi-id');
            if (link.getAttribute('data-fedi-step') === 'comprobante') {
                // Botón "Enviar comprobante" de la ventana de transferencia
                track('AddPaymentInfo', {
                    content_type: 'product',
                    content_ids: tId ? [tId] : undefined,
                    content_name: link.getAttribute('data-fedi-name') || undefined,
                    content_category: 'transferencia',
                    currency: CURRENCY,
                    value: Number(link.getAttribute('data-fedi-value')) || undefined
                });
                track('Contact', { content_name: 'Comprobante transferencia', content_category: 'transferencia' });
            } else if (tId) {
                var tParams = {
                    content_type: 'product',
                    content_ids: [tId],
                    content_name: link.getAttribute('data-fedi-name') || tId,
                    content_category: 'transferencia',
                    currency: CURRENCY,
                    value: Number(link.getAttribute('data-fedi-value')) || undefined,
                    num_items: 1
                };
                track('Contact', tParams);
                track('InitiateCheckout', tParams);
            } else {
                track('Contact', { content_name: 'WhatsApp' });
            }
        }
    }, true);

    // ViewContent: una vez por visita, cuando se ve la sección de modelos
    function setupViewContent() {
        var section = document.getElementById('pricing');
        if (!section) return;
        var sent = false;
        function send() {
            if (sent) return;
            sent = true;
            track('ViewContent', {
                content_type: 'product',
                content_category: 'Dispensadores automáticos',
                content_ids: Object.keys(PRODUCTS).map(function (k) { return PRODUCTS[k].id; }),
                currency: CURRENCY
            });
        }
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        send();
                        observer.disconnect();
                    }
                });
            }, { threshold: 0.3 });
            observer.observe(section);
        } else {
            send();
        }
    }

    // Purchase en gracias.html. Solo dispara si Mercado Pago informa pago aprobado
    // o si hubo un clic en COMPRAR en las últimas 48 horas, y una sola vez por compra.
    function trackPurchase() {
        var query = new URLSearchParams(window.location.search);
        var status = query.get('collection_status') || query.get('status');
        var paymentId = query.get('payment_id') || query.get('collection_id');
        var checkout = storageGet(CHECKOUT_KEY);
        var recentCheckout = checkout && checkout.ts && (Date.now() - checkout.ts) < CHECKOUT_MAX_AGE_MS;

        if (status && status !== 'approved') return;
        if (!status && !recentCheckout) return;

        var key = paymentId || (recentCheckout ? String(checkout.ts) : null);
        if (!key) return;
        var sentKey = 'fedi_purchase_' + key;
        if (storageGet(sentKey)) return;

        var params = { content_type: 'product', currency: CURRENCY, num_items: 1 };
        if (recentCheckout && checkout.id) {
            params.content_ids = [checkout.id];
            params.content_name = checkout.name;
            params.value = checkout.value;
        }
        track('Purchase', params, 'purchase_' + key);
        storageSet(sentKey, true);
        try { window.localStorage.removeItem(CHECKOUT_KEY); } catch (e) { /* sin storage */ }
    }

    window.FediTracking = { trackPurchase: trackPurchase };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupViewContent);
    } else {
        setupViewContent();
    }
})();
