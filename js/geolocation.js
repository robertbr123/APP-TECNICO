// =====================================================
// Sistema de Geolocalização
// =====================================================

/**
 * Obtém a localização atual do dispositivo
 * @param {function} onSuccess - Callback quando a localização for obtida
 * @param {function} onError - Callback quando ocorrer erro
 */
function getCurrentPosition(onSuccess, onError) {
    if (!navigator.geolocation) {
        onError({
            message: 'Geolocalização não é suportada neste navegador',
            code: 0
        });
        return;
    }
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            onSuccess({
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy,
                altitude: position.coords.altitude,
                altitudeAccuracy: position.coords.altitudeAccuracy,
                heading: position.coords.heading,
                speed: position.coords.speed,
                timestamp: position.timestamp
            });
        },
        function(error) {
            var errorMessage = 'Erro ao obter localização';
            
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage = 'Permissão de localização negada pelo usuário';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage = 'Informação de localização indisponível';
                    break;
                case error.TIMEOUT:
                    errorMessage = 'Tempo esgotado ao obter localização';
                    break;
                default:
                    errorMessage = 'Erro desconhecido ao obter localização';
            }
            
            onError({
                message: errorMessage,
                code: error.code,
                originalError: error
            });
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 30000
        }
    );
}

/**
 * Obtém localização com promise
 * @returns {Promise} Promise com a localização
 */
function getLocation() {
    return new Promise(function(resolve, reject) {
        getCurrentPosition(resolve, reject);
    });
}

/**
 * Formata coordenadas para exibição
 * @param {number} lat - Latitude
 * @param {number} lng - Longitude
 * @returns {string} Coordenadas formatadas
 */
function formatCoordinates(lat, lng) {
    return lat.toFixed(6) + ', ' + lng.toFixed(6);
}

/**
 * Gera URL do Google Maps com as coordenadas
 * @param {number} lat - Latitude
 * @param {number} lng - Longitude
 * @returns {string} URL do Google Maps
 */
function getGoogleMapsUrl(lat, lng) {
    return 'https://www.google.com/maps/search/?api=1&query=' + lat + ',' + lng;
}

/**
 * Gera URL do Waze com as coordenadas
 * @param {number} lat - Latitude
 * @param {number} lng - Longitude
 * @returns {string} URL do Waze
 */
function getWazeUrl(lat, lng) {
    return 'https://waze.com/ul?ll=' + lat + ',' + lng + '&navigate=yes';
}

/**
 * Salva localização em um elemento HTML
 * @param {object} location - Objeto de localização
 * @param {string} latElementId - ID do elemento de latitude
 * @param {string} lngElementId - ID do elemento de longitude
 */
function saveLocationToElements(location, latElementId, lngElementId) {
    var latEl = document.getElementById(latElementId);
    var lngEl = document.getElementById(lngElementId);
    
    if (latEl) latEl.value = location.latitude;
    if (lngEl) lngEl.value = location.longitude;
}

/**
 * Calcula distância entre dois pontos (em km)
 * @param {number} lat1 - Latitude do ponto 1
 * @param {number} lng1 - Longitude do ponto 1
 * @param {number} lat2 - Latitude do ponto 2
 * @param {number} lng2 - Longitude do ponto 2
 * @returns {number} Distância em km
 */
function calculateDistance(lat1, lng1, lat2, lng2) {
    var R = 6371; // Raio da Terra em km
    var dLat = toRad(lat2 - lat1);
    var dLng = toRad(lng2 - lng1);
    
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
        Math.sin(dLng / 2) * Math.sin(dLng / 2);
    
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    var d = R * c;
    
    return d;
}

function toRad(degrees) {
    return degrees * (Math.PI / 180);
}