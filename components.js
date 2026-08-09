(function () {
  'use strict';

  /* ─────────────────────────────────────────────
     DEMO FORM ENDPOINT
     Paste the Google Apps Script Web App URL here
     after deploying (see google-apps-script.gs).
     Until set, submissions show success but are not
     sent anywhere.
  ───────────────────────────────────────────── */
  var DEMO_ENDPOINT = 'https://script.google.com/macros/s/AKfycbyV3HXCiL_Byibth5crnjCZDNFN2ly-kvTo8wn4b1DRmuHsnp6Z4tWFk2VPHWX_EVc/exec';

  /* ─────────────────────────────────────────────
     HEADER HTML
  ───────────────────────────────────────────── */
  var HEADER_HTML = `
<div class="topbar">
  <div class="container">
    <div class="tb-left">📍 Online &amp; Offline — Delhi · Gurugram · Noida · Bangalore · Pune · Mumbai</div>
    <div class="tb-right">
      <a href="tel:+917303609096">📞 +91 73036 09096</a>
      <a href="mailto:support@thexlacademy.com">✉ support@thexlacademy.com</a>
    </div>
  </div>
</div>
<header class="nav">
  <div class="container nav-inner">
    <a href="/" class="logo">
      <img src="images/logo.png" alt="The XL Academy" class="logo-img" onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
      <span class="logo-txt" style="display:none">The XL<span> Academy</span></span>
    </a>
    <ul class="menu" id="menu">
      <li class="has-drop"><a href="/#courses">Our Courses</a>
        <ul class="dropdown">
          <li style="font-weight:700;padding:6px 14px 2px;font-size:10px;letter-spacing:1px;text-transform:uppercase;color:var(--orange)">Career Programs</li>
          <li><a href="data-science">Data Science with ML</a></li>
          <li><a href="data-analytics-python">Data Analytics &amp; Python</a></li>
          <li><a href="data-analytics">Data Analytics</a></li>
          <li><a href="mis-reporting">MIS &amp; Reporting</a></li>
          <li style="font-weight:700;padding:10px 14px 2px;font-size:10px;letter-spacing:1px;text-transform:uppercase;color:var(--orange);border-top:1px solid var(--line)">Modular Courses</li>
          <li><a href="python">Python &amp; Pandas</a></li>
          <li><a href="machine-learning">Machine Learning</a></li>
          <li><a href="sql-server">SQL Server</a></li>
          <li><a href="power-bi">Power BI</a></li>
          <li><a href="tableau">Tableau</a></li>
          <li><a href="advanced-excel">Advanced Excel</a></li>
        </ul>
      </li>
      <li class="has-drop"><a href="#">Branches</a>
        <ul class="dropdown">
          <li><a href="branch-delhi">New Delhi (HO)</a></li>
          <li><a href="branch-gurugram">Gurugram</a></li>
          <li><a href="branch-noida">Noida</a></li>
          <li><a href="branch-bangalore">Bangalore</a></li>
          <li><a href="branch-pune">Pune</a></li>
          <li><a href="branch-mumbai">Mumbai</a></li>
        </ul>
      </li>
      <li><a href="placement">Placement</a></li>
      <li><a href="enroll">Enroll Now</a></li>
      <li class="has-drop"><a href="#">More</a>
        <ul class="dropdown">
          <li><a href="pay-now">Pay Now</a></li>
          <li><a href="education-loan">Education Loan</a></li>
          <li><a href="blog">Blog</a></li>
        </ul>
      </li>
    </ul>
    <div class="nav-cta">
      <button class="btn btn-primary" onclick="openModal()">Book Free Demo</button>
      <button class="hamburger" id="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
    </div>
  </div>
</header>`;

  /* ─────────────────────────────────────────────
     FOOTER HTML
  ───────────────────────────────────────────── */
  var FOOTER_HTML = `
<footer>
  <div class="container">
    <div class="foot-grid">
      <div>
        <div class="foot-logo">
          <a href="/" class="foot-logo-img-wrap">
            <img src="images/logo.png" alt="The XL Academy" class="foot-logo-img">
          </a>
        </div>
        <p>Professional training for Data Science, Analytics &amp; MIS — Online &amp; Offline across 6 cities.</p>
        <div class="socials">
          <a href="https://www.facebook.com/thexla" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z"/></svg></a>
          <a href="https://www.linkedin.com/company/thexlacademy/" target="_blank" rel="noopener" aria-label="LinkedIn"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
          <a href="https://www.youtube.com/@thexlacademy" target="_blank" rel="noopener" aria-label="YouTube"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>
          <a href="https://instagram.com/academythexl" target="_blank" rel="noopener" aria-label="Instagram"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/></svg></a>
        </div>
      </div>
      <div>
        <h4>Branches</h4>
        <ul>
          <li><a href="branch-delhi">New Delhi (HO)</a></li>
          <li><a href="branch-gurugram">Gurugram</a></li>
          <li><a href="branch-noida">Noida</a></li>
          <li><a href="branch-bangalore">Bangalore</a></li>
          <li><a href="branch-pune">Pune</a></li>
          <li><a href="branch-mumbai">Mumbai</a></li>
        </ul>
      </div>
      <div>
        <h4>Courses</h4>
        <ul>
          <li><a href="data-science">Data Science with ML</a></li>
          <li><a href="data-analytics-python">Data Analytics &amp; Python</a></li>
          <li><a href="data-analytics">Data Analytics</a></li>
          <li><a href="mis-reporting">MIS &amp; Reporting</a></li>
          <li><a href="python">Python &amp; Pandas</a></li>
          <li><a href="power-bi">Power BI</a></li>
          <li><a href="tableau">Tableau</a></li>
          <li><a href="sql-server">SQL Server</a></li>
          <li><a href="advanced-excel">Advanced Excel</a></li>
        </ul>
        <h4 class="foot-h4-next">Blog</h4>
        <ul>
          <li><a href="blog">Latest Articles</a></li>
        </ul>
      </div>
      <div>
        <h4>Contact</h4>
        <ul>
          <li><a href="mailto:support@thexlacademy.com">support@thexlacademy.com</a></li>
          <li><a href="tel:+917303609096">+91 73036 09096</a></li>
          <li>HQ: YC Co-Working Space, Dwarka Sec-13, New Delhi 110078</li>
        </ul>
      </div>
    </div>
    <div class="foot-bottom">
      <div>© 2026 The XL Academy. All rights reserved.</div>
      <div style="display:flex;gap:18px">
        <a href="privacy-policy.html">Privacy Policy</a>
        <a href="terms-conditions.html">Terms &amp; Conditions</a>
        <a href="cancellation-refund.html">Refund Policy</a>
      </div>
    </div>
  </div>
</footer>

<div class="float-cta">
  <a class="fc-wa" href="https://wa.me/917303609096" target="_blank" rel="noopener" aria-label="WhatsApp">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#fff" width="28" height="28"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
  </a>
  <a class="fc-call" href="tel:+917303609096" aria-label="Call">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#fff" width="26" height="26"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1C10.61 21 3 13.39 3 4c0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/><path d="M15.5 1a1 1 0 000 2 7 7 0 017 7 1 1 0 002 0c0-4.97-4.03-9-9-9zm0 4a1 1 0 000 2 3 3 0 013 3 1 1 0 002 0c0-2.76-2.24-5-5-5z"/></svg>
  </a>
</div>

<div class="modal" id="modal" onclick="if(event.target===this)closeModal()">
  <div class="modal-card">
    <div class="modal-head">
      <button class="modal-close" onclick="closeModal()" aria-label="Close">×</button>
      <h3>Book a <span>Free</span> Demo Class. NOW!</h3>
      <p>Our counsellor will call you within 24 hours.</p>
    </div>
    <div class="modal-body">
      <form id="admissionForm" novalidate>
        <div class="field"><label>Full Name <span class="req">*</span></label><input name="name" type="text" required placeholder="Enter your full name"></div>
        <div class="field"><label>Phone Number <span class="req">*</span></label>
          <div class="phone-wrap">
            <span class="flag" aria-hidden="true"><svg viewBox="0 0 30 20" width="22" height="15" xmlns="http://www.w3.org/2000/svg"><rect width="30" height="20" fill="#fff"/><rect width="30" height="6.67" fill="#FF9933"/><rect y="13.33" width="30" height="6.67" fill="#138808"/><circle cx="15" cy="10" r="2.6" fill="none" stroke="#000080" stroke-width=".7"/></svg></span>
            <input name="mobile" type="tel" required placeholder="Phone number" pattern="[0-9]{10}">
          </div>
        </div>
        <div class="field"><label>Email Address <span class="req">*</span></label><input name="email" type="email" required placeholder="you@example.com"></div>
        <div class="field"><label>Preferred City <span class="req">*</span></label><input name="city" type="text" required placeholder="e.g. Delhi, Mumbai, Bangalore..."></div>
        <button class="btn-submit" type="submit">Submit Enquiry →</button>
        <div class="form-msg" id="formMsg"></div>
        <p class="modal-foot">By submitting, you agree to our <a href="terms-conditions.html">Terms</a> and <a href="privacy-policy.html">Privacy Policy</a>. We never spam.</p>
      </form>
    </div>
  </div>
</div>`;

  /* ─────────────────────────────────────────────
     INJECT — runs synchronously; script is at
     end of <body> so DOM is already parsed
  ───────────────────────────────────────────── */
  function inject() {
    var h = document.getElementById('site-header');
    if (h) h.innerHTML = HEADER_HTML;
    var f = document.getElementById('site-footer');
    if (f) f.innerHTML = FOOTER_HTML;

    setActiveNav();
    initHamburger();
    initModalForm();
  }

  /* ─────────────────────────────────────────────
     ACTIVE NAV STATE
  ───────────────────────────────────────────── */
  function setActiveNav() {
    var raw = location.pathname.split('/').pop() || '';
    var page = raw.replace(/\.html$/, '') || 'index';
    document.querySelectorAll('.menu a').forEach(function (link) {
      var attr = (link.getAttribute('href') || '').split('/').pop().split('#')[0];
      var href = attr.replace(/\.html$/, '');
      if (!href || href === '#' || href === '') return;
      if (href === page) {
        link.classList.add('active');
        var li = link.closest('li');
        if (li) li.classList.add('active');
        var drop = link.closest('.has-drop');
        if (drop && drop !== li) drop.classList.add('active');
      }
    });
  }

  /* ─────────────────────────────────────────────
     HAMBURGER MENU
  ───────────────────────────────────────────── */
  function initHamburger() {
    var ham = document.getElementById('hamburger');
    var menu = document.getElementById('menu');
    if (!ham || !menu) return;
    ham.addEventListener('click', function () { menu.classList.toggle('open'); });
    menu.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        if (window.innerWidth <= 760 && !a.parentElement.classList.contains('has-drop'))
          menu.classList.remove('open');
      });
    });
  }

  /* ─────────────────────────────────────────────
     DEMO MODAL FORM
  ───────────────────────────────────────────── */
  function initModalForm() {
    var form = document.getElementById('admissionForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var msg = document.getElementById('formMsg');
      if (!form.checkValidity()) {
        msg.style.color = '#e8650a';
        msg.textContent = 'Please complete all fields correctly.';
        return;
      }

      var btn = form.querySelector('button[type="submit"]');
      var origTxt = btn ? btn.textContent : '';
      if (btn) { btn.disabled = true; btn.textContent = 'Submitting…'; }
      msg.style.color = '#5a6478';
      msg.textContent = 'Submitting your enquiry…';

      function done(ok) {
        if (ok) {
          msg.style.color = '#1a9c3e';
          msg.textContent = '✓ Thanks! Redirecting…';
          form.reset();
          window.location.href = 'thank-you.html';
        } else {
          if (btn) { btn.disabled = false; btn.textContent = origTxt; }
          msg.style.color = '#e8650a';
          msg.textContent = 'Something went wrong. Please try again or WhatsApp us.';
        }
      }

      var payload = new URLSearchParams({
        name: form.elements['name'].value,
        mobile: form.elements['mobile'].value,
        email: form.elements['email'].value,
        city: form.elements['city'].value,
        source: (document.title || '') + ' | ' + location.href
      });

      if (!DEMO_ENDPOINT || DEMO_ENDPOINT.indexOf('PASTE_') === 0) {
        console.warn('Demo form endpoint not configured. Set DEMO_ENDPOINT in components.js.');
        done(true);
        return;
      }

      // no-cors: Apps Script processes the POST and saves the row even though
      // the response is opaque, so we treat a resolved fetch as success.
      fetch(DEMO_ENDPOINT, { method: 'POST', mode: 'no-cors', body: payload })
        .then(function () { done(true); })
        .catch(function () { done(false); });
    });
  }

  /* ─────────────────────────────────────────────
     GLOBAL HELPERS (called from onclick in HTML)
  ───────────────────────────────────────────── */
  window.openModal = function () {
    var m = document.getElementById('modal');
    if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
  };
  window.closeModal = function () {
    var m = document.getElementById('modal');
    if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
  };
  window.scrollTml = function (dir) {
    var t = document.getElementById('tmlTrack');
    if (t) t.scrollBy({ left: dir * 382, behavior: 'smooth' });
  };

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') window.closeModal();
  });

  /* ─────────────────────────────────────────────
     BOOT
  ───────────────────────────────────────────── */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inject);
  } else {
    inject();
  }

})();

