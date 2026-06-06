/**
 * Debug Helper - Captura errores y los muestra en la consola
 */

'use strict';

// Capturar errores de JavaScript
window.addEventListener('error', function(event) {
    console.error('❌ ERROR JS:', {
        message: event.message,
        source: event.filename,
        lineno: event.lineno,
        colno: event.colno,
        error: event.error?.stack
    });
});

// Capturar promesas rechazadas
window.addEventListener('unhandledrejection', function(event) {
    console.error('❌ UNHANDLED PROMISE:', event.reason);
});

// Intercept fetch para ver errores de red
const originalFetch = window.fetch;
window.fetch = function(...args) {
    const [resource, config] = args;
    console.log('🌐 FETCH:', resource, config);
    
    return originalFetch.apply(this, args)
        .then(response => {
            if (!response.ok) {
                console.warn('⚠️  FETCH ERROR:', response.status, response.statusText, resource);
            }
            return response;
        })
        .catch(error => {
            console.error('❌ FETCH FAILED:', error, resource);
            throw error;
        });
};

console.log('✅ Debug Helper cargado - Monitoreando errores');
