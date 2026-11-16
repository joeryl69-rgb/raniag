(function () {
    function pointInRing(lng, lat, ring) {
        let inside = false;
        const count = ring?.length || 0;
        if (count < 3) return false;

        for (let i = 0, j = count - 1; i < count; j = i++) {
            const [lngI, latI] = ring[i];
            const [lngJ, latJ] = ring[j];
            const intersects = (latI > lat) !== (latJ > lat)
                && lng < ((lngJ - lngI) * (lat - latI)) / (latJ - latI) + lngI;
            if (intersects) inside = !inside;
        }

        return inside;
    }

    function pointInGeometry(lng, lat, geometry) {
        if (!geometry) return null;

        const coordinates = geometry.coordinates || [];
        const polygons = geometry.type === 'MultiPolygon' ? coordinates : [coordinates];
        for (const rings of polygons) {
            if (!rings?.length || !pointInRing(lng, lat, rings[0])) continue;

            let inHole = false;
            for (const hole of rings.slice(1)) {
                if (pointInRing(lng, lat, hole)) {
                    inHole = true;
                    break;
                }
            }
            if (!inHole) return true;
        }

        return false;
    }

    function resolvePlaceFromBoundaries(lat, lng, boundary, barangayBoundaries, address) {
        const municipality = address?.municipality || 'Pamplona';
        const province = address?.province || 'Cagayan';
        const country = address?.country || 'Philippines';

        if (boundary && pointInGeometry(lng, lat, boundary) === false) {
            return {
                inside: false,
                barangay: null,
                municipality: 'Outside Pamplona',
                province,
                country,
            };
        }

        for (const feature of barangayBoundaries?.features || []) {
            const name = feature.properties?.adm4_en;
            if (name && pointInGeometry(lng, lat, feature.geometry)) {
                return { inside: true, barangay: name, municipality, province, country };
            }
        }

        return null;
    }

    window.RANIAG_Geofence = Object.freeze({
        pointInGeometry,
        resolvePlaceFromBoundaries,
    });
})();
