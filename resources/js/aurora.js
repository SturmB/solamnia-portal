/* Solamnia p07 "Aurora" — the living-light background.
 *
 * One fixed, full-viewport WebGL2 canvas sits behind every page that opts in
 * via <body data-aurora="…"> (landing 1.0 · invite 0.7 · app 0.25 · KB 0.1).
 * Three layered noise "curtains" drift and fold like an aurora; the hue slides
 * green → violet → rose on an ~55 s cycle. A CSS scrim (body::before, see
 * app.css) darkens the sky wherever text sits, keeping ink at WCAG AA contrast.
 *
 * Accessibility / performance contract:
 *  - No <body data-aurora>: the sky does not run (auth pages stay plain).
 *  - prefers-reduced-motion: ONE static frame is rendered, no loop starts.
 *  - document.hidden: the rAF loop stops dead; it resumes on return.
 *  - No WebGL2: <html> gets .no-aurora and CSS paints a static gradient.
 *  - The canvas renders at half resolution (the light is soft; cost drops 4×).
 *  - The canvas is @persist'd in the layout, so it survives wire:navigate.
 */
(() => {
    "use strict";

    document.documentElement.classList.add("js");

    /* ---------------------------------------------------------------- GLSL */

    const VERT = `#version 300 es
  /* Fullscreen triangle — three vertices, no buffers to manage. */
  void main() {
    vec2 p = vec2((gl_VertexID << 1) & 2, gl_VertexID & 2);
    gl_Position = vec4(p * 2.0 - 1.0, 0.0, 1.0);
  }`;

    const FRAG = `#version 300 es
  precision highp float;
  out vec4 fragColor;

  uniform vec2  u_res;       /* canvas size in device px            */
  uniform float u_time;      /* seconds                             */
  uniform float u_intensity; /* per-page aurora brightness, 0..1    */
  uniform vec2  u_focus;     /* uv of a hovered CTA (light "pull")  */
  uniform float u_focusAmt;  /* 0..1, eased on the JS side          */

  /* ------------------------------------------------------ simplex noise
     3-D simplex noise, Ashima Arts / Ian McEwan (public domain).
     The z axis is used as time, so the field itself evolves rather than
     merely scrolling. */
  vec3 mod289(vec3 x) { return x - floor(x * (1.0/289.0)) * 289.0; }
  vec4 mod289(vec4 x) { return x - floor(x * (1.0/289.0)) * 289.0; }
  vec4 permute(vec4 x) { return mod289(((x * 34.0) + 1.0) * x); }
  vec4 taylorInvSqrt(vec4 r) { return 1.79284291400159 - 0.85373472095314 * r; }

  float snoise(vec3 v) {
    const vec2 C = vec2(1.0/6.0, 1.0/3.0);
    const vec4 D = vec4(0.0, 0.5, 1.0, 2.0);
    vec3 i  = floor(v + dot(v, C.yyy));
    vec3 x0 = v - i + dot(i, C.xxx);
    vec3 g  = step(x0.yzx, x0.xyz);
    vec3 l  = 1.0 - g;
    vec3 i1 = min(g.xyz, l.zxy);
    vec3 i2 = max(g.xyz, l.zxy);
    vec3 x1 = x0 - i1 + C.xxx;
    vec3 x2 = x0 - i2 + C.yyy;
    vec3 x3 = x0 - D.yyy;
    i = mod289(i);
    vec4 p = permute(permute(permute(
              i.z + vec4(0.0, i1.z, i2.z, 1.0))
            + i.y + vec4(0.0, i1.y, i2.y, 1.0))
            + i.x + vec4(0.0, i1.x, i2.x, 1.0));
    float n_ = 0.142857142857;
    vec3 ns = n_ * D.wyz - D.xzx;
    vec4 j  = p - 49.0 * floor(p * ns.z * ns.z);
    vec4 x_ = floor(j * ns.z);
    vec4 y_ = floor(j - 7.0 * x_);
    vec4 x  = x_ * ns.x + ns.yyyy;
    vec4 y  = y_ * ns.x + ns.yyyy;
    vec4 h  = 1.0 - abs(x) - abs(y);
    vec4 b0 = vec4(x.xy, y.xy);
    vec4 b1 = vec4(x.zw, y.zw);
    vec4 s0 = floor(b0) * 2.0 + 1.0;
    vec4 s1 = floor(b1) * 2.0 + 1.0;
    vec4 sh = -step(h, vec4(0.0));
    vec4 a0 = b0.xzyw + s0.xzyw * sh.xxyy;
    vec4 a1 = b1.xzyw + s1.xzyw * sh.zzww;
    vec3 p0 = vec3(a0.xy, h.x);
    vec3 p1 = vec3(a0.zw, h.y);
    vec3 p2 = vec3(a1.xy, h.z);
    vec3 p3 = vec3(a1.zw, h.w);
    vec4 norm = taylorInvSqrt(vec4(dot(p0,p0), dot(p1,p1), dot(p2,p2), dot(p3,p3)));
    p0 *= norm.x; p1 *= norm.y; p2 *= norm.z; p3 *= norm.w;
    vec4 m = max(0.6 - vec4(dot(x0,x0), dot(x1,x1), dot(x2,x2), dot(x3,x3)), 0.0);
    m = m * m;
    return 42.0 * dot(m * m, vec4(dot(p0,x0), dot(p1,x1), dot(p2,x2), dot(p3,x3)));
  }

  /* -------------------------------------------------------- curl field
     Divergence-free flow: take the 2-D gradient of a scalar noise field
     and rotate it 90°. Warping the curtain's x axis with this makes the
     light FOLD and drape instead of sliding sideways — the signature
     motion of a real aurora. */
  vec2 curl(vec2 p, float t) {
    float e = 0.12;
    float dy = snoise(vec3(p.x, p.y + e, t)) - snoise(vec3(p.x, p.y - e, t));
    float dx = snoise(vec3(p.x + e, p.y, t)) - snoise(vec3(p.x - e, p.y, t));
    return vec2(dy, -dx) / (2.0 * e);
  }

  /* ----------------------------------------------------------- palette
     The three brief colors (sRGB approximations of the oklch tokens),
     blended around a ring so the drift never jump-cuts:
       green  oklch(0.82 0.19 165)   violet oklch(0.62 0.21 300)
       rose   oklch(0.72 0.16 350) */
  vec3 auroraColor(float h) {
    const vec3 GREEN  = vec3(0.10, 0.98, 0.55);
    const vec3 VIOLET = vec3(0.60, 0.32, 1.00);
    const vec3 ROSE   = vec3(1.00, 0.45, 0.70);
    float a = fract(h) * 3.0;
    if (a < 1.0) return mix(GREEN,  VIOLET, smoothstep(0.0, 1.0, a));
    if (a < 2.0) return mix(VIOLET, ROSE,   smoothstep(0.0, 1.0, a - 1.0));
    return              mix(ROSE,   GREEN,  smoothstep(0.0, 1.0, a - 2.0));
  }

  /* ----------------------------------------------------------- curtain
     One sheet of light. Noise is sampled mostly ALONG x (after the curl
     warp) so brightness varies in tall vertical streaks — the "rays".
     Vertical shaping: a rippling lower hem, full brightness overhead,
     dissolving past the top of the frame. */
  float curtain(vec2 p, float t, float seed) {
    vec2 flow = curl(vec2(p.x * 1.1 + seed * 7.0, p.y * 0.5), t * 0.6 + seed);
    float x = p.x + flow.x * 0.38 + seed;

    float rays = snoise(vec3(x * 2.4, p.y * 0.7 + flow.y * 0.35, t + seed * 3.0)) * 0.62
               + snoise(vec3(x * 6.5, p.y * 1.6, t * 1.4 + seed * 5.0)) * 0.26;
    rays = max(rays * 0.5 + 0.5, 0.0);
    rays = pow(rays, 4.0) * 1.8;           /* sharpen into distinct bright rays */

    float hem  = 0.30 + 0.10 * snoise(vec3(x * 1.4, seed, t * 0.7));
    float fall = smoothstep(hem, hem + 0.45, p.y)          /* fade in above the hem */
               * (1.0 - smoothstep(0.85, 1.55, p.y));      /* dissolve overhead     */
    return rays * fall;
  }

  /* Cheap screen-space hash for dithering (kills gradient banding). */
  float hash(vec2 p) {
    return fract(sin(dot(p, vec2(12.9898, 78.233))) * 43758.5453);
  }

  void main() {
    vec2 uv = gl_FragCoord.xy / u_res;               /* 0..1, y up      */
    vec2 p  = vec2(uv.x * u_res.x / u_res.y, uv.y);  /* aspect-corrected */

    float t   = u_time * 0.045;   /* master drift — one "breath" ≈ 70 s  */
    float hue = u_time / 55.0;    /* full green→violet→rose→green cycle  */

    /* Three curtains at different scales, speeds and hue offsets. The
       slight per-layer hue shift with uv.y gives the classic aurora
       look of green skirts under violet tops. */
    float c1 = curtain(p, t, 0.0);
    float c2 = curtain(p * 1.35 + vec2(2.7, 0.06), t * 0.8, 4.2);
    float c3 = curtain(p * 0.75, t * 1.2, 9.1);

    vec3 light = c1 * auroraColor(hue + uv.y * 0.22)
               + c2 * auroraColor(hue + 0.33 + uv.x * 0.04) * 0.80
               + c3 * auroraColor(hue + 0.61) * 0.60;

    /* "Light responds": a hovered CTA pulls a soft ribbon of extra
       exposure toward itself (u_focus is the button's centre in uv). */
    vec2 fd = (uv - u_focus) * vec2(u_res.x / u_res.y, 1.0);
    light *= 1.0 + u_focusAmt * 1.5 * exp(-dot(fd, fd) * 9.0);

    /* Nonlinear intensity: landing (1.0) blazes, app pages (0.25/0.1)
       stay a whisper. The pow curve crushes the low end, so a linear
       floor keeps the quiet pages alive (0.25 → 0.14, 0.1 → 0.055)
       rather than pitch-black; the max() never touches 0.7 or 1.0. */
    light *= max(pow(u_intensity, 2.4) * 2.3, u_intensity * 0.55);

    /* Polar-night base (the --night token) + additive light, then a soft
       exponential shoulder so peaks bloom instead of clipping. */
    vec3 col = vec3(0.035, 0.045, 0.095) + light * 1.1;
    col = 1.0 - exp(-col * 1.5);
    col += (hash(gl_FragCoord.xy) - 0.5) / 255.0;   /* dither */

    fragColor = vec4(col, 1.0);
  }`;

    /* ------------------------------------------------------------- setup */

    /* Only render the sky on pages that opt in via <body data-aurora>. */
    if (document.body.dataset.aurora === undefined) {
        return;
    }

    /* Reuse the @persist'd canvas from the layout if present (survives
     wire:navigate); otherwise create one for pages that don't persist. */
    let canvas = document.getElementById("aurora");
    if (!canvas) {
        canvas = document.createElement("canvas");
        canvas.id = "aurora";
        canvas.setAttribute("aria-hidden", "true");
        document.body.prepend(canvas);
    }

    const gl = canvas.getContext("webgl2", {
        antialias: false,
        depth: false,
        stencil: false,
        alpha: false,
    });

    const reducedMotion = matchMedia(
        "(prefers-reduced-motion: reduce)",
    ).matches;
    const intensity = parseFloat(document.body.dataset.aurora || "0.25");

    if (!gl) {
        /* No WebGL2 → static CSS gradient fallback (see app.css). */
        document.documentElement.classList.add("no-aurora");
        canvas.remove();
    } else {
        start(gl);
    }

    function compile(gl, type, src) {
        const s = gl.createShader(type);
        gl.shaderSource(s, src);
        gl.compileShader(s);
        if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) {
            throw new Error(gl.getShaderInfoLog(s));
        }
        return s;
    }

    function start(gl) {
        let prog;
        try {
            prog = gl.createProgram();
            gl.attachShader(prog, compile(gl, gl.VERTEX_SHADER, VERT));
            gl.attachShader(prog, compile(gl, gl.FRAGMENT_SHADER, FRAG));
            gl.linkProgram(prog);
            if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) {
                throw new Error(gl.getProgramInfoLog(prog));
            }
        } catch (err) {
            /* Shader failure = same dignified fallback as no-WebGL. */
            console.warn("aurora: shader failed, using static gradient.", err);
            document.documentElement.classList.add("no-aurora");
            canvas.remove();
            return;
        }
        gl.useProgram(prog);

        const U = {};
        for (const name of [
            "u_res",
            "u_time",
            "u_intensity",
            "u_focus",
            "u_focusAmt",
        ]) {
            U[name] = gl.getUniformLocation(prog, name);
        }
        gl.uniform1f(U.u_intensity, intensity);

        /* Half-resolution render target — the aurora is soft, nobody can tell. */
        function resize() {
            const scale = Math.min(devicePixelRatio || 1, 2) * 0.5;
            canvas.width = Math.max(1, Math.round(innerWidth * scale));
            canvas.height = Math.max(1, Math.round(innerHeight * scale));
            gl.viewport(0, 0, canvas.width, canvas.height);
            gl.uniform2f(U.u_res, canvas.width, canvas.height);
        }
        resize();

        /* CTA light-pull state, eased every frame. */
        const pull = { x: 0.5, y: 0.55, amt: 0, target: 0 };
        const aimAt = (el, on) => () => {
            const r = el.getBoundingClientRect();
            pull.x = (r.left + r.width / 2) / innerWidth;
            pull.y = 1 - (r.top + r.height / 2) / innerHeight; /* GL y is up */
            pull.target = on ? 1 : 0;
        };
        document.querySelectorAll("[data-pull]").forEach((el) => {
            el.addEventListener("pointerenter", aimAt(el, true));
            el.addEventListener("pointerleave", aimAt(el, false));
            el.addEventListener("focus", aimAt(el, true));
            el.addEventListener("blur", aimAt(el, false));
        });

        function draw(seconds) {
            pull.amt += (pull.target - pull.amt) * 0.06;
            gl.uniform1f(U.u_time, seconds);
            gl.uniform2f(U.u_focus, pull.x, pull.y);
            gl.uniform1f(U.u_focusAmt, pull.amt);
            gl.drawArrays(gl.TRIANGLES, 0, 3);
        }

        if (reducedMotion) {
            /* One static frame, time frozen at a moment with good curtain
         separation. Re-rendered on resize; never animated. */
            const FROZEN = 34.5;
            draw(FROZEN);
            addEventListener("resize", () => {
                resize();
                draw(FROZEN);
            });
            return;
        }

        let raf = null;
        const loop = (ms) => {
            draw(ms / 1000);
            raf = requestAnimationFrame(loop);
        };
        raf = requestAnimationFrame(loop);

        /* Pause the whole show when the tab is hidden. */
        document.addEventListener("visibilitychange", () => {
            if (document.hidden) {
                cancelAnimationFrame(raf);
                raf = null;
            } else if (raf === null) {
                raf = requestAnimationFrame(loop);
            }
        });

        addEventListener("resize", resize);
    }

    /* ------------------------------------------- heading reveal (one-time)
     Section headings marked data-reveal rise 8px and settle from an
     over-exposed bloom the first time they enter the viewport. CSS owns
     the transition; reduced-motion users get them fully visible. */
    if ("IntersectionObserver" in window) {
        const io = new IntersectionObserver(
            (entries) => {
                for (const e of entries) {
                    if (e.isIntersecting) {
                        e.target.classList.add("revealed");
                        io.unobserve(e.target);
                    }
                }
            },
            { threshold: 0.25 },
        );
        document
            .querySelectorAll("[data-reveal]")
            .forEach((el) => io.observe(el));
    } else {
        document
            .querySelectorAll("[data-reveal]")
            .forEach((el) => el.classList.add("revealed"));
    }
})();
