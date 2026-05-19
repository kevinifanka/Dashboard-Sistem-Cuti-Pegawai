// public/assets/js/sidebar.js
// Sidebar: mobile toggle, active state, responsive, auto-close

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const sidebar      = document.getElementById('sidebar');
    const overlay      = document.getElementById('sidebarOverlay');
    const hamburgerBtn = document.getElementById('hamburgerBtn');

    if (!sidebar || !overlay || !hamburgerBtn) return;

    // =========================================================
    // 1. OPEN / CLOSE HELPERS
    // =========================================================
    function openSidebar() {
      sidebar.classList.add('sidebar-open');
      overlay.classList.add('active');
      hamburgerBtn.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
      sidebar.classList.remove('sidebar-open');
      overlay.classList.remove('active');
      hamburgerBtn.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    }

    function isDesktop() {
      return window.innerWidth > 768;
    }

    // =========================================================
    // 2. HAMBURGER TOGGLE
    // =========================================================
    hamburgerBtn.addEventListener('click', function () {
      if (sidebar.classList.contains('sidebar-open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });

    // =========================================================
    // 3. CLOSE ON OVERLAY CLICK
    // =========================================================
    overlay.addEventListener('click', closeSidebar);

    // =========================================================
    // 4. CLOSE ON ESCAPE KEY
    // =========================================================
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && sidebar.classList.contains('sidebar-open')) {
        closeSidebar();
      }
    });

    // =========================================================
    // 5. CLOSE ON WINDOW RESIZE TO DESKTOP
    // =========================================================
    var resizeTimer;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        if (isDesktop()) {
          closeSidebar();
        }
      }, 100);
    });

    // =========================================================
    // 6. AUTO-CLOSE SIDEBAR WHEN NAV LINK IS CLICKED (mobile)
    //    — So the sidebar closes before navigating to next page
    // =========================================================
    var navLinks = sidebar.querySelectorAll('.nav-item, .logout-btn');
    navLinks.forEach(function (link) {
      link.addEventListener('click', function () {
        if (!isDesktop()) {
          closeSidebar();
        }
      });
    });

    // =========================================================
    // 7. ACTIVE STATE — PHP sets it via $currentPage.
    //    JS only ensures the correct item gets active when the
    //    URL param matches, without removing PHP-set classes.
    // =========================================================
    var currentSearch = window.location.search;

    sidebar.querySelectorAll('.nav-item').forEach(function (item) {
      var href      = item.getAttribute('href') || '';
      var urlParams = new URLSearchParams(href.split('?')[1] || '');
      var pageVal   = urlParams.get('page') || '';

      // Only add active if not already set by PHP AND URL matches
      if (pageVal && currentSearch.includes('page=' + pageVal)) {
        if (!item.classList.contains('active')) {
          item.classList.add('active');
          item.setAttribute('aria-current', 'page');
        }
      }
    });

    // =========================================================
    // 8. PROGRESS BAR ANIMATION (dashboard)
    // =========================================================
    document.querySelectorAll('.progress-fill').forEach(function (bar) {
      var targetWidth = bar.style.width;
      if (!targetWidth) return;

      bar.style.width      = '0%';
      bar.style.transition = 'none';

      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          bar.style.transition = 'width 800ms cubic-bezier(0.4, 0, 0.2, 1)';
          bar.style.width      = targetWidth;
        });
      });
    });

    // =========================================================
    // 9. QUOTA BAR ANIMATION (profile page)
    // =========================================================
    document.querySelectorAll('.quota-fill').forEach(function (bar) {
      var targetWidth = bar.style.width;
      if (!targetWidth) return;

      bar.style.width      = '0%';
      bar.style.transition = 'none';

      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          bar.style.transition = 'width 900ms cubic-bezier(0.4, 0, 0.2, 1)';
          bar.style.width      = targetWidth;
        });
      });
    });

    // =========================================================
    // 10. STAT CARD ENTRANCE ANIMATION (dashboard)
    // =========================================================
    document.querySelectorAll('.stat-card').forEach(function (card, index) {
      card.style.opacity   = '0';
      card.style.transform = 'translateY(12px)';
      card.style.transition = 'opacity 400ms ease, transform 400ms ease';

      setTimeout(function () {
        card.style.opacity   = '1';
        card.style.transform = 'translateY(0)';
      }, index * 80);
    });

    // =========================================================
    // 11. SUMMARY CARD ENTRANCE ANIMATION (requests, employees)
    // =========================================================
    document.querySelectorAll('.summary-card, .report-sum-card').forEach(function (card, index) {
      card.style.opacity   = '0';
      card.style.transform = 'translateY(10px)';
      card.style.transition = 'opacity 350ms ease, transform 350ms ease';

      setTimeout(function () {
        card.style.opacity   = '1';
        card.style.transform = 'translateY(0)';
      }, index * 60);
    });

  });

})();
