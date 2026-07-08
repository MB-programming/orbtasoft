(function () {
  var canvasHost = document.getElementById('heroCanvas');
  if (!canvasHost || typeof THREE === 'undefined') return;

  var width = canvasHost.clientWidth;
  var height = canvasHost.clientHeight;

  var scene = new THREE.Scene();
  var camera = new THREE.PerspectiveCamera(60, width / height, 0.1, 1000);
  camera.position.z = 34;

  var renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.setSize(width, height);
  canvasHost.appendChild(renderer.domElement);

  var PARTICLE_COUNT = 260;
  var positions = new Float32Array(PARTICLE_COUNT * 3);
  var spread = 45;

  for (var i = 0; i < PARTICLE_COUNT; i++) {
    positions[i * 3] = (Math.random() - 0.5) * spread * 2;
    positions[i * 3 + 1] = (Math.random() - 0.5) * spread;
    positions[i * 3 + 2] = (Math.random() - 0.5) * spread;
  }

  var geometry = new THREE.BufferGeometry();
  geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

  var material = new THREE.PointsMaterial({
    color: 0x3b82f6,
    size: 0.22,
    transparent: true,
    opacity: 0.75,
    sizeAttenuation: true,
  });

  var points = new THREE.Points(geometry, material);
  scene.add(points);

  var wireGeo = new THREE.IcosahedronGeometry(9, 1);
  var wireMat = new THREE.MeshBasicMaterial({ color: 0x60a5fa, wireframe: true, transparent: true, opacity: 0.12 });
  var wireMesh = new THREE.Mesh(wireGeo, wireMat);
  scene.add(wireMesh);

  var mouseX = 0, mouseY = 0;
  window.addEventListener('mousemove', function (e) {
    mouseX = (e.clientX / window.innerWidth - 0.5) * 2;
    mouseY = (e.clientY / window.innerHeight - 0.5) * 2;
  });

  window.addEventListener('resize', function () {
    width = canvasHost.clientWidth;
    height = canvasHost.clientHeight;
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    renderer.setSize(width, height);
  });

  var clock = new THREE.Clock();

  function animate() {
    requestAnimationFrame(animate);
    var elapsed = clock.getElapsedTime();

    points.rotation.y = elapsed * 0.02 + mouseX * 0.15;
    points.rotation.x = mouseY * 0.1;

    wireMesh.rotation.y = elapsed * 0.05;
    wireMesh.rotation.x = elapsed * 0.03;

    camera.position.x += (mouseX * 3 - camera.position.x) * 0.02;
    camera.position.y += (-mouseY * 3 - camera.position.y) * 0.02;
    camera.lookAt(scene.position);

    renderer.render(scene, camera);
  }

  animate();
})();

/* Small standalone scene for the "Interactive 3D Experiences" service page. */
(function () {
  var canvasHost = document.getElementById('serviceThreeCanvas');
  if (!canvasHost || typeof THREE === 'undefined') return;

  var width = canvasHost.clientWidth;
  var height = canvasHost.clientHeight;

  var scene = new THREE.Scene();
  var camera = new THREE.PerspectiveCamera(55, width / height, 0.1, 1000);
  camera.position.z = 16;

  var renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.setSize(width, height);
  canvasHost.appendChild(renderer.domElement);

  var wireGeo = new THREE.IcosahedronGeometry(5.5, 1);
  var wireMat = new THREE.MeshBasicMaterial({ color: 0x60a5fa, wireframe: true, transparent: true, opacity: 0.55 });
  var wireMesh = new THREE.Mesh(wireGeo, wireMat);
  scene.add(wireMesh);

  var coreGeo = new THREE.IcosahedronGeometry(2.6, 0);
  var coreMat = new THREE.MeshBasicMaterial({ color: 0x3b82f6, wireframe: true, transparent: true, opacity: 0.35 });
  var coreMesh = new THREE.Mesh(coreGeo, coreMat);
  scene.add(coreMesh);

  window.addEventListener('resize', function () {
    if (!canvasHost.isConnected) return;
    width = canvasHost.clientWidth;
    height = canvasHost.clientHeight;
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    renderer.setSize(width, height);
  });

  var clock = new THREE.Clock();

  function animate() {
    if (!canvasHost.isConnected) return;
    requestAnimationFrame(animate);
    var elapsed = clock.getElapsedTime();
    wireMesh.rotation.y = elapsed * 0.25;
    wireMesh.rotation.x = elapsed * 0.12;
    coreMesh.rotation.y = -elapsed * 0.35;
    coreMesh.rotation.x = elapsed * 0.2;
    renderer.render(scene, camera);
  }

  animate();
})();
