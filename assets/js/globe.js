document.addEventListener('DOMContentLoaded', function () {
  var canvas = document.getElementById('globeCanvas');
  if (!canvas || typeof window.createGlobe === 'undefined') return;

  var phi = 0;
  var width = 0;

  function onResize() {
    width = canvas.offsetWidth;
  }
  onResize();
  window.addEventListener('resize', onResize);

  var globe = window.createGlobe(canvas, {
    devicePixelRatio: 2,
    width: width * 2,
    height: width * 2,
    phi: 0,
    theta: 0.3,
    dark: 0,
    diffuse: 0.4,
    mapSamples: 16000,
    mapBrightness: 1.2,
    baseColor: [0.13, 0.18, 0.35],
    markerColor: [0.24, 0.51, 0.96],
    glowColor: [0.24, 0.51, 0.96],
    markers: [
      { location: [41.0082, 28.9784], size: 0.06 },
      { location: [40.7128, -74.006], size: 0.1 },
      { location: [34.6937, 135.5022], size: 0.05 },
      { location: [-23.5505, -46.6333], size: 0.1 },
      { location: [30.0444, 31.2357], size: 0.08 },
    ],
    onRender: function (state) {
      phi += 0.005;
      state.phi = phi;
      state.width = width * 2;
      state.height = width * 2;
    },
  });

  window.addEventListener('beforeunload', function () {
    if (globe) globe.destroy();
  });
});
