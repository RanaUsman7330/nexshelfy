/* ============================================
   NEXSHELFY v2.0 - MODERN INTERACTIONS
   Complete JS Overhaul with Animations
   ============================================ */

(function() {
  'use strict';

  // ===== Theme Toggle =====
  const Theme = {
    init() {
      const saved = localStorage.getItem('ns-theme');
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      if (saved === 'dark' || (!saved && prefersDark)) {
        document.documentElement.setAttribute('data-theme', 'dark');
      }
      this.bindToggle();
    },
    bindToggle() {
      const btn = document.getElementById('themeToggle');
      if (!btn) return;
      btn.addEventListener('click', () => {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        if (isDark) {
          document.documentElement.removeAttribute('data-theme');
          localStorage.setItem('ns-theme', 'light');
          btn.textContent = '🌙';
        } else {
          document.documentElement.setAttribute('data-theme', 'dark');
          localStorage.setItem('ns-theme', 'dark');
          btn.textContent = '☀️';
        }
      });
      // Set initial icon
      const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      btn.textContent = isDark ? '☀️' : '🌙';
    }
  };

  // ===== Scroll Animations =====
  const ScrollAnimations = {
    init() {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

      document.querySelectorAll('.ns-animate').forEach(el => observer.observe(el));
    }
  };

  // ===== Header Scroll Effect =====
  const Header = {
    init() {
      const header = document.querySelector('.ns-header');
      if (!header) return;
      let ticking = false;
      window.addEventListener('scroll', () => {
        if (!ticking) {
          requestAnimationFrame(() => {
            header.classList.toggle('scrolled', window.scrollY > 20);
            ticking = false;
          });
          ticking = true;
        }
      });
    }
  };

  // ===== Back to Top =====
  const BackToTop = {
    init() {
      const btn = document.querySelector('.ns-back-to-top');
      if (!btn) return;
      window.addEventListener('scroll', () => {
        btn.classList.toggle('visible', window.scrollY > 500);
      });
      btn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    }
  };

  // ===== Mobile Menu =====
  const MobileMenu = {
    init() {
      const btn = document.querySelector('.ns-mobile-menu-btn');
      const nav = document.querySelector('.ns-mobile-nav');
      if (!btn || !nav) return;
      btn.addEventListener('click', () => {
        btn.classList.toggle('active');
        nav.classList.toggle('active');
        document.body.style.overflow = nav.classList.contains('active') ? 'hidden' : '';
      });
      nav.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
          btn.classList.remove('active');
          nav.classList.remove('active');
          document.body.style.overflow = '';
        });
      });
    }
  };

  // ===== Toast Notifications =====
  const Toast = {
    container: null,
    init() {
      this.container = document.createElement('div');
      this.container.className = 'ns-toast-container';
      document.body.appendChild(this.container);
    },
    show(message, type = 'info', title = '') {
      if (!this.container) this.init();
      const icons = { success: '✅', error: '❌', info: 'ℹ️' };
      const toast = document.createElement('div');
      toast.className = `ns-toast ${type}`;
      toast.innerHTML = `
        <span class="ns-toast-icon">${icons[type]}</span>
        <div class="ns-toast-content">
          ${title ? `<b>${title}</b>` : ''}
          <span>${message}</span>
        </div>
        <button class="ns-toast-close">&times;</button>
      `;
      this.container.appendChild(toast);
      toast.querySelector('.ns-toast-close').addEventListener('click', () => this.remove(toast));
      setTimeout(() => this.remove(toast), 5000);
    },
    remove(toast) {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      setTimeout(() => toast.remove(), 300);
    }
  };

  // ===== Counter Animation =====
  const Counter = {
    init() {
      const counters = document.querySelectorAll('[data-counter]');
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            this.animate(entry.target);
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.5 });
      counters.forEach(c => observer.observe(c));
    },
    animate(el) {
      const target = parseInt(el.dataset.counter);
      const suffix = el.dataset.suffix || '';
      const duration = 2000;
      const start = performance.now();
      const animate = (now) => {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = Math.floor(eased * target);
        const fallback = el.textContent;
        if (fallback && fallback !== '0') { el.textContent = fallback; return; }
        el.textContent = current.toLocaleString() + suffix;
        if (progress < 1) requestAnimationFrame(animate);
      };
      requestAnimationFrame(animate);
    }
  };

  // ===== Product Save (Heart) =====
  const ProductSave = {
    init() {
      document.querySelectorAll('[data-save]').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          btn.classList.toggle('saved');
          const isSaved = btn.classList.contains('saved');
          btn.innerHTML = isSaved ? '♥' : '♡';
          Toast.show(
            isSaved ? 'Saved to your collection' : 'Removed from collection',
            'success',
            isSaved ? 'Added!' : 'Removed!'
          );
        });
      });
    }
  };

  // ===== Search Focus =====
  const Search = {
    init() {
      const inputs = document.querySelectorAll('.ns-hero-search input, .ns-filter-search input');
      inputs.forEach(input => {
        input.addEventListener('focus', () => {
          input.parentElement.style.transform = 'scale(1.02)';
        });
        input.addEventListener('blur', () => {
          input.parentElement.style.transform = 'scale(1)';
        });
      });
    }
  };

  // ===== Smooth Scroll for Anchor Links =====
  const SmoothScroll = {
    init() {
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
          const target = document.querySelector(this.getAttribute('href'));
          if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        });
      });
    }
  };

  // ===== Ripple Effect =====
  const Ripple = {
    init() {
      document.querySelectorAll('.ns-btn-primary, .ns-header-btn.primary').forEach(btn => {
        btn.addEventListener('click', function(e) {
          const rect = this.getBoundingClientRect();
          const ripple = document.createElement('span');
          const size = Math.max(rect.width, rect.height);
          ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${e.clientX - rect.left - size/2}px;
            top: ${e.clientY - rect.top - size/2}px;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
          `;
          this.style.position = 'relative';
          this.style.overflow = 'hidden';
          this.appendChild(ripple);
          setTimeout(() => ripple.remove(), 600);
        });
      });
    }
  };

  // ===== Newsletter Form =====
  const Newsletter = {
    init() {
      const forms = document.querySelectorAll('.ns-newsletter-form');
      forms.forEach(form => {
        form.addEventListener('submit', (e) => {
          e.preventDefault();
          const input = form.querySelector('input');
          if (input.value) {
            Toast.show('You have been subscribed!', 'success', 'Welcome!');
            input.value = '';
          }
        });
      });
    }
  };

  // ===== Skeleton Loading =====
  const Skeleton = {
    show(container) {
      container.innerHTML = '<div class="ns-skeleton" style="height:200px;width:100%;"></div>';
    },
    hide(container, content) {
      container.innerHTML = content;
    }
  };

  // ===== Initialize Everything =====
  document.addEventListener('DOMContentLoaded', () => {
    Theme.init();
    ScrollAnimations.init();
    Header.init();
    BackToTop.init();
    MobileMenu.init();
    Toast.init();
    Counter.init();
    ProductSave.init();
    Search.init();
    SmoothScroll.init();
    Ripple.init();
    Newsletter.init();

    console.log('🚀 NexShelfy v2.0 loaded successfully!');
  });

  // Expose globally
  window.NexShelfy = { Toast, Skeleton, Theme };

})();
