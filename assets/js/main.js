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

  /* ---------- Hover slider (expertise) ---------- */
  var hoverSlider = document.getElementById('hoverSlider');
  if (hoverSlider) {
    var isCursiveScript = /[؀-ۿ]/;
    hoverSlider.querySelectorAll('.hover-slider__label').forEach(function (label) {
      var text = label.getAttribute('data-text') || '';
      if (isCursiveScript.test(text)) {
        label.textContent = text;
        return;
      }
      var chars = text.split('');
      chars.forEach(function (char, i) {
        var wrap = document.createElement('span');
        wrap.className = 'hover-slider__char';
        wrap.style.transitionDelay = (i * 0.025) + 's';

        var rest = document.createElement('span');
        rest.className = 'hover-slider__char-rest';
        rest.textContent = char === ' ' ? ' ' : char;

        var top = document.createElement('span');
        top.className = 'hover-slider__char-top';
        top.textContent = char === ' ' ? ' ' : char;

        wrap.appendChild(rest);
        wrap.appendChild(top);
        label.appendChild(wrap);
      });
    });

    var sliderItems = hoverSlider.querySelectorAll('.hover-slider__item');
    var sliderImages = hoverSlider.querySelectorAll('.hover-slider__image');
    function setActiveSlide(index) {
      sliderItems.forEach(function (item) {
        item.classList.toggle('is-active', item.getAttribute('data-index') === String(index));
      });
      sliderImages.forEach(function (img) {
        img.classList.toggle('is-active', img.getAttribute('data-index') === String(index));
      });
    }
    sliderItems.forEach(function (item) {
      var index = item.getAttribute('data-index');
      item.addEventListener('mouseenter', function () { setActiveSlide(index); });
      item.addEventListener('focus', function () { setActiveSlide(index); });
      item.addEventListener('click', function () { setActiveSlide(index); });
    });
  }

  /* ---------- Popout menu ---------- */
  var menuTrigger = document.getElementById('menuTrigger');
  var menuClose = document.getElementById('menuClose');
  var popoutMenu = document.getElementById('popoutMenu');
  var popoutBackdrop = document.getElementById('popoutBackdrop');

  function openMenu() {
    popoutMenu.classList.add('is-open');
    popoutMenu.setAttribute('aria-hidden', 'false');
    menuTrigger.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }
  function closeMenu() {
    popoutMenu.classList.remove('is-open');
    popoutMenu.setAttribute('aria-hidden', 'true');
    menuTrigger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }
  if (menuTrigger && popoutMenu) {
    menuTrigger.addEventListener('click', openMenu);
    if (menuClose) menuClose.addEventListener('click', closeMenu);
    if (popoutBackdrop) popoutBackdrop.addEventListener('click', closeMenu);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeMenu();
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
    var stackTrack = document.querySelector('.stack-track');
    if (stackTrack) {
      var originalWidth = stackTrack.scrollWidth / 2;
      gsap.to(stackTrack, {
        x: -originalWidth,
        duration: 22,
        ease: 'none',
        repeat: -1,
      });
    }

    /* Creative work gallery: pinned horizontal scroll on desktop,
       native swipe-scroll on mobile (handled by CSS overflow-x). */
    var isRTL = document.documentElement.dir === 'rtl';
    ScrollTrigger.matchMedia({
      '(min-width: 900px)': function () {
        document.querySelectorAll('.work-gallery__pin').forEach(function (pin) {
          var galleryTrack = pin.querySelector('.work-gallery__track');
          if (!galleryTrack) return;
          var getDistance = function () {
            return Math.max(0, galleryTrack.scrollWidth - pin.clientWidth);
          };
          gsap.to(galleryTrack, {
            x: function () { return isRTL ? getDistance() : -getDistance(); },
            ease: 'none',
            scrollTrigger: {
              trigger: pin,
              start: 'top top',
              end: function () { return '+=' + getDistance(); },
              scrub: 1,
              pin: true,
              anticipatePin: 1,
              invalidateOnRefresh: true,
            },
          });
        });
      },
    });
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

  /* ---------- Newsletter form + confetti ---------- */
  var newsletterForm = document.getElementById('newsletterForm');
  if (newsletterForm) {
    var wrap = document.getElementById('newsletterWrap');
    var canvas = document.getElementById('confettiCanvas');

    function fireConfetti() {
      if (!canvas) return;
      var ctx = canvas.getContext('2d');
      var colors = ['#3b82f6', '#10b981', '#fbbf24', '#f472b6', '#ffffff'];
      canvas.width = canvas.offsetWidth;
      canvas.height = canvas.offsetHeight;

      var particles = [];
      for (var i = 0; i < 50; i++) {
        particles.push({
          x: canvas.width / 2,
          y: canvas.height / 2,
          vx: (Math.random() - 0.5) * 12,
          vy: (Math.random() - 2) * 10,
          life: 100,
          color: colors[Math.floor(Math.random() * colors.length)],
          size: Math.random() * 4 + 2,
        });
      }

      function animate() {
        if (particles.length === 0) {
          ctx.clearRect(0, 0, canvas.width, canvas.height);
          return;
        }
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        for (var j = 0; j < particles.length; j++) {
          var p = particles[j];
          p.x += p.vx;
          p.y += p.vy;
          p.vy += 0.5;
          p.life -= 2;
          ctx.fillStyle = p.color;
          ctx.globalAlpha = Math.max(0, p.life / 100);
          ctx.beginPath();
          ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
          ctx.fill();
          if (p.life <= 0) {
            particles.splice(j, 1);
            j--;
          }
        }
        requestAnimationFrame(animate);
      }
      animate();
    }

    newsletterForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var submitBtn = newsletterForm.querySelector('button[type="submit"]');
      submitBtn.disabled = true;

      fetch('/newsletter-handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(new FormData(newsletterForm)),
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.success) {
            var successEl = document.getElementById('newsletterSuccessText');
            if (successEl) successEl.textContent = data.message;
            wrap.classList.add('is-success');
            fireConfetti();
            newsletterForm.reset();
          } else {
            submitBtn.disabled = false;
            var input = newsletterForm.querySelector('input[type="email"]');
            if (input) {
              input.style.borderColor = '#f87171';
              setTimeout(function () { input.style.borderColor = ''; }, 1600);
            }
          }
        })
        .catch(function () {
          submitBtn.disabled = false;
        });
    });
  }
});
