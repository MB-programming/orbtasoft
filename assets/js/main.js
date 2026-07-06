document.addEventListener('DOMContentLoaded', function () {
  if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
    gsap.registerPlugin(ScrollTrigger);
  }

  /* ---------- Header scroll state ---------- */
  var header = document.getElementById('siteHeader');
  function onScroll() {
    if (!header) return;
    header.classList.toggle('is-scrolled', window.scrollY > 40);
  }
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  /* ---------- Mobile nav ---------- */
  var navToggle = document.getElementById('navToggle');
  var mainNav = document.getElementById('mainNav');
  if (navToggle && mainNav) {
    navToggle.addEventListener('click', function () {
      var isOpen = mainNav.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    mainNav.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        mainNav.classList.remove('is-open');
        navToggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  /* ---------- Premium card mouse sheen + tilt ---------- */
  var card = document.querySelector('.premium-card');
  var deviceFrame = document.querySelector('.device__frame');
  if (card) {
    card.addEventListener('mousemove', function (e) {
      var rect = card.getBoundingClientRect();
      card.style.setProperty('--mx', (e.clientX - rect.left) + 'px');
      card.style.setProperty('--my', (e.clientY - rect.top) + 'px');

      if (deviceFrame && typeof gsap !== 'undefined') {
        var xVal = (e.clientX / window.innerWidth - 0.5) * 2;
        var yVal = (e.clientY / window.innerHeight - 0.5) * 2;
        gsap.to(deviceFrame, {
          rotationY: xVal * 10,
          rotationX: -yVal * 10,
          transformPerspective: 1000,
          ease: 'power3.out',
          duration: 1,
        });
      }
    });
  }

  /* ---------- GSAP scroll reveals ---------- */
  if (typeof gsap !== 'undefined') {
    var revealTargets = gsap.utils.toArray('.reveal');
    revealTargets.forEach(function (el, i) {
      gsap.set(el, { autoAlpha: 0, y: 40 });
      ScrollTrigger.create({
        trigger: el,
        start: 'top 85%',
        onEnter: function () {
          gsap.to(el, { autoAlpha: 1, y: 0, duration: 1, ease: 'power3.out', delay: (i % 3) * 0.08 });
        },
        once: true,
      });
    });

    /* Hero intro (only on pages that have one) */
    if (document.querySelector('.hero-line')) {
      var heroTl = gsap.timeline({ delay: 0.2 });
      gsap.set('.hero-line', { autoAlpha: 0, y: 50, filter: 'blur(14px)' });
      heroTl.to('.hero-line', { autoAlpha: 1, y: 0, filter: 'blur(0px)', duration: 1.3, ease: 'expo.out', stagger: 0.15 });
    }

    /* Counter animation on the device metric */
    var counter = document.querySelector('.counter-val');
    if (counter) {
      var target = parseInt(counter.getAttribute('data-target'), 10) || 0;
      ScrollTrigger.create({
        trigger: counter,
        start: 'top 80%',
        once: true,
        onEnter: function () {
          var obj = { val: 0 };
          gsap.to(obj, {
            val: target,
            duration: 2,
            ease: 'power2.out',
            onUpdate: function () { counter.textContent = Math.round(obj.val).toLocaleString(); },
          });
        },
      });
    }

    /* Typewriter code lines in device mockup */
    var codeLines = gsap.utils.toArray('.code-line');
    if (codeLines.length) {
      gsap.set(codeLines, { autoAlpha: 0 });
      ScrollTrigger.create({
        trigger: '.device',
        start: 'top 75%',
        once: true,
        onEnter: function () {
          gsap.to(codeLines, { autoAlpha: 1, duration: 0.4, stagger: 0.25, ease: 'none' });
        },
      });
    }

    /* Stack marquee */
    var track = document.querySelector('.stack-track');
    if (track) {
      var originalWidth = track.scrollWidth / 2;
      gsap.to(track, {
        x: -originalWidth,
        duration: 22,
        ease: 'none',
        repeat: -1,
      });
    }
  }

  /* ---------- Contact form ---------- */
  var form = document.getElementById('contactForm');
  if (form) {
    var status = document.getElementById('formStatus');
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var submitBtn = form.querySelector('button[type="submit"]');
      submitBtn.disabled = true;

      fetch('/contact-handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(new FormData(form)),
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          status.textContent = data.message;
          status.className = 'form-status ' + (data.success ? 'is-success' : 'is-error');
          if (data.success) form.reset();
        })
        .catch(function () {
          status.textContent = form.getAttribute('data-error-msg') || 'Something went wrong.';
          status.className = 'form-status is-error';
        })
        .finally(function () {
          submitBtn.disabled = false;
        });
    });
  }
});
