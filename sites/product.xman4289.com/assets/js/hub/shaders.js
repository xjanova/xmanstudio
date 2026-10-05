/* GLSL for the product hub universe. Colours arrive as linear THREE.Color uniforms.
   Sky, planets, rings, halos and points follow the XGAMES HUB engine; the screen,
   frame, circuit world, glyphs and links are this hub's own. */

// Ashima Arts 3D simplex noise (MIT) + fbm.
export const NOISE = /* glsl */ `
vec3 mod289(vec3 x){return x-floor(x*(1.0/289.0))*289.0;}
vec4 mod289(vec4 x){return x-floor(x*(1.0/289.0))*289.0;}
vec4 permute(vec4 x){return mod289(((x*34.0)+1.0)*x);}
vec4 taylorInvSqrt(vec4 r){return 1.79284291400159-0.85373472095314*r;}
float snoise(vec3 v){
  const vec2 C=vec2(1.0/6.0,1.0/3.0);
  const vec4 D=vec4(0.0,0.5,1.0,2.0);
  vec3 i=floor(v+dot(v,C.yyy));
  vec3 x0=v-i+dot(i,C.xxx);
  vec3 g=step(x0.yzx,x0.xyz);
  vec3 l=1.0-g;
  vec3 i1=min(g.xyz,l.zxy);
  vec3 i2=max(g.xyz,l.zxy);
  vec3 x1=x0-i1+C.xxx;
  vec3 x2=x0-i2+C.yyy;
  vec3 x3=x0-D.yyy;
  i=mod289(i);
  vec4 p=permute(permute(permute(i.z+vec4(0.0,i1.z,i2.z,1.0))+i.y+vec4(0.0,i1.y,i2.y,1.0))+i.x+vec4(0.0,i1.x,i2.x,1.0));
  float n_=0.142857142857;
  vec3 ns=n_*D.wyz-D.xzx;
  vec4 j=p-49.0*floor(p*ns.z*ns.z);
  vec4 x_=floor(j*ns.z);
  vec4 y_=floor(j-7.0*x_);
  vec4 x=x_*ns.x+ns.yyyy;
  vec4 y=y_*ns.x+ns.yyyy;
  vec4 h=1.0-abs(x)-abs(y);
  vec4 b0=vec4(x.xy,y.xy);
  vec4 b1=vec4(x.zw,y.zw);
  vec4 s0=floor(b0)*2.0+1.0;
  vec4 s1=floor(b1)*2.0+1.0;
  vec4 sh=-step(h,vec4(0.0));
  vec4 a0=b0.xzyw+s0.xzyw*sh.xxyy;
  vec4 a1=b1.xzyw+s1.xzyw*sh.zzww;
  vec3 p0=vec3(a0.xy,h.x);
  vec3 p1=vec3(a0.zw,h.y);
  vec3 p2=vec3(a1.xy,h.z);
  vec3 p3=vec3(a1.zw,h.w);
  vec4 norm=taylorInvSqrt(vec4(dot(p0,p0),dot(p1,p1),dot(p2,p2),dot(p3,p3)));
  p0*=norm.x;p1*=norm.y;p2*=norm.z;p3*=norm.w;
  vec4 m=max(0.6-vec4(dot(x0,x0),dot(x1,x1),dot(x2,x2),dot(x3,x3)),0.0);
  m=m*m;
  return 42.0*dot(m*m,vec4(dot(p0,x0),dot(p1,x1),dot(p2,x2),dot(p3,x3)));
}
float fbm(vec3 p){
  float a=0.5,s=0.0;
  for(int i=0;i<5;i++){s+=a*snoise(p);p=p*2.03+17.1;a*=0.5;}
  return 0.5+0.5*s;
}
float hash2(vec2 p){return fract(sin(dot(p,vec2(127.1,311.7)))*43758.5453);}
`;

/* ---------- Sky: rendered into a cube map only while its tint changes ---------- */

export const SKY_VERT = /* glsl */ `
varying vec3 vDir;
void main(){
  vDir=position;
  gl_Position=projectionMatrix*modelViewMatrix*vec4(position,1.0);
}`;

export const SKY_FRAG = /* glsl */ `
uniform vec3 uA;
uniform vec3 uB;
uniform vec3 uC;
varying vec3 vDir;
${NOISE}
void main(){
  vec3 d=normalize(vDir);
  vec3 bandN=normalize(vec3(0.28,1.0,0.32));
  float band=exp(-pow(dot(d,bandN)*2.2,2.0));
  float n1=fbm(d*1.7+3.7);
  float n2=fbm(d*3.9+vec3(n1*1.8)+7.1);
  float n3=fbm(d*9.0+vec3(n2*2.0)-4.0);
  float dens=smoothstep(0.5,0.95,n1*0.5+n2*0.45+band*0.35);
  float fil=smoothstep(0.55,0.85,n3)*dens;
  vec3 col=vec3(0.0014,0.0018,0.006);
  col+=uB*dens*0.18;
  col+=uA*pow(dens,2.4)*0.32;
  col+=uA*fil*0.2;
  float wisp=smoothstep(0.66,0.93,fbm(d*6.5+3.3))*band*smoothstep(0.2,0.6,dens+0.2);
  col+=uC*wisp*0.09;
  float dust=smoothstep(0.5,0.78,fbm(d*5.0-2.0));
  col*=1.0-dust*0.7*band;
  float core=pow(max(dot(d,normalize(vec3(0.35,0.05,-1.0))),0.0),8.0);
  col+=uA*core*0.05;
  gl_FragColor=vec4(col,1.0);
}`;

/* ---------- Planet surface, baked once per world into an equirect texture ---------- */

export const BAKE_VERT = /* glsl */ `
varying vec2 vUv;
void main(){ vUv=uv; gl_Position=vec4(position.xy,0.0,1.0); }`;

// Same lon/lat as THREE.SphereGeometry's uv, so the baked map wraps without a seam.
export const BAKE_FRAG = /* glsl */ `
uniform vec3 uA;
uniform vec3 uB;
uniform vec3 uC;
uniform float uStyle;
uniform float uSeed;
varying vec2 vUv;
${NOISE}
void main(){
  float phi=vUv.x*6.28318530718;
  float theta=(1.0-vUv.y)*3.14159265359;
  vec3 p=vec3(-cos(phi)*sin(theta),cos(theta),sin(phi)*sin(theta));
  vec3 q=p+vec3(uSeed);
  vec3 col;
  float emis=0.0;
  if(uStyle<0.5){
    // banded giant
    float w=fbm(q*vec3(1.6,5.5,1.6));
    float b=sin(p.y*9.0+w*5.0+uSeed);
    float b2=sin(p.y*24.0+w*9.0);
    col=mix(uB,uA,smoothstep(-0.7,0.85,b));
    col=mix(col,uC,smoothstep(0.7,1.0,b2)*0.45);
    col*=0.8+0.4*fbm(q*7.0);
    float spot=smoothstep(0.24,0.0,length(p-normalize(vec3(0.62,-0.28,0.73))));
    col=mix(col,uC*1.15,spot*0.75);
    emis=smoothstep(0.82,1.0,b2)*0.25;
  } else if(uStyle<1.5){
    // continents, oceans, clouds, ice
    float h=fbm(q*2.3)+0.32*fbm(q*7.5);
    float land=smoothstep(0.62,0.66,h);
    vec3 ocean=mix(uB*0.45,uB,smoothstep(0.35,0.62,h));
    vec3 ground=mix(uA*0.8,uC,smoothstep(0.7,0.95,h));
    col=mix(ocean,ground,land);
    float clouds=smoothstep(0.56,0.82,fbm(q*3.2+11.0));
    col=mix(col,vec3(0.92,0.95,1.0),clouds*0.6);
    col=mix(col,vec3(0.9,0.94,1.0),smoothstep(0.84,0.93,abs(p.y)));
    emis=land*(1.0-clouds)*smoothstep(0.75,0.95,fbm(q*14.0))*0.8;
  } else if(uStyle<2.5){
    // dark crystal world, glowing fault lines
    float h=fbm(q*3.0);
    col=mix(uB*0.25,uB*0.9,h);
    float r=pow(1.0-abs(snoise(q*4.5)),14.0);
    float r2=pow(1.0-abs(snoise(q*10.0+3.0)),20.0);
    emis=clamp(r+r2*0.6,0.0,1.0);
    col=mix(col,uA,r*0.5);
  } else {
    // circuit world: a board of traces on the lat/long grid, lit in districts
    vec2 g=vec2(vUv.x*64.0,vUv.y*32.0);
    vec2 id=floor(g);
    vec2 f=fract(g);
    float h=hash2(id+uSeed);
    float hv=hash2(id.yx*1.37+uSeed*3.1);
    float lh=step(0.55,h)*smoothstep(0.09,0.02,abs(f.y-0.5));
    float lv=step(hv,0.38)*smoothstep(0.09,0.02,abs(f.x-0.5));
    float trace=max(lh,lv);
    float via=smoothstep(0.2,0.1,length(f-0.5))*step(0.86,hash2(id*1.7+uSeed+9.0));
    // a few bigger "chips": rectangles of the coarser grid
    vec2 cg=floor(vec2(vUv.x*16.0,vUv.y*8.0));
    float chip=step(0.9,hash2(cg+uSeed*0.7));
    float base=fbm(q*2.4);
    col=mix(uB*0.32,uB*0.75,base);
    col=mix(col,uA*0.55,trace*0.75);
    col=mix(col,mix(uB,uC,0.25)*0.9,chip*0.55);
    float district=smoothstep(0.42,0.72,fbm(q*2.6+5.0));
    emis=clamp(trace*0.5*district+via*1.0+chip*0.08,0.0,1.0);
    // polar caps stay quiet: the grid pinches there
    float cap=smoothstep(0.86,0.97,abs(p.y));
    col=mix(col,uB*0.4,cap);
    emis*=1.0-cap;
  }
  gl_FragColor=vec4(col,emis);
}`;

export const PLANET_VERT = /* glsl */ `
varying vec2 vUv;
varying vec3 vN;
varying vec3 vV;
void main(){
  vUv=uv;
  vN=normalize(normalMatrix*normal);
  vec4 mv=modelViewMatrix*vec4(position,1.0);
  vV=normalize(-mv.xyz);
  gl_Position=projectionMatrix*mv;
}`;

export const PLANET_FRAG = /* glsl */ `
uniform sampler2D uTex0;
uniform sampler2D uTex1;
uniform float uMix;
uniform vec3 uC0;
uniform vec3 uC1;
uniform vec3 uLight;
uniform float uGlow;
varying vec2 vUv;
varying vec3 vN;
varying vec3 vV;
void main(){
  vec4 t=uMix<0.001?texture2D(uTex0,vUv):(uMix>0.999?texture2D(uTex1,vUv):mix(texture2D(uTex0,vUv),texture2D(uTex1,vUv),uMix));
  vec3 C=mix(uC0,uC1,uMix);
  vec3 n=normalize(vN);
  float ndl=dot(n,normalize(uLight));
  float diff=smoothstep(-0.28,0.95,ndl);
  vec3 col=t.rgb*(0.035+diff*1.2);
  float term=smoothstep(-0.25,0.02,ndl)*smoothstep(0.4,0.02,ndl);
  col+=C*term*0.2;
  col+=C*t.a*(1.0-smoothstep(-0.35,0.35,ndl))*1.8+C*t.a*0.18;
  float fr=pow(1.0-max(dot(n,normalize(vV)),0.0),3.0);
  col+=C*fr*(0.2+0.9*diff)*uGlow;
  gl_FragColor=vec4(col,1.0);
  #include <colorspace_fragment>
}`;

export const ATMO_FRAG = /* glsl */ `
uniform vec3 uColor;
uniform vec3 uLight;
uniform float uPower;
varying vec2 vUv;
varying vec3 vN;
varying vec3 vV;
void main(){
  vec3 n=normalize(vN);
  float i=pow(clamp(0.78-dot(n,vec3(0.0,0.0,1.0)),0.0,1.0),3.0)*uPower;
  float lit=0.35+0.65*smoothstep(-0.6,0.6,dot(-n,normalize(uLight)));
  gl_FragColor=vec4(uColor*i*lit,1.0);
  #include <colorspace_fragment>
}`;

export const RING_VERT = /* glsl */ `
varying vec3 vPos;
void main(){ vPos=position; gl_Position=projectionMatrix*modelViewMatrix*vec4(position,1.0); }`;

export const RING_FRAG = /* glsl */ `
uniform vec3 uA;
uniform vec3 uB;
uniform float uTime;
uniform float uInner;
uniform float uOuter;
varying vec3 vPos;
float h1(float x){return fract(sin(x*127.1)*43758.5453);}
float n1(float x){float i=floor(x);float f=fract(x);return mix(h1(i),h1(i+1.0),f*f*(3.0-2.0*f));}
void main(){
  float r=length(vPos.xy);
  float t=(r-uInner)/(uOuter-uInner);
  float ang=atan(vPos.y,vPos.x);
  float bands=0.35+0.65*n1(t*46.0)*n1(t*13.0+4.0);
  float dens=smoothstep(0.0,0.06,t)*smoothstep(0.86,0.62,t)*bands;
  dens*=1.0-smoothstep(0.025,0.0,abs(t-0.4))*0.85;
  float dash=step(0.45,fract(ang*9.549+uTime*0.15));
  float line=smoothstep(0.016,0.0,abs(t-0.95))*dash;
  float fine=smoothstep(0.006,0.0,abs(t-0.78))*0.6;
  vec3 col=mix(uB,uA,t)*dens*0.55+uA*(line*1.1+fine*0.5);
  gl_FragColor=vec4(col,1.0);
  #include <colorspace_fragment>
}`;

export const HALO_VERT = /* glsl */ `
varying vec2 vUv;
void main(){ vUv=uv; gl_Position=projectionMatrix*modelViewMatrix*vec4(position,1.0); }`;

export const HALO_FRAG = /* glsl */ `
uniform vec3 uColor;
uniform float uStrength;
varying vec2 vUv;
void main(){
  float d=length(vUv-0.5)*2.0;
  float a=pow(max(1.0-d,0.0),2.4)*uStrength;
  gl_FragColor=vec4(uColor*a,1.0);
  #include <colorspace_fragment>
}`;

/* ---------- The holo screen: the product's real picture in a window ---------- */

export const SCREEN_VERT = /* glsl */ `
uniform float uBend;
varying vec2 vUv;
varying vec3 vN;
varying vec3 vV;
void main(){
  vUv=uv;
  vec3 p=position;
  // a gentle curve, like a wide monitor
  p.z-=uBend*p.x*p.x;
  vec3 n=normalize(vec3(2.0*uBend*p.x,0.0,1.0));
  vN=normalize(normalMatrix*n);
  vec4 mv=modelViewMatrix*vec4(p,1.0);
  vV=normalize(-mv.xyz);
  gl_Position=projectionMatrix*mv;
}`;

export const SCREEN_FRAG = /* glsl */ `
uniform sampler2D uTex0;
uniform sampler2D uTex1;
uniform float uMix;
uniform float uTime;
uniform float uAspect;
uniform vec3 uAccent;
uniform float uPower;
varying vec2 vUv;
varying vec3 vN;
varying vec3 vV;
float rbox(vec2 p,vec2 b,float r){vec2 q=abs(p)-b+r;return length(max(q,0.0))+min(max(q.x,q.y),0.0)-r;}
void main(){
  vec2 p=(vUv-0.5)*vec2(uAspect,1.0);
  float d=rbox(p,vec2(uAspect*0.5-0.012,0.488),0.034);
  float inside=smoothstep(0.003,-0.003,d);
  // the new picture boots in behind a scan line running top to bottom
  float y=1.0-vUv.y;
  float edge=uMix*1.08-0.04;
  float shown=smoothstep(edge+0.012,edge-0.012,y);
  vec3 a=texture2D(uTex0,vUv).rgb;
  vec3 b=texture2D(uTex1,vUv).rgb;
  vec3 col=mix(a,b,shown);
  float scan=smoothstep(0.03,0.0,abs(y-edge))*step(0.001,uMix)*step(uMix,0.999);
  col+=uAccent*scan*1.4;
  // panel feel: fine scanlines, a sheen that drifts across, a soft inner vignette
  col*=0.955+0.045*sin(vUv.y*720.0);
  float sheen=smoothstep(0.08,0.0,abs(fract(vUv.x*0.55-vUv.y*0.35-uTime*0.035)-0.5));
  col+=vec3(0.06,0.08,0.12)*sheen;
  col*=mix(0.78,1.0,smoothstep(0.0,0.06,-d));
  col*=uPower;
  // glowing bezel just outside the glass
  float rim=exp(-max(d,0.0)*120.0)*(1.0-inside);
  float fr=pow(1.0-max(dot(normalize(vN),normalize(vV)),0.0),2.0);
  vec3 glow=uAccent*(rim*1.25+fr*0.25*inside);
  float alpha=max(inside,rim*0.85);
  gl_FragColor=vec4(col*inside+glow,alpha);
  #include <colorspace_fragment>
}`;

// The device's back plate, a little bigger than the glass: depth when it turns.
export const PLATE_FRAG = /* glsl */ `
uniform float uAspect;
uniform vec3 uAccent;
varying vec2 vUv;
float rbox(vec2 p,vec2 b,float r){vec2 q=abs(p)-b+r;return length(max(q,0.0))+min(max(q.x,q.y),0.0)-r;}
void main(){
  vec2 p=(vUv-0.5)*vec2(uAspect,1.0);
  float d=rbox(p,vec2(uAspect*0.5-0.01,0.49),0.05);
  float inside=smoothstep(0.004,-0.004,d);
  float line=smoothstep(0.012,0.0,abs(d+0.006));
  vec3 col=vec3(0.012,0.016,0.035)+uAccent*line*0.9;
  gl_FragColor=vec4(col,inside*0.92+line*0.4);
  #include <colorspace_fragment>
}`;

/* ---------- Stars, dust and code glyphs ---------- */

export const POINTS_VERT = /* glsl */ `
attribute float aSize;
attribute float aPhase;
attribute vec3 aColor;
uniform float uTime;
uniform float uPR;
uniform float uAtten;
varying vec3 vColor;
void main(){
  vec4 mv=modelViewMatrix*vec4(position,1.0);
  gl_Position=projectionMatrix*mv;
  float tw=0.6+0.4*sin(uTime*(0.5+aPhase*1.8)+aPhase*31.0);
  vColor=aColor*tw;
  float s=aSize*uPR;
  if(uAtten>0.0){ s*=uAtten/max(-mv.z,0.1); }
  gl_PointSize=min(s,40.0*uPR);
}`;

export const POINTS_FRAG = /* glsl */ `
varying vec3 vColor;
void main(){
  float d=length(gl_PointCoord-0.5);
  float a=smoothstep(0.5,0.0,d);
  a*=a;
  gl_FragColor=vec4(vColor*a,1.0);
  #include <colorspace_fragment>
}`;

export const GLYPH_VERT = /* glsl */ `
attribute float aSize;
attribute float aPhase;
attribute float aGlyph;
attribute vec3 aColor;
uniform float uTime;
uniform float uPR;
uniform float uAtten;
uniform float uK;
varying vec3 vColor;
varying float vGlyph;
void main(){
  vec4 mv=modelViewMatrix*vec4(position,1.0);
  gl_Position=projectionMatrix*mv;
  float tw=0.55+0.45*sin(uTime*(0.6+aPhase*1.6)+aPhase*41.0);
  vColor=aColor*tw;
  // glyphs re-roll now and then, like a terminal redrawing
  vGlyph=mod(aGlyph+floor(uTime*(0.15+aPhase*0.35)+aPhase*9.0),48.0);
  float s=aSize*uPR*uK;
  if(uAtten>0.0){ s*=uAtten/max(-mv.z,0.1); }
  gl_PointSize=min(s,48.0*uPR);
}`;

export const GLYPH_FRAG = /* glsl */ `
uniform sampler2D uAtlas;
varying vec3 vColor;
varying float vGlyph;
void main(){
  vec2 cell=vec2(mod(vGlyph,8.0),floor(vGlyph/8.0));
  vec2 pc=gl_PointCoord;
  vec2 uv=vec2((cell.x+pc.x)/8.0,1.0-(cell.y+pc.y)/8.0);
  float a=texture2D(uAtlas,uv).r;
  gl_FragColor=vec4(vColor*a,1.0);
  #include <colorspace_fragment>
}`;

/* ---------- Links from the studio core to each product node ---------- */

export const LINK_VERT = /* glsl */ `
attribute float aT;
attribute float aPhase;
attribute vec3 aColor;
varying float vT;
varying float vPhase;
varying vec3 vColor;
void main(){
  vT=aT; vPhase=aPhase; vColor=aColor;
  gl_Position=projectionMatrix*modelViewMatrix*vec4(position,1.0);
}`;

export const LINK_FRAG = /* glsl */ `
uniform float uTime;
varying float vT;
varying float vPhase;
varying vec3 vColor;
void main(){
  float head=fract(uTime*0.32+vPhase);
  float pulse=smoothstep(0.16,0.0,abs(vT-head));
  float fade=smoothstep(0.0,0.25,vT);
  gl_FragColor=vec4(vColor*(0.12+pulse*1.3)*fade,1.0);
  #include <colorspace_fragment>
}`;
