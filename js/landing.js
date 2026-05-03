/* ═══════════════════════════════════════════════
   ENSIASD LANDING — landing.js
   AOS + Bootstrap + Custom interactions
   ═══════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

  /* ── 1. Init AOS (scroll animations) ── */
  if (typeof AOS !== 'undefined') {
    AOS.init({
      duration: 700,
      easing: 'ease-out-cubic',
      once: true,
      offset: 60,
    });
  }

  /* ── 2. Navbar: add .scrolled class on scroll ── */
  const navbar = document.getElementById('mainNavbar');
  if (navbar) {
    const onScroll = () => {
      navbar.classList.toggle('scrolled', window.scrollY > 40);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll(); // run on load
  }

  /* ── 3. Active nav-link based on scroll position ── */
  const sections = document.querySelectorAll('section[id], div[id="home"]');
  const navLinks = document.querySelectorAll('.lp-nav-link');

  const activateLink = () => {
    let current = '';
    sections.forEach(sec => {
      const top = sec.offsetTop - 120;
      if (window.scrollY >= top) current = sec.getAttribute('id');
    });
    navLinks.forEach(a => {
      a.classList.toggle('active', a.getAttribute('href') === '#' + current);
    });
  };

  window.addEventListener('scroll', activateLink, { passive: true });

  /* ── 4. Smooth scroll for anchor links ── */
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', e => {
      const target = document.querySelector(link.getAttribute('href'));
      if (target) {
        e.preventDefault();
        const offset = navbar ? navbar.offsetHeight : 80;
        window.scrollTo({ top: target.offsetTop - offset, behavior: 'smooth' });

        // Close mobile menu if open
        const collapse = document.getElementById('navMenu');
        if (collapse && collapse.classList.contains('show')) {
          const bsCollapse = bootstrap.Collapse.getInstance(collapse);
          if (bsCollapse) bsCollapse.hide();
        }
      }
    });
  });

  /* ── 5. Animated counters ── */
  const counters = document.querySelectorAll('[data-count]');

  const animateCounter = (el) => {
    const target = parseInt(el.dataset.count, 10);
    const duration = 1800;
    const start = performance.now();

    const step = (timestamp) => {
      const progress = Math.min((timestamp - start) / duration, 1);
      // Ease-out cubic
      const eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.floor(eased * target);
      if (progress < 1) requestAnimationFrame(step);
      else el.textContent = target;
    };

    requestAnimationFrame(step);
  };

  // Intersection Observer to trigger counters when visible
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });

    counters.forEach(c => io.observe(c));
  } else {
    counters.forEach(c => animateCounter(c));
  }

  /* ── 6. Step cards: hover ripple effect ── */
  document.querySelectorAll('.lp-step-card, .lp-feature-item, .lp-exp-card').forEach(card => {
    card.addEventListener('mousemove', e => {
      const rect = card.getBoundingClientRect();
      const x = ((e.clientX - rect.left) / rect.width) * 100;
      const y = ((e.clientY - rect.top) / rect.height) * 100;
      card.style.setProperty('--mx', x + '%');
      card.style.setProperty('--my', y + '%');
    });
  });

  /* ── 7. Typewriter effect on hero badge ── */
  const badge = document.querySelector('.lp-badge');
  if (badge) {
    badge.style.opacity = '0';
    setTimeout(() => {
      badge.style.transition = 'opacity 0.5s';
      badge.style.opacity = '1';
    }, 300);
  }

  /* ── 8. Parallax orbs on mousemove ── */
  const orbs = document.querySelectorAll('.lp-hero-bg-orb');
  document.addEventListener('mousemove', e => {
    const cx = window.innerWidth / 2;
    const cy = window.innerHeight / 2;
    const dx = (e.clientX - cx) / cx;
    const dy = (e.clientY - cy) / cy;

    orbs.forEach((orb, i) => {
      const factor = (i + 1) * 12;
      orb.style.transform = `translate(${dx * factor}px, ${dy * factor}px)`;
    });
  });

  /* ── 9. Floating cards stagger on load ── */
  document.querySelectorAll('.lp-floating-card').forEach((card, i) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    setTimeout(() => {
      card.style.transition = 'opacity 0.6s ease, transform 0.6s cubic-bezier(0.34,1.56,0.64,1)';
      card.style.opacity = '1';
      card.style.transform = 'translateY(0)';
    }, 800 + i * 200);
  });

  /* ── 10. Experience cards: pulse icon on hover ── */
  document.querySelectorAll('.lp-exp-card').forEach(card => {
    const icon = card.querySelector('i');
    card.addEventListener('mouseenter', () => {
      if (!icon) return;
      icon.style.transition = 'transform 0.3s cubic-bezier(0.34,1.56,0.64,1)';
      icon.style.transform = 'scale(1.25) rotate(-5deg)';
    });
    card.addEventListener('mouseleave', () => {
      if (!icon) return;
      icon.style.transform = 'scale(1) rotate(0deg)';
    });
  });

  /* ── 11. Scroll-to-top on logo click (already href) ── */

  /* ── 12. Feature items: slide-in shimmer on hover ── */
  document.querySelectorAll('.lp-feature-item').forEach(item => {
    item.addEventListener('mouseenter', () => {
      item.style.transition = 'transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease';
    });
  });

  /* ── 13. CTA buttons: glow pulse ── */
  document.querySelectorAll('.lp-btn-primary').forEach(btn => {
    btn.addEventListener('mouseenter', () => {
      btn.style.boxShadow = '0 0 0 6px rgba(24,119,242,0.2)';
    });
    btn.addEventListener('mouseleave', () => {
      btn.style.boxShadow = '';
    });
  });

  console.log('🎓 ENSIASD Landing JS loaded ✓');
});