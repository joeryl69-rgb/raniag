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

    function orientation(a, b, c) {
        return (b[0] - a[0]) * (c[1] - a[1]) - (b[1] - a[1]) * (c[0] - a[0]);
    }

    function pointOnSegment(point, start, end, epsilon) {
        return point[0] >= Math.min(start[0], end[0]) - epsilon
            && point[0] <= Math.max(start[0], end[0]) + epsilon
            && point[1] >= Math.min(start[1], end[1]) - epsilon
            && point[1] <= Math.max(start[1], end[1]) + epsilon;
    }

    function segmentsIntersect(a, b, c, d, epsilon) {
        const first = orientation(a, b, c);
        const second = orientation(a, b, d);
        const third = orientation(c, d, a);
        const fourth = orientation(c, d, b);
        const crosses = (first > epsilon && second < -epsilon || first < -epsilon && second > epsilon)
            && (third > epsilon && fourth < -epsilon || third < -epsilon && fourth > epsilon);

        return crosses
            || (Math.abs(first) <= epsilon && pointOnSegment(c, a, b, epsilon))
            || (Math.abs(second) <= epsilon && pointOnSegment(d, a, b, epsilon))
            || (Math.abs(third) <= epsilon && pointOnSegment(a, c, d, epsilon))
            || (Math.abs(fourth) <= epsilon && pointOnSegment(b, c, d, epsilon));
    }

    function validateBoundaryGeometry(geometry) {
        if (geometry?.type !== 'Polygon' || !Array.isArray(geometry.coordinates) || geometry.coordinates.length !== 1) {
            return { valid: false, message: 'Draw one polygon outline before saving.' };
        }

        const ring = geometry.coordinates[0];
        if (!Array.isArray(ring) || ring.length < 4 || ring.length > 2001) {
            return { valid: false, message: 'The outline must contain 3 to 2,000 boundary points.' };
        }

        if (ring.some((point) => !Array.isArray(point)
            || point.length !== 2
            || !Number.isFinite(Number(point[0]))
            || !Number.isFinite(Number(point[1]))
            || Number(point[0]) < -180
            || Number(point[0]) > 180
            || Number(point[1]) < -90
            || Number(point[1]) > 90)) {
            return { valid: false, message: 'Every boundary point must have valid longitude and latitude coordinates.' };
        }

        const first = ring[0];
        const last = ring[ring.length - 1];
        if (Number(first[0]) !== Number(last[0]) || Number(first[1]) !== Number(last[1])) {
            return { valid: false, message: 'Close the polygon by connecting its final point to the first point.' };
        }

        const distinct = new Set(ring.slice(0, -1).map((point) => `${Number(point[0]).toFixed(8)},${Number(point[1]).toFixed(8)}`));
        if (distinct.size < 3) {
            return { valid: false, message: 'The outline needs at least three distinct points.' };
        }

        const segmentCount = ring.length - 1;
        const epsilon = 1e-12;
        for (let firstIndex = 0; firstIndex < segmentCount; firstIndex++) {
            for (let secondIndex = firstIndex + 1; secondIndex < segmentCount; secondIndex++) {
                if (secondIndex === firstIndex + 1 || (firstIndex === 0 && secondIndex === segmentCount - 1)) continue;
                if (segmentsIntersect(
                    ring[firstIndex],
                    ring[firstIndex + 1],
                    ring[secondIndex],
                    ring[secondIndex + 1],
                    epsilon
                )) {
                    return { valid: false, message: 'The outline crosses itself. Adjust the points to form one simple area.' };
                }
            }
        }

        return { valid: true, message: '' };
    }

    function resolvePlaceFromBoundaries(lat, lng, boundary, barangayBoundaries, address) {
        const municipality = address?.municipality || 'Pamplona';
        const province = address?.province || 'Cagayan';
        const country = address?.country || 'Philippines';
        const insideMunicipality = boundary ? pointInGeometry(lng, lat, boundary) : null;

        if (insideMunicipality === false) {
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

        if (insideMunicipality === true) {
            return { inside: true, barangay: null, municipality, province, country };
        }

        return null;
    }

    window.RANIAG_Geofence = Object.freeze({
        pointInGeometry,
        validateBoundaryGeometry,
        resolvePlaceFromBoundaries,
    });
})();
