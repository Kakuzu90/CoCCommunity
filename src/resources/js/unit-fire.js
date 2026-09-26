// Fire ring for maxed level badges: flames hug the badge outline on every side and lick upward,
// tallest above the badge and shortest below it, so the badge reads as burning, not sitting on a fire.
const SIZE = 48;
// The badge occupies the centre of the canvas; CSS insets the canvas by the same margin (12px).
const MARGIN = 12;
const RADIUS = 5;
const FRAME_MS = 1000 / 30;
// Share the heat field so a full progression grid only runs one simulation.
const targets = new Map();
let renderer;

function createRenderer() {
    const source = document.createElement('canvas');
    source.width = SIZE;
    source.height = SIZE;
    const context = source.getContext('2d');
    const pixels = context.createImageData(SIZE, SIZE);
    const noise = Float32Array.from({ length: 1024 }, () => Math.random());
    const motion = matchMedia('(prefers-reduced-motion: reduce)');
    let frame = 0;
    let tick = Math.random() * 1000;
    let lastTime = 0;

    // Per pixel, once: distance outside the rounded badge, and how far flame may reach there.
    const distance = new Float32Array(SIZE * SIZE);
    const reach = new Float32Array(SIZE * SIZE);
    const low = MARGIN;
    const high = SIZE - 1 - MARGIN;
    for (let y = 0; y < SIZE; y++) {
        for (let x = 0; x < SIZE; x++) {
            const dx = Math.max(low + RADIUS - x, 0, x - (high - RADIUS));
            const dy = Math.max(low + RADIUS - y, 0, y - (high - RADIUS));
            distance[y * SIZE + x] = Math.hypot(dx, dy) - RADIUS;
            const up = Math.min(1, Math.max(0, (high - y) / (high - low + MARGIN)));
            reach[y * SIZE + x] = 2.5 + up * up * 9;
        }
    }

    // Ember → red → orange → yellow → white-hot, with alpha rising out of transparent smoke.
    const palette = new Uint8ClampedArray(256 * 4);
    for (let i = 0; i < 256; i++) {
        const t = i / 255;
        palette[i * 4] = Math.min(255, t * 3.2 * 255);
        palette[i * 4 + 1] = Math.max(0, Math.min(255, (t - .32) * 2.6 * 255));
        palette[i * 4 + 2] = Math.max(0, Math.min(255, (t - .8) * 5 * 255));
        const alpha = Math.min(1, Math.max(0, (t - .08) * 2.2));
        palette[i * 4 + 3] = alpha * alpha * (3 - 2 * alpha) * 255;
    }

    function sample(x, y) {
        const column = Math.floor(x);
        const row = Math.floor(y);
        let horizontal = x - column;
        let vertical = y - row;
        horizontal *= horizontal * (3 - 2 * horizontal);
        vertical *= vertical * (3 - 2 * vertical);
        const a = noise[(row & 31) * 32 + (column & 31)];
        const b = noise[(row & 31) * 32 + ((column + 1) & 31)];
        const c = noise[((row + 1) & 31) * 32 + (column & 31)];
        const d = noise[((row + 1) & 31) * 32 + ((column + 1) & 31)];
        return (a + (b - a) * horizontal) * (1 - vertical) + (c + (d - c) * horizontal) * vertical;
    }

    function paint() {
        // Sampling at y + flow moves the noise upward, so tongues rise off every edge.
        const flow = ++tick * .16;
        const gain = .9 + sample(tick * .03, 11.7) * .25;
        for (let y = 0; y < SIZE; y++) {
            const edgeFade = Math.min(1, y / 5, (SIZE - 1 - y) / 3);
            for (let x = 0; x < SIZE; x++) {
                const i = y * SIZE + x;
                const sideFade = Math.min(1, x / 3, (SIZE - 1 - x) / 3);
                const turbulence = sample(x * .2, y * .16 + flow) * .6 + sample(x * .45, y * .38 + flow * 1.7) * .4;
                const body = 1 - Math.max(0, distance[i]) / reach[i];
                const heat = Math.max(0, Math.min(1, (body * 1.15 + (turbulence - .5) * 1.3 - .12) * gain * edgeFade * sideFade));
                const index = Math.floor(heat * 255) * 4;
                const offset = i * 4;
                pixels.data[offset] = palette[index];
                pixels.data[offset + 1] = palette[index + 1];
                pixels.data[offset + 2] = palette[index + 2];
                pixels.data[offset + 3] = palette[index + 3];
            }
        }
        context.putImageData(pixels, 0, 0);
    }

    function draw(target) {
        target.context.clearRect(0, 0, SIZE, SIZE);
        target.context.save();
        if (target.mirror) {
            target.context.translate(SIZE, 0);
            target.context.scale(-1, 1);
        }
        target.context.drawImage(source, 0, 0);
        target.context.restore();
    }

    function animate(time) {
        frame = 0;
        if (time - lastTime >= FRAME_MS) {
            paint();
            for (const target of targets.values()) if (target.visible) draw(target);
            lastTime = time;
        }
        resume();
    }

    function resume() {
        const active = !motion.matches && !document.hidden && [...targets.values()].some(target => target.visible);
        if (active && !frame) frame = requestAnimationFrame(animate);
        if (!active && frame) {
            cancelAnimationFrame(frame);
            frame = 0;
        }
    }

    const observer = new IntersectionObserver(entries => {
        for (const entry of entries) {
            const target = targets.get(entry.target);
            if (target) {
                target.visible = entry.isIntersecting;
                if (target.visible) draw(target);
            }
        }
        resume();
    });
    motion.addEventListener('change', resume);
    document.addEventListener('visibilitychange', resume);
    paint();

    return {
        add(canvas) {
            const context = canvas.getContext('2d');
            if (!context) return;
            const target = { context, visible: false, mirror: Math.random() > .5 };
            targets.set(canvas, target);
            draw(target);
            observer.observe(canvas);
        },
        remove(canvas) {
            observer.unobserve(canvas);
            targets.delete(canvas);
            resume();
        },
    };
}

export function unitFire() {
    return {
        init() {
            renderer ??= createRenderer();
            renderer.add(this.$el);
        },
        destroy() {
            renderer?.remove(this.$el);
        },
    };
}
