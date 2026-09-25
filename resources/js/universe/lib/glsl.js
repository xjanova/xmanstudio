// GLSL shared by the universe's shaders. Everything in the scene is drawn
// procedurally — there is not a single texture file to download.

/**
 * Value noise and fractal Brownian motion. FBM_OCTAVES must be #defined by the
 * material (quality tiers pass fewer octaves).
 */
export const NOISE = /* glsl */ `
float xuHash(vec3 p) {
    p = fract(p * 0.3183099 + vec3(0.71, 0.113, 0.419));
    p *= 17.0;
    return fract(p.x * p.y * p.z * (p.x + p.y + p.z));
}

float xuNoise(vec3 x) {
    vec3 i = floor(x);
    vec3 f = fract(x);
    f = f * f * (3.0 - 2.0 * f);
    return mix(
        mix(mix(xuHash(i + vec3(0.0, 0.0, 0.0)), xuHash(i + vec3(1.0, 0.0, 0.0)), f.x),
            mix(xuHash(i + vec3(0.0, 1.0, 0.0)), xuHash(i + vec3(1.0, 1.0, 0.0)), f.x), f.y),
        mix(mix(xuHash(i + vec3(0.0, 0.0, 1.0)), xuHash(i + vec3(1.0, 0.0, 1.0)), f.x),
            mix(xuHash(i + vec3(0.0, 1.0, 1.0)), xuHash(i + vec3(1.0, 1.0, 1.0)), f.x), f.y),
        f.z);
}

float xuFbm(vec3 p) {
    float amp = 0.5;
    float sum = 0.0;
    for (int i = 0; i < FBM_OCTAVES; i++) {
        sum += amp * xuNoise(p);
        p = p * 2.03 + vec3(17.1, 3.7, 9.2);
        amp *= 0.5;
    }
    return sum;
}
`;

/**
 * A quad that always faces the camera, sized in world units by uSize. Used for
 * glows, coronas and nebula clouds.
 */
export const BILLBOARD_VERT = /* glsl */ `
uniform float uSize;
varying vec2 vUv;
void main() {
    vUv = uv;
    vec4 mv = modelViewMatrix * vec4(0.0, 0.0, 0.0, 1.0);
    mv.xy += position.xy * uSize;
    gl_Position = projectionMatrix * mv;
}
`;

/** A soft round glow for BILLBOARD_VERT: uColor * falloff, additive. */
export const GLOW_FRAG = /* glsl */ `
uniform vec3 uColor;
uniform float uIntensity;
uniform float uFalloff;
varying vec2 vUv;
void main() {
    float r = length(vUv - 0.5) * 2.0;
    float g = exp(-r * uFalloff) * smoothstep(1.0, 0.6, r);
    gl_FragColor = vec4(uColor * g * uIntensity, 1.0);
}
`;
