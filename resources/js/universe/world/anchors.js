import { Vector3 } from 'three';

/**
 * Where everything sits in the universe (world units). The flight path in
 * engine/Path.js is designed around these points, and every world module
 * builds its body here. The route winds forward along -Z, swinging left and
 * right and up and down so each stop is seen from a new angle.
 */
export const ANCHORS = {
    portal: new Vector3(0, 0, 430),
    core: new Vector3(0, 0, 0),
    origin: new Vector3(-46, 18, -260),
    services: new Vector3(46, -40, -580),
    products: new Vector3(-74, 12, -900),
    // One per platform, alternating sides: the planet on the left leaves room for its card on the right.
    platforms: [
        new Vector3(-40, 4, -1180),
        new Vector3(40, -8, -1330),
        new Vector3(-38, 12, -1480),
        new Vector3(42, 2, -1630),
        new Vector3(-40, -10, -1780),
        new Vector3(38, 8, -1930),
    ],
    stack: new Vector3(0, -36, -2190),
    reviews: new Vector3(60, 24, -2480),
    launch: new Vector3(0, 0, -2760),
};

/** Planet radii, same order as ANCHORS.platforms. */
export const PLANET_RADII = [17, 16, 21, 17, 18, 16];

/** The rough centre line of the route, for scattering dust along it. */
export function routePolyline() {
    return [
        ANCHORS.portal,
        ANCHORS.core,
        ANCHORS.origin,
        ANCHORS.services,
        ANCHORS.products,
        ...ANCHORS.platforms,
        ANCHORS.stack,
        ANCHORS.reviews,
        ANCHORS.launch,
        new Vector3(0, 0, -3100),
    ];
}
