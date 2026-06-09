(function () {
    const mapConfig = window.RANIAG_MAP || { default_lat: 18.472, default_lng: 121.325, default_zoom: 13 };

    const typeCards = document.querySelectorAll('.raniag-type-card');
    typeCards.forEach((card) => {
        card.addEventListener('click', () => {
            typeCards.forEach((c) => c.classList.remove('selected'));
            card.classList.add('selected');
            const input = card.querySelector('input[type="radio"]');
            if (input) {
                input.checked = true;
            }
        });
    });

    const anonymousToggle = document.getElementById('is_anonymous');
    const reporterFields = document.getElementById('reporter-fields');

    function syncReporterFields() {
        if (!anonymousToggle || !reporterFields) {
            return;
        }

        const isAnonymous = anonymousToggle.checked;
        reporterFields.classList.toggle('disabled', isAnonymous);

        reporterFields.querySelectorAll('input').forEach((input) => {
            input.disabled = isAnonymous;
            if (isAnonymous) {
                input.value = '';
            }
        });
    }

    if (anonymousToggle) {
        anonymousToggle.addEventListener('change', syncReporterFields);
        syncReporterFields();
    }

    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const mapElement = document.getElementById('incident-map');

    let mapInstance = null;
    let mapMarker = null;

    function updateCoordinateInputs(lat, lng) {
        if (latInput) {
            latInput.value = Number(lat).toFixed(8);
        }
        if (lngInput) {
            lngInput.value = Number(lng).toFixed(8);
        }
    }

    function setMarker(lat, lng, options = {}) {
        if (!mapInstance) {
            updateCoordinateInputs(lat, lng);
            return;
        }

        if (mapMarker) {
            mapMarker.setLatLng([lat, lng]);
        } else {
            mapMarker = L.marker([lat, lng], { draggable: true }).addTo(mapInstance);
            mapMarker.on('dragend', (event) => {
                const position = event.target.getLatLng();
                updateCoordinateInputs(position.lat, position.lng);
            });
        }

        updateCoordinateInputs(lat, lng);

        if (options.pan !== false) {
            mapInstance.panTo([lat, lng]);
        }
    }

    if (mapElement && typeof L !== 'undefined') {
        const defaultLat = parseFloat(latInput?.value) || mapConfig.default_lat;
        const defaultLng = parseFloat(lngInput?.value) || mapConfig.default_lng;

        mapInstance = L.map('incident-map').setView([defaultLat, defaultLng], mapConfig.default_zoom || 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(mapInstance);

        mapInstance.on('click', (event) => {
            setMarker(event.latlng.lat, event.latlng.lng);
        });

        if (latInput?.value && lngInput?.value) {
            setMarker(parseFloat(latInput.value), parseFloat(lngInput.value), { pan: false });
        }

        setTimeout(() => mapInstance.invalidateSize(), 200);
    }

    window.RANIAG_MAP_API = {
        setCoordinates(lat, lng, options = {}) {
            setMarker(lat, lng, options);
        },
        getCoordinates() {
            const lat = parseFloat(latInput?.value);
            const lng = parseFloat(lngInput?.value);
            if (Number.isFinite(lat) && Number.isFinite(lng)) {
                return { lat, lng };
            }
            return null;
        },
        panTo(lat, lng) {
            if (mapInstance) {
                mapInstance.panTo([lat, lng]);
            }
        },
    };

    const form = document.getElementById('incident-report-form');
    const submitButton = document.getElementById('submit-report');

    if (form && submitButton) {
        form.addEventListener('submit', () => {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
        });
    }
})();

